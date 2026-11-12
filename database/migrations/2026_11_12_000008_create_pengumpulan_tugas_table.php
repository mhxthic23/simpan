<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumpulan_tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_id')
                ->constrained('tugas')
                ->restrictOnDelete();
            $table->foreignId('siswa_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('attempt_no');
            $table->dateTime('submitted_at')->nullable();
            $table->string('status', 30);
            $table->string('original_filename')->nullable();
            $table->string('stored_path', 1024)->nullable();
            $table->string('mime_type', 127)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('ipfs_cid', 255)->nullable();
            $table->string('file_hash', 128)->nullable();
            $table->decimal('similarity_score', 5, 2)->nullable();
            $table->decimal('ai_score', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['tugas_id', 'siswa_id', 'attempt_no']);
            $table->index(['siswa_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumpulan_tugas');
    }
};
