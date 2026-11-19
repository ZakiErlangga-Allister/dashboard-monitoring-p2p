<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Vendor;
use App\Services\TahapPo;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;


class LacakStatusController extends Controller
{
    private const SUMBER = [
        1 => 'ProcureX', 'SAP',
        2 => 'SAP',
        3 => 'MOKONDO', 'SAP',
        4 => 'Mitraku',
        5 => 'SAP', 'Mitraku',
        6 => 'Aplikasi Bank, Mitraku',
    ];

    private const RELASI = [
        'purchaseOrder.vendor',
        'purchaseOrder.goodsReceipts',
        'purchaseOrder.invoices.mvp',
        'purchaseOrder.invoices.pembayaran',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'urut' => ['nullable', 'in:terbaru,terlama'],
        ]);

        $q = trim((string) $request->input('q', ''));
        $tahap = $request->input('tahap', 'berjalan');
        $vendorId = $request->integer('vendor');
        $dari = $request->input('dari');
        $sampai =$request->input('sampai');
        $urut = $request->input('urut', 'terbaru');

        $query = PurchaseRequisition::query()->with(self::RELASI);

        if ($q !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(function ($w) use ($like) {
                $w->where('no_pr', 'like', $like)
                ->orWhereHas('purchaseOrder', function ($p) use ($like) {
                    $p->where('no_po', 'like', $like)
                        ->orWhereHas('vendor', fn ($v) => $v->where('nama', 'like', $like));
                }) ;
            });
        }

        if ($vendorId > 0) {
            $query->whereHas('purchaseOrder', fn ($p) => $p->where('vendor_id', $vendorId));
        }

        $baris = $query->get()->map(function (PurchaseRequisition $pr) {
            $hasil = TahapPo::dariPurchaseRequisition($pr);
            $berisi = collect($hasil['tahap'])->filter(fn ($t) => $t['tanggal'] !== null);

            return [
                'pr' => $pr,
                'po' => $pr->purchaseOrder,
                'vendor' => $pr->purchaseOrder?->vendor,
                'saat_ini' => $hasil['saat_ini'],
                'selesai' => $hasil['selesai'],
                'terakhir' => $berisi->isNotEmpty() ? Carbon::instance($berisi->last()['tanggal']) : null,
            ];
        });

        if ($tahap === 'berjalan') {
    $baris = $baris->filter(fn ($b) => ! $b['selesai']);
} elseif ($tahap === 'selesai') {
    $baris = $baris->filter(fn ($b) => $b['selesai']);
} elseif ($tahap !== 'semua') {
    $baris = $baris->filter(
        fn ($b) => ! $b['selesai'] && $b['saat_ini'] === $tahap
    );
}

    if ($dari) {
        $baris = $baris->filter(
            fn ($b) => $b['terakhir'] && $b['terakhir']->format('Y-m-d') >= $dari
        );
    }

    if ($sampai) {
        $baris = $baris->filter(
            fn ($b) => $b['terakhir'] && $b['terakhir']->format('Y-m-d') <= $sampai
        );
    }

    $baris = $baris
        ->sortBy(
            fn ($b) => $b['terakhir']?->timestamp ?? 0,
            SORT_REGULAR,
            $urut !== 'terlama'
        )
        ->values();
        
        $perHalaman = 15;
        $halaman = LengthAwarePaginator::resolveCurrentPage();
        $paginasi = (new LengthAwarePaginator(
            $baris->forPage($halaman, $perHalaman)->values(),
            $baris->count(),
            $perHalaman,
            $halaman,
            ['path' => $request->url()]  
        ))->withQueryString();

        return view('lacak-status.index', [
            'paginasi' => $paginasi,
            'daftarTahap' => collect(TahapPo::TAHAP)->except(1)->values()->all(),
            'daftarVendor' => Vendor::orderBy('nama')->get(['id', 'nama']),
            'dataPer' => Pengaturan::find('data_per')?->nilai,
            'filter' => [
                'q' => $q,
                'tahap' => $tahap,
                'vendor' => $vendorId,
                'dari' => $dari,
                'sampai' => $sampai,
                'urut' => $urut,
            ],
        ]);
    }

    public function show(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        $pr = $purchaseRequisition->load(self::RELASI);
        $po = $pr->purchaseOrder;
        $gr = $po?->goodsReceipts->sortBy('tgl_gr')->first();
        $invoice = $po?->invoices->sortBy('tgl_terima_kasir')->first();
        $hasil = TahapPo::dariPurchaseRequisition($pr);

        $dokumen = [
            1 => $pr->no_pr,
            2 => $po?->no_po,
            3 => $gr?->no_gr,
            4 => $invoice?->no_invoice,
            5 => $invoice?->mvp?->no_mvp,
            6 => null,
        ];

        $garis = collect($hasil['tahap'])->map(fn ($t) => [
            'no' => $t['no'],
            'nama' => $t['nama'],
            'status' => $t['status'],
            'tanggal' => $t['tanggal'] ? Carbon::instance($t['tanggal'])->translatedFormat('d M Y') : null,
            'dokumen' => $dokumen[$t['no']],
            'sumber' => self::SUMBER[$t['no']],
        ]);

        //FR-23: data sensitif  hanya dikirim ke tampilan bila peran berhak
        $lengkap = in_array($request->user()->peran, ['staf_keuangan', 'admin'], true);

        $ringkasan =[
            'No. PR' => $pr->no_pr,
            'Jenis PR' => $pr->jenis,
            'No. PO' => $po?->no_po ?? 'Belum terbit',
            'Vendor' => $po?->vendor?->nama ?? '-',
        ];

        if ($lengkap && $po) {
            $ringkasan['nilai PO'] = 'Rp '.number_format($po->nilai,0, ',', '.');
            $ringkasan['Bank penerima'] = $po->vendor->bank_penerima;
            $ringkasan['No. rekening'] = $po->vendor->no_rekening;
        }

        return view('lacak-status.show', [
            'ringkasan' => $ringkasan,
            'garis' => $garis,
            'saatIni' => $hasil['saat_ini'],
            'selesai' => $hasil['selesai'],
            'invoice' => $invoice,
            'bisaKeJadwalBayar' => $lengkap && $invoice?->mvp?->tgl_serah_keuangan !== null,
            'dataPer' => Pengaturan::find('data_per')?->nilai,
        ]);
    }
}
