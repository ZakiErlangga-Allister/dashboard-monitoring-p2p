<?php

namespace Tests\Unit;

use App\Services\StatusJadwalBayar;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class StatusJadwalBayarTest extends TestCase
{
    private function acuan(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-17');
    }

    private function dasar(array $ubah = []): array
    {
        return array_merge([
            'tgl_bayar' => null,
            'bg_ada' => true,
            'skf_ada' => true,
            'surat_pernyataan_ada' => true,
            'rekening_ada' => true,
            'ttd_status' => 'sudah',
            'jatuh_tempo_sap' => '2026-11-30',
            'jatuh_tempo_mitraku' => '2026-11-30',
            'status_bg_sap' => 'Valid',
            'status_bg_mitraku' => 'Valid',
        ], $ubah);
    }

    private function hitung(array $ubah = [], int $ambang = 3): array
    {
        return StatusJadwalBayar::hitung($this->dasar($ubah), $this->acuan(), $ambang);
    }

    public function test_siap_bayar_bila_lengkap_dan_sudah_ttd(): void
    {
        $h = $this->hitung();

        $this->assertSame('Siap bayar', $h['status']);
        $this->assertSame(4, $h['kelengkapan']);
        $this->assertEmpty($h['label']);
    }

    public function test_menunggu_tanda_tangan_bila_lengkap_tapi_belum_ttd(): void
    {
        $h = $this->hitung(['ttd_status' => 'belum']);

        $this->assertSame('Menunggu tanda tangan', $h['status']);
    }

    public function test_tertahan_menyebut_butir_yang_kurang(): void
    {
        $h = $this->hitung(['bg_ada' => false, 'rekening_ada' => false]);

        $this->assertSame('Tertahan', $h['status']);
        $this->assertSame(2, $h['kelengkapan']);
        $this->assertSame(['Bank garansi', 'Rekening penerima'], $h['kurang']);
    }

    public function test_tertahan_menang_atas_tanda_tangan_sudah(): void
    {
        $h = $this->hitung(['skf_ada' => false, 'ttd_status' => 'sudah']);

        $this->assertSame('Tertahan', $h['status']);
    }

    public function test_dibayar_menang_atas_kelengkapan(): void
    {
        $h = $this->hitung(['tgl_bayar' => '2026-11-10', 'bg_ada' => false]);

        $this->assertSame('Dibayar', $h['status']);
    }

    public function test_terlambat_bila_jatuh_tempo_lewat_dan_belum_dibayar(): void
    {
        $h = $this->hitung(['jatuh_tempo_sap' => '2026-11-16', 'jatuh_tempo_mitraku' => '2026-11-16']);

        $this->assertContains('Terlambat', $h['label']);
        $this->assertNotContains('Mendekati jatuh tempo', $h['label']);
        $this->assertSame(-1, $h['sisa_hari']);
    }

    public function test_dibayar_tidak_diberi_label_terlambat(): void
    {
        $h = $this->hitung([
            'tgl_bayar' => '2026-11-05',
            'jatuh_tempo_sap' => '2026-11-10',
            'jatuh_tempo_mitraku' => '2026-11-10',
        ]);

        $this->assertNotContains('Terlambat', $h['label']);
    }

    public function test_jatuh_tempo_hari_ini_mendekati_bukan_terlambat(): void
    {
        $h = $this->hitung(['jatuh_tempo_sap' => '2026-11-17', 'jatuh_tempo_mitraku' => '2026-11-17']);

        $this->assertContains('Mendekati jatuh tempo', $h['label']);
        $this->assertNotContains('Terlambat', $h['label']);
        $this->assertSame(0, $h['sisa_hari']);
    }

    public function test_mendekati_tepat_di_batas_ambang(): void
    {
        $tepat = $this->hitung(['jatuh_tempo_sap' => '2026-11-20', 'jatuh_tempo_mitraku' => '2026-11-20']);
        $lewat = $this->hitung(['jatuh_tempo_sap' => '2026-11-21', 'jatuh_tempo_mitraku' => '2026-11-21']);

        $this->assertContains('Mendekati jatuh tempo', $tepat['label']);
        $this->assertNotContains('Mendekati jatuh tempo', $lewat['label']);
    }

    public function test_ambang_nol_hanya_menandai_jatuh_tempo_hari_ini(): void
    {
        $hariIni = $this->hitung(['jatuh_tempo_sap' => '2026-11-17', 'jatuh_tempo_mitraku' => '2026-11-17'], 0);
        $besok = $this->hitung(['jatuh_tempo_sap' => '2026-11-18', 'jatuh_tempo_mitraku' => '2026-11-18'], 0);

        $this->assertContains('Mendekati jatuh tempo', $hariIni['label']);
        $this->assertEmpty($besok['label']);
    }

    public function test_selisih_jatuh_tempo_antara_sap_dan_mitraku(): void
    {
        $h = $this->hitung(['jatuh_tempo_mitraku' => '2026-11-29']);

        $this->assertContains('Selisih data', $h['label']);
    }

    public function test_selisih_status_bank_garansi(): void
    {
        $h = $this->hitung(['status_bg_mitraku' => 'Tidak valid']);

        $this->assertContains('Selisih data', $h['label']);
    }

    public function test_status_bank_garansi_tidak_peka_huruf_besar(): void
    {
        $h = $this->hitung(['status_bg_sap' => 'Valid', 'status_bg_mitraku' => 'valid ']);

        $this->assertNotContains('Selisih data', $h['label']);
    }

    public function test_tanpa_catatan_dianggap_tertahan_dengan_empat_butir_kurang(): void
    {
        $h = StatusJadwalBayar::hitung([
            'jatuh_tempo_sap' => '2026-11-30',
            'jatuh_tempo_mitraku' => '2026-11-30',
        ], $this->acuan());

        $this->assertSame('Tertahan', $h['status']);
        $this->assertSame(0, $h['kelengkapan']);
        $this->assertCount(4, $h['kurang']);
    }

    public function test_selisih_tetap_ditandai_pada_invoice_yang_sudah_dibayar(): void
    {
        $h = $this->hitung(['tgl_bayar' => '2026-11-10', 'jatuh_tempo_mitraku' => '2026-11-29']);

        $this->assertSame('Dibayar', $h['status']);
        $this->assertContains('Selisih data', $h['label']);
    }
}
