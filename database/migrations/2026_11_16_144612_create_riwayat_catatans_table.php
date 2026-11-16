<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('riwayat_catatans', function (Blueprint $table) {
            // $table->id();
            // $table->timestamps();
            $table->id();
            $table->foreignId('catatan_dokumen_id')->constrained('catatan_dokumens')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kolom');
            $table->string('nilai_lama')->nullable();
            $table->string('nilai_baru')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_catatans');
    }
};
