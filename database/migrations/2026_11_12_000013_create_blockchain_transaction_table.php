<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blockchain_transaction', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50);

            // Polymorphic reference: the target depends on reference_type, so no direct FK is used.
            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id');

            $table->string('network', 50);
            $table->unsignedBigInteger('chain_id');
            $table->string('contract_address', 42)->nullable();
            $table->char('transaction_hash', 66)->nullable();

            // SHA-256 hexadecimal digest only; never store the unhashed payload here.
            $table->char('payload_hash', 64);

            $table->unsignedBigInteger('block_number')->nullable();
            $table->enum('status', ['pending', 'submitted', 'confirmed', 'failed'])
                ->default('pending');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('idempotency_key', 100)->unique();
            $table->text('error_message')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->timestamps();

            $table->unique(['network', 'transaction_hash']);
            $table->index(['event_type', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('submitted_at');
            $table->index('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blockchain_transaction');
    }
};
