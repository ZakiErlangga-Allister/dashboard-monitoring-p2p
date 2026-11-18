<?php

namespace Tests\Unit;

use App\Services\TahapPo;
use PHPUnit\Framework\TestCase;

class TahapPoTest extends TestCase
{
    private function statuses(array $hasil): array
    {
        return array_column($hasil['tahap'], 'status');
    }

    public function test_semua_selesai_bila_pembayaran_terisi(): void
    {
        $h = TahapPo::hitung([
            1 => '2026-09-01', 2 => '2026-09-15', 3 => '2026-10-01',
            4 => '2026-10-10', 5 => '2026-10-15', 6 => '2026-10-25',
        ]);

        $this->assertSame(array_fill(0, 6, 'Selesai'), $this->statuses($h));
        $this->assertTrue($h['selesai']);
        $this->assertSame('Selesai', $h['saat_ini']);
    }

    public function test_po_tanpa_invoice_membuat_tahap_invoice_berjalan(): void
    {
        $h = TahapPo::hitung([1 => '2026-09-01', 2 => '2026-09-15', 3 => '2026-10-01']);

        $this->assertSame(
            ['Selesai', 'Selesai', 'Selesai', 'Berjalan', 'Menunggu', 'Menunggu'],
            $this->statuses($h)
        );
        $this->assertSame('Invoice diterima', $h['saat_ini']);
        $this->assertFalse($h['selesai']);
    }

    public function test_hanya_pr_membuat_tahap_po_berjalan(): void
    {
        $h = TahapPo::hitung([1 => '2026-09-01']);

        $this->assertSame(
            ['Selesai', 'Berjalan', 'Menunggu', 'Menunggu', 'Menunggu', 'Menunggu'],
            $this->statuses($h)
        );
        $this->assertSame('PO terbit', $h['saat_ini']);
    }

    public function test_tanpa_tanggal_apa_pun_tahap_pertama_berjalan(): void
    {
        $h = TahapPo::hitung([]);

        $this->assertSame('Berjalan', $this->statuses($h)[0]);
        $this->assertSame('PR disetujui', $h['saat_ini']);
    }

    public function test_tahap_sebelum_yang_terakhir_dianggap_selesai_walau_tanggal_kosong(): void
    {
        $h = TahapPo::hitung([1 => '2026-09-01', 3 => '2026-10-01']);

        $this->assertSame('Selesai', $this->statuses($h)[1]);
        $this->assertNull($h['tahap'][1]['tanggal']);
        $this->assertSame('Invoice diterima', $h['saat_ini']);
    }

    public function test_verifikasi_berjalan_bila_invoice_ada_tapi_mvp_belum_disetujui(): void
    {
        $h = TahapPo::hitung([
            1 => '2026-09-01', 2 => '2026-09-15', 3 => '2026-10-01', 4 => '2026-10-10',
        ]);

        $this->assertSame('Verifikasi dan MVP', $h['saat_ini']);
        $this->assertSame('Berjalan', $this->statuses($h)[4]);
    }
}
