<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaian_riwayat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penilaian_id')
                ->constrained('penilaian')
                ->restrictOnDelete();
            $table->foreignId('diubah_oleh')
                ->constrained('users')
                ->restrictOnDelete();
            $table->decimal('nilai_sebelumnya', 7, 2)->nullable();
            $table->decimal('nilai_baru', 7, 2);
            $table->text('alasan_perubahan')->nullable();
            $table->json('snapshot_data')->nullable();
            $table->timestamps();

            $table->index(['penilaian_id', 'created_at']);
            $table->index(['diubah_oleh', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian_riwayat');
    }
};
