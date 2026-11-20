<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pengaturan</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('sukses'))
                <div class="rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('sukses') }}</div>
            @endif
            @if (session('info'))
                <div class="rounded-md bg-gray-100 p-3 text-sm text-gray-700">{{ session('info') }}</div>
            @endif
            @if ($acuan)
                <div class="rounded-md bg-yellow-50 p-3 text-sm text-yellow-800">
                    Tanggal acuan aktif: {{ $acuan }}. Semua status dihitung seolah-olah hari ini adalah tanggal itu.
                    Kosongkan kolom tanggal acuan untuk kembali ke tanggal sebenarnya.
                </div>
            @endif

            <form method="POST" action="{{ route('admin.pengaturan.update') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm text-gray-600">Ambang "mendekati jatuh tempo" (hari, 0 sampai 30)</label>
                    <input type="number" name="ambang_hari" min="0" max="30"
                           value="{{ old('ambang_hari', $ambang) }}"
                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                    @error('ambang_hari') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm text-gray-600">Tanggal acuan (untuk demo)</label>
                    <input type="date" name="tanggal_acuan"
                           value="{{ old('tanggal_acuan', $acuan) }}"
                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                    <p class="mt-1 text-xs text-gray-500">Kosong berarti memakai tanggal hari ini.</p>
                    @error('tanggal_acuan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm text-gray-600">Daftar bank (pisahkan dengan koma)</label>
                    <input type="text" name="daftar_bank" maxlength="200"
                           value="{{ old('daftar_bank', $bank) }}"
                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                    <p class="mt-1 text-xs text-gray-500">Dipakai untuk pilihan bank di Jadwal bayar. Maksimal 20 karakter per bank.</p>
                    @error('daftar_bank') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm text-gray-600">Data per</label>
                    <input type="text" value="{{ $dataPer ?? '-' }}" disabled
                           class="mt-1 w-full border-gray-200 bg-gray-50 rounded-md shadow-sm">
                    <p class="mt-1 text-xs text-gray-500">Berubah otomatis saat data diimpor.</p>
                </div>

                <div>
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>