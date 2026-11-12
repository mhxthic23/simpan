<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voting_dao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')
                ->constrained('kelas')
                ->restrictOnDelete();
            $table->foreignId('penilaian_id')
                ->nullable()
                ->constrained('penilaian')
                ->restrictOnDelete();
            $table->foreignId('dibuat_oleh')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('jenis_voting', 50);
            $table->enum('status', ['draft', 'active', 'closed', 'cancelled'])
                ->default('draft');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();

            $table->index(['kelas_id', 'status', 'starts_at']);
            $table->index(['penilaian_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voting_dao');
    }
};
