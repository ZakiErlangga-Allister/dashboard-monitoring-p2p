<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lacak status PO</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <form method="GET" action="{{ route('lacak-status.index') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-600">Cari</label>
                    <input type="text" name="q" value="{{ $filter['q'] }}"
                           placeholder="Nomor PO, nomor PR, atau vendor"
                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Tahap yang sedang berjalan</label>
                    <select name="tahap" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        <option value="berjalan" @selected($filter['tahap'] === 'berjalan')>Semua yang berjalan</option>
                        @foreach ($daftarTahap as $nama)
                            <option value="{{ $nama }}" @selected($filter['tahap'] === $nama)>{{ $nama }}</option>
                        @endforeach
                        <option value="selesai" @selected($filter['tahap'] === 'selesai')>Selesai</option>
                        <option value="semua" @selected($filter['tahap'] === 'semua')>Semua</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Vendor</label>
                    <select name="vendor" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Semua vendor</option>
                        @foreach ($daftarVendor as $v)
                            <option value="{{ $v->id }}" @selected($filter['vendor'] === $v->id)>{{ $v->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Tanggal tahap terakhir dari</label>
                    <input type="date" name="dari" value="{{ $filter['dari'] }}"
                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Sampai</label>
                    <input type="date" name="sampai" value="{{ $filter['sampai'] }}"
                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div class="md:col-span-6 flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-sm text-gray-600">Urutkan</label>
                        <select name="urut" class="mt-1 border-gray-300 rounded-md shadow-sm">
                            <option value="terbaru" @selected($filter['urut'] === 'terbaru')>Aktivitas terbaru</option>
                            <option value="terlama" @selected($filter['urut'] === 'terlama')>Aktivitas terlama</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm">Terapkan</button>
                    <a href="{{ route('lacak-status.index') }}" class="px-4 py-2 border rounded-md text-sm">Atur ulang</a>
                </div>
            </form>

            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-3 text-sm text-red-800">Periksa kembali isian tanggal.</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-3">No. PR</th>
                            <th class="px-4 py-3">No. PO</th>
                            <th class="px-4 py-3">Vendor</th>
                            <th class="px-4 py-3">Sedang di tahap</th>
                            <th class="px-4 py-3">Tanggal tahap terakhir</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($paginasi as $b)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $b['pr']->no_pr }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $b['po']?->no_po ?? 'Belum terbit' }}</td>
                                <td class="px-4 py-3">{{ $b['vendor']?->nama ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @if ($b['selesai'])
                                        <span class="inline-block rounded px-2 py-0.5 text-xs bg-gray-100 text-gray-700">Selesai</span>
                                    @else
                                        <span class="inline-block rounded px-2 py-0.5 text-xs bg-blue-100 text-blue-800">{{ $b['saat_ini'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $b['terakhir']?->translatedFormat('d M Y') ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('lacak-status.show', $b['pr']) }}" class="text-blue-700 hover:underline">Lihat</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                    Tidak ada hasil yang cocok dengan pencarian atau filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $paginasi->links() }}

            <p class="text-xs text-gray-500">Data per {{ $dataPer ?? '-' }}. Data contoh, bukan data nyata.</p>
        </div>
    </div>
</x-app-layout>