<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Log aktivitas</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <form method="GET" action="{{ route('admin.log.index') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-4 grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                <div>
                    <label class="block text-sm text-gray-600">Aksi</label>
                    <select name="aksi" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Semua aksi</option>
                        @foreach ($daftarAksi as $a)
                            <option value="{{ $a }}" @selected(($filter['aksi'] ?? '') === $a)>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Pengguna</label>
                    <select name="user" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Semua pengguna</option>
                        @foreach ($daftarUser as $u)
                            <option value="{{ $u->id }}" @selected((string) ($filter['user'] ?? '') === (string) $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Dari tanggal</label>
                    <input type="date" name="dari" value="{{ $filter['dari'] ?? '' }}"
                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Sampai tanggal</label>
                    <input type="date" name="sampai" value="{{ $filter['sampai'] ?? '' }}"
                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm">Terapkan</button>
                    <a href="{{ route('admin.log.index') }}" class="px-4 py-2 border rounded-md text-sm">Atur ulang</a>
                </div>
            </form>

            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-3 text-sm text-red-800">Periksa kembali isian tanggal.</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Pengguna</th>
                            <th class="px-4 py-3">Aksi</th>
                            <th class="px-4 py-3">Rincian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($log as $l)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $l->created_at->translatedFormat('d M Y H:i') }}</td>
                                <td class="px-4 py-3">{{ $l->user?->name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block rounded px-2 py-0.5 text-xs {{ $l->aksi === 'login_gagal' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700' }}">{{ $l->aksi }}</span>
                                </td>
                                <td class="px-4 py-3">{{ $l->rincian }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">Tidak ada catatan yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $log->links() }}
        </div>
    </div>
</x-app-layout>