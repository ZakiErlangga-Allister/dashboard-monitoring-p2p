<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pengaturan extends Model
{
    /** @use HasFactory<\Database\Factories\PengaturanFactory> */
    use HasFactory;

    protected $guarded = [];
    protected $primaryKey = 'kunci';
    public $incrementing = false;
    protected $keyType = 'string';
}
