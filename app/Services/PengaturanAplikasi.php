<?php

namespace App\Services;

use App\Models\Pengaturan;
use DateTimeImmutable;
use Throwable;

/** Membaca pengaturan dari tabel pengaturans (ambang hari dan tanggal acuan). */
final class PengaturanAplikasi
{
    public static function ambangHari(): int
    {
        $nilai = Pengaturan::find('ambang_hari')?->nilai;

        return is_numeric($nilai) ? max(0, (int) $nilai) : 3;
    }

    /** Tanggal yang dipakai sebagai "hari ini". Kosong = tanggal sebenarnya hari ini. */
    public static function tanggalAcuan(): DateTimeImmutable
    {
        $nilai = Pengaturan::find('tanggal_acuan')?->nilai;

        if ($nilai) {
            try {
                return new DateTimeImmutable($nilai);
            } catch (Throwable) {
                // nilai tidak valid, pakai hari ini
            }
        }

        return new DateTimeImmutable('today');
    }
}
