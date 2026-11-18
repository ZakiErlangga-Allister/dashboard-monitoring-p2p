<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Invoice {{ $invoice->no_invoice }}</h2>
    </x-slot>

    @php
        $warnaStatus = [
            'Siap bayar' => 'bg-green-100 text-green-800',
            'Menunggu tanda tangan' => 'bg-blue-100 text-blue-800',
            'Tertahan' => 'bg-yellow-100 text-yellow-800',
            'Dibayar' => 'bg-gray-100 text-gray-700',
        ];
        $warnaLabel = [
            'Terlambat' => 'bg-red-100 text-red-800',
            'Mendekati jatuh tempo' => 'bg-orange-100 text-orange-800',
            'Selisih data' => 'bg-purple-100 text-purple-800',
        ];
        $butir = [
            'bg_ada' => 'Bank garansi',
            'skf_ada' => 'SKF',
            'surat_pernyataan_ada' => 'Surat pernyataan',
            'rekening_ada' => 'Rekening penerima',
        ];
        $selisihTanggal = ! $invoice->jatuh_tempo_sap->isSameDay($invoice->jatuh_tempo_mitraku);
        $selisihBg = strtolower(trim((string) $invoice->status_bg_sap)) !== strtolower(trim((string) $invoice->status_bg_mitraku));
    @endphp

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('jadwal-bayar.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Kembali ke Jadwal bayar</a>

            @if (session('sukses'))
                <div class="rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('sukses') }}</div>
            @endif
            @if (session('info'))
                <div class="rounded-md bg-gray-100 p-3 text-sm text-gray-700">{{ session('info') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-3 text-sm text-red-800">Periksa kembali isian di bawah.</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div><dt class="text-gray-500">Vendor</dt><dd>{{ $vendor->nama }}</dd></div>
                    <div><dt class="text-gray-500">Bank penerima</dt><dd>{{ $vendor->bank_penerima }}</dd></div>
                    <div><dt class="text-gray-500">Nomor MVP</dt><dd>{{ $invoice->mvp->no_mvp }}</dd></div>
                    <div><dt class="text-gray-500">Nilai</dt><dd>Rp {{ number_format($invoice->nilai, 0, ',', '.') }}</dd></div>
                    <div>
                        <dt class="text-gray-500">Diterima kasir</dt>
                        <dd>{{ $invoice->tgl_terima_kasir->translatedFormat('d M Y') }} (tenor {{ $invoice->tenor_hari }} hari)</dd>
                    </div>
                    <div><dt class="text-gray-500">Jatuh tempo SAP</dt><dd>{{ $invoice->jatuh_tempo_sap->translatedFormat('d M Y') }}</dd></div>
                    <div>
                        <dt class="text-gray-500">Jatuh tempo Mitraku</dt>
                        <dd class="{{ $selisihTanggal ? 'text-purple-700 font-medium' : '' }}">
                            {{ $invoice->jatuh_tempo_mitraku->translatedFormat('d M Y') }}
                        </dd>
                    </div>
                    <div><dt class="text-gray-500">Bank garansi di SAP</dt><dd>{{ $invoice->status_bg_sap ?? '-' }}</dd></div>
                    <div>
                        <dt class="text-gray-500">Bank garansi di Mitraku</dt>
                        <dd class="{{ $selisihBg ? 'text-purple-700 font-medium' : '' }}">{{ $invoice->status_bg_mitraku ?? '-' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Status</dt>
                        <dd class="space-y-1">
                            <span class="inline-block rounded px-2 py-0.5 text-xs {{ $warnaStatus[$hasil['status']] }}">{{ $hasil['status'] }}</span>
                            @foreach ($hasil['label'] as $label)
                                <span class="inline-block rounded px-2 py-0.5 text-xs {{ $warnaLabel[$label] }}">{{ $label }}</span>
                            @endforeach
                            <div class="text-xs text-gray-500">
                                Kelengkapan {{ $hasil['kelengkapan'] }} dari 4
                                @if ($hasil['status'] === 'Tertahan')
                                    . Belum ada: {{ implode(', ', $hasil['kurang']) }}
                                @endif
                            </div>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Catatan dokumen</h3>

                @unless ($bolehUbah)
                    <p class="text-sm text-gray-500 mb-4">Hanya Staf Keuangan yang dapat mengubah catatan. Anda hanya dapat melihat.</p>
                @endunless

                <form method="POST" action="{{ route('jadwal-bayar.catatan.update', $invoice) }}">
                    @csrf
                    @method('PUT')

                    <fieldset @disabled(! $bolehUbah) class="space-y-5">
                        <div>
                            <div class="text-sm text-gray-600 mb-2">Kelengkapan dokumen</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach ($butir as $nama => $label)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="{{ $nama }}" value="1"
                                               class="rounded border-gray-300"
                                               @checked(old($nama, $catatan->{$nama}))>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600">Tanda tangan otorisator</label>
                                @php $ttd = old('ttd_status', $catatan->ttd_status ?? 'belum'); @endphp
                                <select name="ttd_status" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="belum" @selected($ttd === 'belum')>Belum</option>
                                    <option value="sudah" @selected($ttd === 'sudah')>Sudah</option>
                                </select>
                                @error('ttd_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600">Tanggal tanda tangan</label>
                                <input type="date" name="ttd_tanggal"
                                       value="{{ old('ttd_tanggal', $catatan->ttd_tanggal?->format('Y-m-d')) }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                @error('ttd_tanggal') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600">Lokasi arsip</label>
                            <input type="text" name="lokasi_arsip" maxlength="50"
                                   value="{{ old('lokasi_arsip', $catatan->lokasi_arsip) }}"
                                   placeholder="Contoh: Lemari 17-A"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            @error('lokasi_arsip') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </fieldset>

                    @if ($bolehUbah)
                        <div class="mt-5">
                            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm">Simpan</button>
                        </div>
                    @endif
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Riwayat perubahan</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm divide-y divide-gray-200">
                        <thead class="text-left text-gray-600">
                            <tr>
                                <th class="py-2 pr-4">Waktu</th>
                                <th class="py-2 pr-4">Kolom</th>
                                <th class="py-2 pr-4">Perubahan</th>
                                <th class="py-2">Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($riwayat as $r)
                                <tr>
                                    <td class="py-2 pr-4 whitespace-nowrap">{{ $r->created_at->translatedFormat('d M Y H:i') }}</td>
                                    <td class="py-2 pr-4">{{ $namaKolom[$r->kolom] ?? $r->kolom }}</td>
                                    <td class="py-2 pr-4">{{ $r->nilai_lama ?? '-' }} &rarr; {{ $r->nilai_baru ?? '-' }}</td>
                                    <td class="py-2">{{ $r->user?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-gray-500">Belum ada perubahan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>