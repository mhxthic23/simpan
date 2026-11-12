<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penilaian_id')
                ->constrained('penilaian')
                ->restrictOnDelete();
            $table->foreignId('siswa_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('alasan');
            $table->string('bukti_path', 1024)->nullable();
            $table->string('status', 30);
            $table->text('tanggapan_pengajar')->nullable();
            $table->text('keputusan')->nullable();
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->dateTime('submitted_at');
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->unique('penilaian_id');
            $table->index(['status', 'submitted_at']);
            $table->index(['siswa_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputasi');
    }
};
