<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Jadwal bayar</h2>
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
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500">Jatuh tempo hari ini</div>
                    <div class="text-2xl font-semibold">{{ $ringkasan['hari_ini'] }} invoice</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500">Siap bayar</div>
                    <div class="text-2xl font-semibold">{{ $ringkasan['siap_bayar'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500">Perlu tindakan</div>
                    <div class="text-2xl font-semibold">{{ $ringkasan['perlu_tindakan'] }}</div>
                </div>
            </div>

            <form method="GET" action="{{ route('jadwal-bayar.index') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                <div>
                    <label class="block text-sm text-gray-600">Jatuh tempo dari</label>
                    <input type="date" name="dari" value="{{ $filter['dari'] }}"
                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Sampai</label>
                    <input type="date" name="sampai" value="{{ $filter['sampai'] }}"
                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Bank</label>
                    <select name="bank" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Semua bank</option>
                        @foreach ($daftarBank as $b)
                            <option value="{{ $b }}" @selected($filter['bank'] === $b)>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Status</label>
                    <select name="status" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        <option value="belum_dibayar" @selected($filter['status'] === 'belum_dibayar')>Belum dibayar</option>
                        @foreach ($daftarStatus as $s)
                            <option value="{{ $s }}" @selected($filter['status'] === $s)>{{ $s }}</option>
                        @endforeach
                        <option value="semua" @selected($filter['status'] === 'semua')>Semua</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Vendor</label>
                    <input type="text" name="vendor" value="{{ $filter['vendor'] }}" placeholder="Nama vendor"
                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm">Terapkan</button>
                    <a href="{{ route('jadwal-bayar.index') }}" class="px-4 py-2 border rounded-md text-sm">Atur ulang</a>
                </div>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Jatuh tempo</th>
                            <th class="px-4 py-3">Vendor dan MVP</th>
                            <th class="px-4 py-3">Bank</th>
                            <th class="px-4 py-3">Kelengkapan</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Lokasi arsip</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($baris as $b)
                            @php
                                $inv = $b['invoice'];
                                $h = $b['hasil'];
                            @endphp
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $inv->jatuh_tempo_sap->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('jadwal-bayar.show', $inv) }}" class="text-blue-700 hover:underline">{{ $b['vendor']->nama }}</a>
                                    <div class="text-xs text-gray-500">{{ $inv->mvp->no_mvp }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $b['vendor']->bank_penerima }}</td>
                                <td class="px-4 py-3">{{ $h['kelengkapan'] }} dari 4</td>
                                <td class="px-4 py-3 space-y-1">
                                    <span class="inline-block rounded px-2 py-0.5 text-xs {{ $warnaStatus[$h['status']] }}">{{ $h['status'] }}</span>
                                    @if ($h['status'] === 'Tertahan')
                                        <div class="text-xs text-gray-500">Belum ada: {{ implode(', ', $h['kurang']) }}</div>
                                    @endif
                                    @foreach ($h['label'] as $label)
                                        <span class="inline-block rounded px-2 py-0.5 text-xs {{ $warnaLabel[$label] }}">{{ $label }}</span>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3">{{ $inv->catatanDokumen?->lokasi_arsip ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                    Tidak ada invoice yang cocok dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-gray-500">Data per {{ $dataPer ?? '-' }}. Data contoh, bukan data nyata.</p>
        </div>
    </div>
</x-app-layout>