<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voting_option', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_dao_id')
                ->constrained('voting_dao')
                ->restrictOnDelete();
            $table->string('opsi');
            $table->text('deskripsi')->nullable();
            $table->unsignedSmallInteger('urutan');
            $table->timestamps();

            $table->unique(['voting_dao_id', 'urutan']);
            // Candidate key used by voting_log to enforce option/vote consistency.
            $table->unique(['voting_dao_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voting_option');
    }
};
