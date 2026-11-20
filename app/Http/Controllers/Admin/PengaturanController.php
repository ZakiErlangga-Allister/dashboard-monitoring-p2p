<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\Pengaturan;
use Illuminate\Http\Request;

class PengaturanController extends Controller
{
    public function edit()
    {
        return view('admin.pengaturan.edit', [
            'ambang' => Pengaturan::find('ambang_hari')?->nilai ?? '3',
            'acuan' => Pengaturan::find('tanggal_acuan')?->nilai,
            'bank' => Pengaturan::find('daftar_bank')?->nilai ?? 'BRI,Mandiri,BNI,BCA',
            "dataPer" => Pengaturan::find('data_per')?->nilai,
        ]);
    }

    public function update (Request $request)
    {
        $data = $request->validate([
            'ambang_hari' => ['required', 'integer', 'min:0', 'max:30'],
            'tanggal_acuan' => ['nullable', 'date'],
            'daftar_bank' => ['required', 'string', 'max:200'],
        ], [
            'ambang_hari.required' => 'Ambang hari wajib diisi.',
            'ambang_hari.integer' => 'Ambang hari harus berupa angka bulat.',
            'ambang_hari.min' => 'Ambang hari minimal 0.',
            'ambang_hari.max' => 'Ambang hari maksimal 30.',
            'tanggal_acuan.date' => 'Tanggal acuan tidak valid.',
            'daftar_bank.required' => 'Daftar bank wajib diisi.',
            'daftar_bank.max' => 'Daftar bank maksimal 200 karakter.',
        ]);

        $bank = collect(explode(',', $data['daftar_bank']))
            ->map(fn ($b) => trim($b))
            ->filter()
            ->unique()
            ->values();

        if ($bank->isEmpty()) {
            return back()->withErrors(['daftar_bank' => 'Isi minimal satu bank.'])->withInput();
        }
        if ($bank->contains(fn ($b) => mb_strlen($b) > 20)) {
            return back()->withErrors(['daftar_bank' => 'Nama bank maksimal 20 karakter.'])->withInput();
        }

        $baru = [
            'ambang_hari' => (string) $data['ambang_hari'],
            'tanggal_acuan' => $data['tanggal_acuan'] ?: null,
            'daftar_bank' => $bank->implode(','),
        ];

        $berubah = [];
        foreach ($baru as $kunci => $nilai) {
            if (Pengaturan::find($kunci)?->nilai !== $nilai) {
                $berubah[] = $kunci;
            }
        }

        if ($berubah === []) {
            return redirect()->route('admin.pengaturan.edit')->with('info', 'Tidak ada perubahan');
        }

        foreach ($baru as $kunci =>$nilai) {
            Pengaturan::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        }

        LogAktivitas::create([
            'user_id' => $request->user()->id,
            'aksi' => 'ubah_pengaturan',
            'rincian' => 'Diubah: '.implode(', ', $berubah),
        ]);

        return redirect()->route('admin.pengaturan.edit')->with('sukses', 'Pengaturan disimpan.');
    }
}
