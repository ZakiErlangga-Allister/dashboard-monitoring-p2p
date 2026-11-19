<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PurchaseRequisition;
use App\Services\PengaturanAplikasi;
use App\Services\StatusJadwalBayar;
use App\Services\TahapPo;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $lengkap = in_array($request->user()->peran, ['staf_keuangan', 'admin'], true);

        // PO yang masih berjalan, per tahap (semua peran)
        $perTahap = PurchaseRequisition::query()
        ->with([
            'purchaseOrder.goodsReceipts',
            'purchaseOrder.invoices.mvp',
            'purchaseOrder.invoices.pembayaran',
        ])
        ->get()
        ->map(fn (PurchaseRequisition $pr) => TahapPo::dariPurchaseRequisition($pr))
        ->reject(fn ($hasil) => $hasil['selesai'])
        ->countBy('saat_ini');
    
    $kartuTahap = collect(TahapPo::TAHAP)
        ->except(1)
        ->map(fn ($nama) => ['nama' => $nama, 'jumlah' => $perTahap->get($nama, 0)])
        ->values();

    $ringkasan = null;
    $perluTIndakan =collect();
    $tanggal= null;

    // Data keuangan hanya dihitung dan dikirim bila perannya berhak
    if ($lengkap) {
        $acuan = PengaturanAplikasi::tanggalAcuan();
        $ambang = PengaturanAplikasi::ambangHari();

        $baris = Invoice::query()
            ->whereHas('mvp', fn ($q) => $q->whereNotNull('tgl_serah_keuangan'))
            ->with(['purchaseOrder.vendor', 'mvp', 'catatanDokumen', 'pembayaran'])
            ->get()
            ->map(fn (Invoice $invoice) => [
                'invoice' => $invoice,
                'hasil' => StatusJadwalBayar::dariInvoice($invoice, $acuan, $ambang),
            ]);

            $belumBayar = $baris->filter(
                fn ($b) => $b['hasil']['status'] !== StatusJadwalBayar::DIBAYAR
            );

            $ringkasan = [
                'hari_ini' => $belumBayar->filter(
                    fn($b) => $b['hasil']['sisa_hari'] === 0
                )->count(),
                'tujuh_hari' => $belumBayar->filter(
                    fn ($b) => $b['hasil']['sisa_hari'] >= 0 && $b['hasil']['sisa_hari'] <= 7
                )->count(),
                'terlambat' => $belumBayar->filter(
                    fn ($b) => $b['hasil']['sisa_hari'] < 0 
                )->count(),
                'peringatan' => $baris->filter(
                    fn ($b) => in_array(StatusJadwalBayar::LABEL_SELISIH, $b['hasil']['label'], true)
                )->count(),
            ];

            //Yang sudah lewat atau jatuh tempo hari ini, paling terlambat di atas
            $perluTIndakan = $belumBayar
                ->filter(fn ($b) => $b['hasil']['sisa_hari'] <= 0)
                ->sortBy(fn ($b) => $b['hasil']['sisa_hari'])
                ->values();

            $tanggal = [
                'hari_ini' => $acuan->format('Y-m-d'),
                'tujuh_hari' => $acuan->modify('+7 days')->format('Y-m-d'),
                'kemarin' => $acuan->modify('-1 day')->format('Y-m-d'),
            ];
        }

        return view('dashboard', [
            'kartuTahap' => $kartuTahap,
            'ringkasan' => $ringkasan,
            'perluTindakan' => $perluTIndakan,
            'tanggal' => $tanggal,
        ]);
    }
}
