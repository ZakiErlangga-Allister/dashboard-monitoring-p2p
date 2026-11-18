<?php

namespace App\Services;

use App\Models\Invoice;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Menghitung status dan label sebuah invoice di Jadwal bayar.
 * Aturan: PRD BR-03 sampai BR-14 (flowchart 2.3).
 * Fungsi hitung() murni (tanpa basis data), sehingga mudah diuji.
 */
final class StatusJadwalBayar
{
    public const DIBAYAR = 'Dibayar';
    public const TERTAHAN = 'Tertahan';
    public const MENUNGGU_TTD = 'Menunggu tanda tangan';
    public const SIAP_BAYAR = 'Siap bayar';

    public const LABEL_TERLAMBAT = 'Terlambat';
    public const LABEL_MENDEKATI = 'Mendekati jatuh tempo';
    public const LABEL_SELISIH = 'Selisih data';

    private const BUTIR = [
        'bg_ada' => 'Bank garansi',
        'skf_ada' => 'SKF',
        'surat_pernyataan_ada' => 'Surat pernyataan',
        'rekening_ada' => 'Rekening penerima',
    ];

    /**
     * @param array $d Kunci: tgl_bayar, bg_ada, skf_ada, surat_pernyataan_ada, rekening_ada,
     *                 ttd_status, jatuh_tempo_sap, jatuh_tempo_mitraku, status_bg_sap, status_bg_mitraku
     * @return array{status:string,kelengkapan:int,kurang:array,label:array,sisa_hari:?int}
     */
    public static function hitung(array $d, DateTimeInterface $acuan, int $ambangHari = 3): array
    {
        // BR-03: kelengkapan = butir terisi dari empat butir
        $kurang = [];
        foreach (self::BUTIR as $kolom => $nama) {
            if (! (bool) ($d[$kolom] ?? false)) {
                $kurang[] = $nama;
            }
        }
        $kelengkapan = count(self::BUTIR) - count($kurang);

        $dibayar = self::tgl($d['tgl_bayar'] ?? null) !== null;
        $sudahTtd = ($d['ttd_status'] ?? 'belum') === 'sudah';

        // BR-14: prioritas Dibayar, Tertahan, Menunggu tanda tangan, Siap bayar
        if ($dibayar) {
            $status = self::DIBAYAR;
        } elseif ($kelengkapan < 4) {
            $status = self::TERTAHAN;
        } elseif (! $sudahTtd) {
            $status = self::MENUNGGU_TTD;
        } else {
            $status = self::SIAP_BAYAR;
        }

        // Sisa hari ke jatuh tempo SAP (negatif = sudah lewat)
        $jatuhTempo = self::tgl($d['jatuh_tempo_sap'] ?? null);
        $sisa = null;
        if ($jatuhTempo !== null) {
            $hariIni = self::tgl($acuan)->setTime(0, 0);
            $sisa = (int) $hariIni->diff($jatuhTempo->setTime(0, 0))->format('%r%a');
        }

        // BR-08 dan BR-09: Terlambat dan Mendekati tidak muncul bersamaan
        $label = [];
        if (! $dibayar && $sisa !== null) {
            if ($sisa < 0) {
                $label[] = self::LABEL_TERLAMBAT;
            } elseif ($sisa <= $ambangHari) {
                $label[] = self::LABEL_MENDEKATI;
            }
        }

        // BR-10
        if (self::adaSelisih($d)) {
            $label[] = self::LABEL_SELISIH;
        }

        return [
            'status' => $status,
            'kelengkapan' => $kelengkapan,
            'kurang' => $kurang,
            'label' => $label,
            'sisa_hari' => $sisa,
        ];
    }

    /** Menghitung dari model Invoice (relasi catatanDokumen dan pembayaran boleh kosong). */
    public static function dariInvoice(Invoice $invoice, DateTimeInterface $acuan, int $ambangHari = 3): array
    {
        $c = $invoice->catatanDokumen;

        return self::hitung([
            'tgl_bayar' => $invoice->pembayaran?->tgl_bayar,
            'bg_ada' => $c?->bg_ada,
            'skf_ada' => $c?->skf_ada,
            'surat_pernyataan_ada' => $c?->surat_pernyataan_ada,
            'rekening_ada' => $c?->rekening_ada,
            'ttd_status' => $c?->ttd_status ?? 'belum',
            'jatuh_tempo_sap' => $invoice->jatuh_tempo_sap,
            'jatuh_tempo_mitraku' => $invoice->jatuh_tempo_mitraku,
            'status_bg_sap' => $invoice->status_bg_sap,
            'status_bg_mitraku' => $invoice->status_bg_mitraku,
        ], $acuan, $ambangHari);
    }

    private static function adaSelisih(array $d): bool
    {
        $sap = self::tgl($d['jatuh_tempo_sap'] ?? null)?->format('Y-m-d');
        $mitraku = self::tgl($d['jatuh_tempo_mitraku'] ?? null)?->format('Y-m-d');
        $bgSap = strtolower(trim((string) ($d['status_bg_sap'] ?? '')));
        $bgMitraku = strtolower(trim((string) ($d['status_bg_mitraku'] ?? '')));

        return $sap !== $mitraku || $bgSap !== $bgMitraku;
    }

    private static function tgl(mixed $v): ?DateTimeImmutable
    {
        if ($v === null || $v === '') {
            return null;
        }
        if ($v instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($v);
        }

        return new DateTimeImmutable((string) $v);
    }
}
