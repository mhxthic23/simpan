<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')
                ->constrained('kelas')
                ->restrictOnDelete();
            $table->foreignId('pengajar_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->dateTime('deadline_at');
            $table->string('status', 30);
            $table->dateTime('published_at')->nullable();
            $table->unsignedBigInteger('max_file_size')->nullable();
            $table->json('allowed_file_types')->nullable();
            $table->timestamps();

            $table->index(['kelas_id', 'deadline_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas');
    }
};
