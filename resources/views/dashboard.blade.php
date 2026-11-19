<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Beranda</h2>
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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <section>
                <h3 class="text-sm font-semibold text-gray-600 mb-3">PO yang sedang berjalan, per tahap</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    @foreach ($kartuTahap as $kartu)
                        <a href="{{ route('lacak-status.index', ['tahap' => $kartu['nama']]) }}"
                           class="bg-white shadow-sm sm:rounded-lg p-4 hover:ring-2 hover:ring-blue-300">
                            <div class="text-sm text-gray-500">{{ $kartu['nama'] }}</div>
                            <div class="text-2xl font-semibold">{{ $kartu['jumlah'] }}</div>
                        </a>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-gray-500">
                    Nama tahap adalah tahap yang sedang berjalan. Contoh: "PO terbit" berarti PR sudah disetujui dan PO belum terbit.
                </p>
            </section>

            @if ($ringkasan)
                <section>
                    <h3 class="text-sm font-semibold text-gray-600 mb-3">Jadwal pembayaran</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a href="{{ route('jadwal-bayar.index', ['dari' => $tanggal['hari_ini'], 'sampai' => $tanggal['hari_ini']]) }}"
                           class="bg-white shadow-sm sm:rounded-lg p-4 hover:ring-2 hover:ring-blue-300">
                            <div class="text-sm text-gray-500">Jatuh tempo hari ini</div>
                            <div class="text-2xl font-semibold">{{ $ringkasan['hari_ini'] }}</div>
                        </a>
                        <a href="{{ route('jadwal-bayar.index', ['dari' => $tanggal['hari_ini'], 'sampai' => $tanggal['tujuh_hari']]) }}"
                           class="bg-white shadow-sm sm:rounded-lg p-4 hover:ring-2 hover:ring-blue-300">
                            <div class="text-sm text-gray-500">Dalam 7 hari (termasuk hari ini)</div>
                            <div class="text-2xl font-semibold">{{ $ringkasan['tujuh_hari'] }}</div>
                        </a>
                        <a href="{{ route('jadwal-bayar.index', ['sampai' => $tanggal['kemarin']]) }}"
                           class="bg-white shadow-sm sm:rounded-lg p-4 hover:ring-2 hover:ring-blue-300">
                            <div class="text-sm text-gray-500">Terlambat</div>
                            <div class="text-2xl font-semibold text-red-700">{{ $ringkasan['terlambat'] }}</div>
                        </a>
                        <div class="bg-white shadow-sm sm:rounded-lg p-4">
                            <div class="text-sm text-gray-500">Peringatan selisih data</div>
                            <div class="text-2xl font-semibold text-purple-700">{{ $ringkasan['peringatan'] }}</div>
                            <div class="text-xs text-gray-400">Ditandai ungu di Jadwal bayar</div>
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="text-sm font-semibold text-gray-600 mb-3">Perlu tindakan hari ini</h3>
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-gray-600">
                                <tr>
                                    <th class="px-4 py-3">Jatuh tempo</th>
                                    <th class="px-4 py-3">Vendor dan MVP</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($perluTindakan as $b)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $b['invoice']->jatuh_tempo_sap->translatedFormat('d M Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $b['invoice']->purchaseOrder->vendor->nama }}
                                            <div class="text-xs text-gray-500">{{ $b['invoice']->mvp->no_mvp }}</div>
                                        </td>
                                        <td class="px-4 py-3 space-y-1">
                                            <span class="inline-block rounded px-2 py-0.5 text-xs {{ $warnaStatus[$b['hasil']['status']] }}">{{ $b['hasil']['status'] }}</span>
                                            @foreach ($b['hasil']['label'] as $label)
                                                <span class="inline-block rounded px-2 py-0.5 text-xs {{ $warnaLabel[$label] }}">{{ $label }}</span>
                                            @endforeach
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('jadwal-bayar.show', $b['invoice']) }}" class="text-blue-700 hover:underline">Buka</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                            Tidak ada invoice yang terlambat atau jatuh tempo hari ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            <p class="text-xs text-gray-500">Data contoh, bukan data nyata.</p>
        </div>
    </div>
</x-app-layout>