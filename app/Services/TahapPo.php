<?php

namespace App\Services;

use App\Models\PurchaseRequisition;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Menghitung status enam tahap garis waktu sebuah PO (PRD BR-02, flowchart 2.2).
 * Selesai: sampai tahap terakhir yang tanggalnya terisi. Berjalan: tahap berikutnya.
 * Menunggu: sisanya.
 */
final class TahapPo
{
    public const TAHAP = [
        1 => 'PR disetujui',
        2 => 'PO terbit',
        3 => 'Barang diterima',
        4 => 'Invoice diterima',
        5 => 'Verifikasi dan MVP',
        6 => 'Pembayaran',
    ];

    /**
     * @param array $tanggal Indeks 1 sampai 6 berisi tanggal (string, DateTime) atau null
     * @return array{tahap:array,saat_ini:string,selesai:bool}
     */
    public static function hitung(array $tanggal): array
    {
        $terakhir = 0;
        foreach (array_keys(self::TAHAP) as $no) {
            if (self::tgl($tanggal[$no] ?? null) !== null) {
                $terakhir = $no;
            }
        }

        $hasil = [];
        $saatIni = 'Selesai';
        foreach (self::TAHAP as $no => $nama) {
            if ($no <= $terakhir) {
                $status = 'Selesai';
            } elseif ($no === $terakhir + 1) {
                $status = 'Berjalan';
                $saatIni = $nama;
            } else {
                $status = 'Menunggu';
            }

            $hasil[] = [
                'no' => $no,
                'nama' => $nama,
                'tanggal' => self::tgl($tanggal[$no] ?? null),
                'status' => $status,
            ];
        }

        return ['tahap' => $hasil, 'saat_ini' => $saatIni, 'selesai' => $terakhir === 6];
    }

    /**
     * Dari PR (PR yang belum menjadi PO tetap bisa dihitung).
     * Asumsi prototipe: satu PO punya satu invoice (pengiriman parsial di luar lingkup),
     * jadi tahap 4 sampai 6 memakai invoice paling awal.
     */
    public static function dariPurchaseRequisition(PurchaseRequisition $pr): array
    {
        $po = $pr->purchaseOrder;
        $gr = $po?->goodsReceipts->sortBy('tgl_gr')->first();
        $invoice = $po?->invoices->sortBy('tgl_terima_kasir')->first();

        return self::hitung([
            1 => $pr->tgl_disetujui,
            2 => $po?->tgl_terbit,
            3 => $gr?->tgl_gr,
            4 => $invoice?->tgl_terima_kasir,
            5 => $invoice?->mvp?->tgl_disetujui,
            6 => $invoice?->pembayaran?->tgl_bayar,
        ]);
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
