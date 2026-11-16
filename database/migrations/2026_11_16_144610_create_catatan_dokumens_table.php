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
        Schema::create('catatan_dokumens', function (Blueprint $table) {
            // $table->id();
            // $table->timestamps();
            $table->id();
            $table->foreignId('invoice_id')->unique()->constrained('invoices');
            $table->boolean('bg_ada')->default(false);
            $table->boolean('skf_ada')->default(false);
            $table->boolean('surat_pernyataan_ada')->default(false);
            $table->boolean('rekening_ada')->default(false);
            $table->string('ttd_status', 10)->default('belum');
            $table->date('ttd_tanggal')->nullable();
            $table->string('lokasi_arsip', 50)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catatan_dokumens');
    }
};
