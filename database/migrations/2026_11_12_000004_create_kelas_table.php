<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurusan_id')
                ->constrained('jurusan')
                ->restrictOnDelete();
            $table->foreignId('pengajar_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('nama', 150);
            $table->timestamps();

            $table->index(['pengajar_id', 'jurusan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
