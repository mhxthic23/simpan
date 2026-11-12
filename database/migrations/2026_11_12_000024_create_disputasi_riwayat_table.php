<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputasi_riwayat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disputasi_id')
                ->constrained('disputasi')
                ->restrictOnDelete();
            $table->foreignId('diubah_oleh')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('status_sebelumnya', 30)->nullable();
            $table->string('status_baru', 30);
            $table->text('tanggapan')->nullable();
            $table->text('keputusan')->nullable();
            $table->json('snapshot_data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['disputasi_id', 'created_at']);
            $table->index(['diubah_oleh', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputasi_riwayat');
    }
};
