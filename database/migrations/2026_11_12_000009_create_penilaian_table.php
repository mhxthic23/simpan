<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengumpulan_tugas_id')
                ->constrained('pengumpulan_tugas')
                ->restrictOnDelete();
            $table->foreignId('pengajar_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('status', 30);
            $table->decimal('nilai_total', 7, 2);
            $table->text('feedback')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->unique('pengumpulan_tugas_id');
            $table->index(['pengajar_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian');
    }
};
