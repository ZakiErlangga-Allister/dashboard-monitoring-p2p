<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail status</h2>
    </x-slot>

    @php
        $warna = [
            'Selesai' => 'bg-green-100 text-green-800',
            'Berjalan' => 'bg-blue-100 text-blue-800',
            'Menunggu' => 'bg-gray-100 text-gray-600',
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('lacak-status.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Kembali ke daftar</a>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    @foreach ($ringkasan as $nama => $nilai)
                        <div>
                            <dt class="text-gray-500">{{ $nama }}</dt>
                            <dd>{{ $nilai }}</dd>
                        </div>
                    @endforeach
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Posisi saat ini</dt>
                        <dd>{{ $selesai ? 'Seluruh tahap selesai' : 'Sedang di tahap '.$saatIni }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Garis waktu</h3>
                <ol class="space-y-3">
                    @foreach ($garis as $t)
                        <li class="rounded-lg border p-4 {{ $t['status'] === 'Berjalan' ? 'border-blue-400 bg-blue-50' : 'border-gray-200' }}">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="font-medium">{{ $t['no'] }}. {{ $t['nama'] }}</div>
                                <span class="inline-block rounded px-2 py-0.5 text-xs {{ $warna[$t['status']] }}">{{ $t['status'] }}</span>
                            </div>
                            <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm text-gray-600">
                                <div><span class="text-gray-400">Tanggal:</span> {{ $t['tanggal'] ?? '-' }}</div>
                                <div><span class="text-gray-400">Dokumen:</span> {{ $t['dokumen'] ?? '-' }}</div>
                                <div><span class="text-gray-400">Sumber data:</span> {{ $t['sumber'] }}</div>
                            </div>
                        </li>
                    @endforeach
                </ol>

                @if ($bisaKeJadwalBayar)
                    <div class="mt-4">
                        <a href="{{ route('jadwal-bayar.show', $invoice) }}" class="text-sm text-blue-700 hover:underline">
                            Buka di Jadwal bayar &rarr;
                        </a>
                    </div>
                @endif
            </div>

            <p class="text-xs text-gray-500">Data per {{ $dataPer ?? '-' }}. Data contoh, bukan data nyata.</p>
        </div>
    </div>
</x-app-layout>