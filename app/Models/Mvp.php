<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;

class Mvp extends Model
{
    /** @use HasFactory<\Database\Factories\MvpFactory> */
    use HasFactory;

    protected $guarded = [];

    #[Override]
    protected function casts()
    {
        return [
            'tgl_terbit' => 'date',
            'tgl_disetujui' => 'date',
            'tgl_serah_keuangan' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
