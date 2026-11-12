<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurusan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fakultas_id')
                ->constrained('fakultas')
                ->restrictOnDelete();
            $table->string('nama', 150);
            $table->timestamps();

            $table->unique(['fakultas_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurusan');
    }
};
