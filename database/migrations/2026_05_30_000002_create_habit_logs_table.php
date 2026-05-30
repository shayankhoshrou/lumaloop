<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('habit_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->timestampTz('logged_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['habit_id', 'log_date']);
            $table->index(['user_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_logs');
    }
};

