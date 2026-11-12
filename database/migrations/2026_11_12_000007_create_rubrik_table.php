<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubrik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_id')
                ->constrained('tugas')
                ->restrictOnDelete();
            $table->string('nama', 150);
            $table->text('deskripsi')->nullable();
            $table->decimal('bobot', 5, 2);
            $table->decimal('skor_maksimum', 7, 2);
            $table->unsignedSmallInteger('urutan');
            $table->timestamps();

            $table->index(['tugas_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubrik');
    }
};
