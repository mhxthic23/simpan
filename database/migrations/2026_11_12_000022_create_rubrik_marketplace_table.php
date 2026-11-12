<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubrik_marketplace', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajar_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('rubrik_id')
                ->nullable()
                ->constrained('rubrik')
                ->restrictOnDelete();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->string('kategori', 100)->nullable();
            $table->json('struktur_rubrik');
            $table->enum('status', ['draft', 'published', 'archived'])
                ->default('draft');
            $table->boolean('is_verified')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['kategori', 'status']);
            $table->index(['pengajar_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubrik_marketplace');
    }
};
