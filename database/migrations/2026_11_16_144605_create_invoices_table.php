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
        Schema::create('invoices', function (Blueprint $table) {
            // $table->id();
            // $table->timestamps();
            $table->id();
            $table->string('no_invoice')->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->date('tgl_terima_kasir');
            $table->unsignedSmallInteger('tenor_hari')->default(14);
            $table->date('jatuh_tempo_sap');
            $table->date('jatuh_tempo_mitraku');
            $table->string('status_bg_sap')->nullable();
            $table->string('status_bg_mitraku')->nullable();
            $table->decimal('nilai', 15, 2)->default(0);
            $table->timestamps();
            $table->index('jatuh_tempo_sap');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
