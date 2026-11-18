<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Pengaturan;
use App\Services\PengaturanAplikasi;
use App\Services\StatusJadwalBayar;
use Illuminate\Http\Request;

class JadwalBayarController extends Controller
{
    public function index(Request $request)
    {
        $acuan = PengaturanAplikasi::tanggalAcuan();
        $ambang = PengaturanAplikasi::ambangHari();

        //BR-13: Hanya invoice yang mvp-nya sudah diserahkan ke keuangan
        $semua = Invoice::query()
            ->whereHas('mvp', fn ($q) => $q->whereNotNull('tgl_serah_keuangan'))
            ->with(['purchaseOrder.vendor', 'mvp', 'catatanDokumen', 'Pembayaran'])
            ->get()
            ->map(fn (invoice $invoice) => [
                'invoice' => $invoice,
                'vendor' => $invoice->purchaseOrder->vendor,
                'hasil' => StatusJadwalBayar::dariInvoice($invoice, $acuan, $ambang),
            ]);

        $ringkasan = [
            'hari_ini' => $semua->filter(fn ($b) => $b['hasil']['status'] !== StatusJadwalBayar::DIBAYAR
                && $b['hasil']['sisa_hari'] === 0)->count(),
            'siap_bayar' => $semua->filter(fn ($b) => $b['hasil']['status'] === StatusJadwalBayar::SIAP_BAYAR)->count(),
            "perlu_tindakan" => $semua->filter(fn ($b) => in_array($b['hasil']['status'], [
                StatusJadwalBayar::TERTAHAN,
                StatusJadwalBayar::MENUNGGU_TTD,
            ], true))->count(),
        ];

        $dari = $request->date('dari');
        $sampai = $request->date('sampai');
        $bank = $request->input('bank');
        $vendor = trim((string) $request->input('vendor', ''));
        $status = $request->input('status', 'belum_dibayar');


        $baris = $semua
            ->when($dari, fn ($c) => $c->filter(
                fn ($b) => $b['invoice']->jatuh_tempo_sap->gte($dari)
            ))
            ->when($sampai, fn ($c) => $c->filter(
                fn ($b) => $b['invoice']->jatuh_tempo_sap->lte($sampai)
            ))
            ->when($bank, fn ($c) => $c->filter(
                fn ($b) => $b['vendor']->bank_penerima === $bank
            ))
            ->when($vendor !== '', fn ($c) => $c->filter(
                fn ($b) => str_contains(
                    mb_strtolower($b['vendor']->nama),
                    mb_strtolower($vendor)
                )
            ))
            ->when($status === 'belum_dibayar', fn ($c) => $c->filter(
                fn ($b) => $b['hasil']['status'] !== StatusJadwalBayar::DIBAYAR
            ))
            ->sortBy(fn ($b) => $b['invoice']->jatuh_tempo_sap->timestamp)
            ->values();

        
        return view('jadwal-bayar.index', [
            'baris' => $baris,
            'ringkasan' => $ringkasan,
            'daftarBank' => explode(',', Pengaturan::find('daftar_bank')?->nilai ?? 'BRI,Mandiri,BNI,BCA'),
            'daftarStatus' => [
                StatusJadwalBayar::SIAP_BAYAR,
                StatusJadwalBayar::MENUNGGU_TTD,
                StatusJadwalBayar::TERTAHAN,
                StatusJadwalBayar::DIBAYAR,
            ],
            'dataPer' => Pengaturan::find('data_per')?->nilai,
            'filter' => [
                'dari' => $request->input('dari'),
                'sampai' => $request->input('sampai'),
                'bank' => $bank,
                'vendor' => $vendor,
                'status' => $status,
            ],
        ]);
    }
}
