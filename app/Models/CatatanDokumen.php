<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;

class CatatanDokumen extends Model
{
    /** @use HasFactory<\Database\Factories\CatatanDokumenFactory> */
    use HasFactory;

    protected $guarded = [];

    #[Override]
    protected function casts()
    {
        return [
            'bg_ada' => 'boolean',
            'skf_ada' => 'boolean',
            'surat_pernyataan_ada' => 'boolean',
            'rekening_ada' => 'boolean',
            'ttd_tanggal' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(RiwayatCatatan::class);
    }
}
