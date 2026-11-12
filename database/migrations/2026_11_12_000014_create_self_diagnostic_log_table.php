<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_diagnostic_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengumpulan_tugas_id')
                ->constrained('pengumpulan_tugas')
                ->restrictOnDelete();
            $table->foreignId('siswa_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->decimal('similarity_score', 5, 2)->nullable();
            $table->decimal('ai_score', 5, 2)->nullable();
            $table->json('plagiarism_result')->nullable();
            $table->json('ai_result')->nullable();
            $table->string('status', 30);
            $table->dateTime('checked_at');
            $table->timestamps();

            $table->index(['siswa_id', 'status', 'checked_at']);
            $table->index(['pengumpulan_tugas_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_diagnostic_log');
    }
};
