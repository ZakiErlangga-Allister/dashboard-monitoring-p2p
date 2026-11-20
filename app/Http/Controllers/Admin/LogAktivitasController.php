<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\Request;

class LogAktivitasController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        $query = LogAktivitas::query()->with('user')->latest('id');

        if ($request->filled('aksi')) {
            $query->where('aksi', $request->input('aksi'));
        }
        if ($request->filled('user')) {
            $query->where('user_id', $request->integer('user'));
        }
        if ($request->filled('dari')) {
            $query->whereDate('created_at', '>=', $request->input('dari'));
        }
        if ($request->filled('sampai')) {
            $query->whereDate('created_at', '<=', $request->input('sampai'));
        }

        return view('admin.log.index', [
            'log' => $query->paginate(25)->withQueryString(),
            'daftarAksi' => LogAktivitas::query()->select('aksi')->distinct()->orderBy('aksi')->pluck('aksi'),
            'daftarUser' => User::orderBy('name')->get(['id', 'name']),
            'filter' => $request->only(['aksi', 'user', 'dari', 'sampai']),
        ]);
    }
}
