<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimpanCatatanDokumenRequest;
use App\Models\CatatanDokumen;
use App\Models\Invoice;
use App\Models\LogAktivitas;
use App\Models\RiwayatCatatan;
use App\Services\PengaturanAplikasi;
use App\Services\StatusJadwalBayar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CatatanDokumenController extends Controller
{
    private const KOLOM = [
        'bg_ada' => 'Bank garansi',
        'skf_ada' => 'SKF',
        'surat_pernyataan_ada' => 'Surat_pernyataan',
        'rekening_ada' => 'rekening penerima',
        'ttd_status' => 'Tanda tangan otorisator',
        'ttd_tanggal' => 'tanggal tanda tangan',
        'lokasi_arsip' => 'Lokasi arsip',
    ];

    public function show(Request $request, Invoice $invoice)
    {
        $this->pastikanDiJadwalBayar($invoice);

        $invoice->load(['purchaseOrder.vendor', 'mvp', 'pembayaran', 'catatanDokumen.riwayat.user']);

        $hasil = StatusJadwalBayar::dariInvoice(
            $invoice,
            PengaturanAplikasi::tanggalAcuan(),
            PengaturanAplikasi::ambangHari()
        );

        return view ('jadwal-bayar.show', [
            'invoice' => $invoice,
            'vendor' => $invoice->purchaseOrder->vendor,
            'catatan' => $invoice->catatanDokumen ?? new CatatanDokumen(),
            'riwayat' => $invoice->catatanDokumen?->riwayat->sortByDesc('id')->values() ?? collect(),
            'hasil' => $hasil,
            'bolehUbah' => $request->user()->peran === 'staf_keuangan',
            'namaKolom' => self::KOLOM
        ]);
    }

    public function update(SimpanCatatanDokumenRequest $request, Invoice $invoice)
    {
        $this->pastikanDiJadwalBayar($invoice);

        $data = $request->validated();
        if ($data['ttd_status'] !== 'sudah') {
            $data['ttd_tanggal'] = null;
        }
        $data['lokasi_arsip'] = filled($data['lokasi_arsip'] ?? null) ? trim($data['lokasi_arsip']) : null;

        $catatan = $invoice->catatanDokumen ?? new CatatanDokumen(['invoice_id' => $invoice->id]);

        //nilai sebelum diubah (untuk Riwayat)
        $lama = [];
        foreach (array_keys(self::KOLOM) as $kolom) {
            $lama[$kolom] = $this->teks($kolom, $catatan->exists ? $catatan->{$kolom} : $this->bawaan($kolom));
        }

        $catatan->fill($data);

        $baru = [];
        foreach (array_keys(self::KOLOM) as $kolom) {
            $baru[$kolom] = $this->teks($kolom, $catatan->{$kolom});
        }

        $berubah = array_values(array_filter(
            array_keys(self::KOLOM),
            fn ($kolom) => $baru[$kolom] !== $lama[$kolom]
        ));

        if ($berubah === []) {
            return redirect()->route('jadwal-bayar.show', $invoice)->with('info', 'tidak ada perubahan.');
        }

        $userId = $request->user()->id;

        DB::transaction(function () use ($catatan, $invoice, $userId, $lama, $baru, $berubah) {
            $catatan->user_id = $userId;
            $catatan->save();

            foreach ($berubah as $kolom) {
                RiwayatCatatan::create([
                    'catatan_dokumen_id' => $catatan->id,
                    'user_id' => $userId,
                    'kolom' => $kolom,
                    'nilai_lama' => $lama[$kolom],
                    'nilai_baru' => $baru[$kolom],
                ]);
            }

            LogAktivitas::create([
                'user_id' => $userId,
                'aksi' => 'ubah_catatan',
                'rincian' => 'invoice '.$invoice->no_invoice.': '
                    .implode(', ', array_map(fn ($k) => self::KOLOM[$k], $berubah)),
            ]);
        });

        return redirect()->route('jadwal-bayar.show', $invoice)->with('sukses', 'Catatan dokumen disimpan.');
    }

    /**BR-13: hanya invoice yang mvp-nya sudah diserahkan ke Keuangan. */
    private function pastikanDiJadwalBayar(Invoice $invoice): void
    {
        abort_unless($invoice->mvp?->tgl_serah_keuangan !== null, 404);
    }

    private function bawaan(string $kolom): mixed
    {
        if ($kolom === 'ttd_status') {
            return 'belum';
        }

        return str_ends_with($kolom, '_ada') ? false : null;
    }

    private function teks(string $kolom, mixed $nilai): ?string
    {
        if (str_ends_with($kolom, '_ada')) {
            return $nilai ? 'ada' : 'belum ada';
        }
        if ($kolom === 'ttd_tanggal') {
            return $nilai ? Carbon::parse($nilai)-> format('Y-m-d') : null;
        }

        return ($nilai === null || $nilai === '') ? null : (string) $nilai;
    }
}
