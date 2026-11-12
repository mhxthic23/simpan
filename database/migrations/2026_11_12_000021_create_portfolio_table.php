<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('pengumpulan_tugas_id')
                ->nullable()
                ->constrained('pengumpulan_tugas')
                ->restrictOnDelete();
            $table->foreignId('penilaian_id')
                ->nullable()
                ->constrained('penilaian')
                ->restrictOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('file_path', 1024)->nullable();
            $table->string('ipfs_cid', 255)->nullable();
            $table->boolean('is_public')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->index(['siswa_id', 'is_public']);
            $table->index('pengumpulan_tugas_id');
            $table->index('penilaian_id');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio');
    }
};
