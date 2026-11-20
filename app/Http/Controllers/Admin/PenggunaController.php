<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\Concerns\Has;
use Illuminate\Validation\Rule;

class PenggunaController extends Controller
{
    private const PERAN = [
        'pengguna_umum' => 'Pengguna umum',
        'staf_keuangan' => 'Staf Keuangan',
        'admin' => 'Admin',
    ];

    public function index()
    {
        return view('admin.pengguna.index', [
            'pengguna' => User::orderBy('name')->get(),
            'daftarPeran' => self::PERAN,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'peran' => ['required', Rule::in(array_keys(self::PERAN))],
        ], $this->pesan());

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'peran' => $data['peran'],
            'aktif' => true,
        ]);

        LogAktivitas::create([
            'user_id' => $request->user()->id,
            'aksi' => 'tambah_pengguna',
            'rincian' => $user->email.' ('.self::PERAN[$user->peran]. ')',
        ]);

        return redirect()->route('admin.pengguna.index')->with('sukses', 'Pengguna '.$user->email.' ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'peran' => ['required', Rule::in(array_keys(self::PERAN))],
            'aktif' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ], $this->pesan());

        $aktif = (bool) $data['aktif'];
        $tetapAdminAktif = $data['peran'] === 'admin' && $aktif;

        // Admin tidak boleh mengunci dirinya sendiri
        if ($user->is($request->user()) && ! $tetapAdminAktif) {
            return back()->withErrors([
                'peran' => 'Anda tidak dapat menurunkan peran atau menonaktifkan akun anda sendiri.',
            ]);
        }

        $perubahan = [];
        if  ($user->peran !== $data['peran']) {
            $perubahan[] = 'peran '.(self::PERAN[$user->peran] ?? $user->peran).' menjadi '.self::PERAN[$data['peran']];
        }
        if  ((bool) $user->aktif !== $aktif) {
            $perubahan[] = $aktif ? 'diaktifkan' : 'dinonaktifkan';
        }
        if (filled($data['password'] ?? null)) {
            $perubahan[] = 'kata sandi diatur ulang';
        }

        if ($perubahan === []) {
            return redirect()->route('admin.pengguna.index')->with('info', 'Tidak ada perubahan.');
        }

        $user->peran = $data['peran'];
        $user->aktif = $aktif;
        if (filled($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        LogAktivitas::create([
            'user_id' => $request->user()->id,
            'aksi' => 'ubah_pengguna',
            'rincian' => $user->email.': '.implode(', ', $perubahan),
        ]);

        return redirect()->route('admin.pengguna.index')->with('sukses', 'perubahan disimpan untuk '.$user->email.'.');
    }

    private function pesan(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah dipakai pengguna lain.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'peran.required' => 'Peran wajib dipilih.',
            'peran.in' => 'Peran tidak valid.',
            'aktif.required' => 'Status akun wajib dipilih.',
            'aktif.boolean' => 'Status akun tidak valid.',
        ];
    }
}
