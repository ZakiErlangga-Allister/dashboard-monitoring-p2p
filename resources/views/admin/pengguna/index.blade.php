<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kelola pengguna</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('sukses'))
                <div class="rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('sukses') }}</div>
            @endif
            @if (session('info'))
                <div class="rounded-md bg-gray-100 p-3 text-sm text-gray-700">{{ session('info') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-3 text-sm text-red-800">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.pengguna.store') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-4 grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-sm text-gray-600">Nama</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Kata sandi (min. 8 karakter)</label>
                    <input type="password" name="password" autocomplete="new-password"
                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Peran</label>
                    <select name="peran" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        @foreach ($daftarPeran as $nilai => $nama)
                            <option value="{{ $nilai }}" @selected(old('peran') === $nilai)>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm">Tambah pengguna</button>
                </div>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Ubah peran, status, atau kata sandi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($pengguna as $u)
                            <tr>
                                <td class="px-4 py-3">{{ $u->name }}</td>
                                <td class="px-4 py-3">{{ $u->email }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block rounded px-2 py-0.5 text-xs {{ $u->aktif ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $u->aktif ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                    <div class="text-xs text-gray-500">{{ $daftarPeran[$u->peran] ?? $u->peran }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.pengguna.update', $u) }}"
                                          class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="peran" class="border-gray-300 rounded-md shadow-sm text-sm">
                                            @foreach ($daftarPeran as $nilai => $nama)
                                                <option value="{{ $nilai }}" @selected($u->peran === $nilai)>{{ $nama }}</option>
                                            @endforeach
                                        </select>
                                        <select name="aktif" class="border-gray-300 rounded-md shadow-sm text-sm">
                                            <option value="1" @selected($u->aktif)>Aktif</option>
                                            <option value="0" @selected(! $u->aktif)>Nonaktif</option>
                                        </select>
                                        <input type="password" name="password" autocomplete="new-password"
                                               placeholder="Kata sandi baru (opsional)"
                                               class="border-gray-300 rounded-md shadow-sm text-sm">
                                        <button type="submit" class="px-3 py-1.5 bg-gray-800 text-white rounded-md text-sm">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>