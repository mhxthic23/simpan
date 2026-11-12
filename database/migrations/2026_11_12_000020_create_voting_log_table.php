<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voting_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_dao_id')
                ->constrained('voting_dao')
                ->restrictOnDelete();
            $table->foreignId('voting_option_id');
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->dateTime('voted_at');
            $table->timestamps();

            $table->unique(['voting_dao_id', 'user_id']);
            $table->index(['voting_dao_id', 'voting_option_id']);
            $table->index(['user_id', 'voted_at']);

            // Composite FK guarantees the selected option belongs to this voting.
            $table->foreign(
                ['voting_dao_id', 'voting_option_id'],
                'voting_log_option_matches_voting_foreign'
            )
                ->references(['voting_dao_id', 'id'])
                ->on('voting_option')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voting_log');
    }
};
