<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('metric_type', 64);
            $table->json('value');
            $table->timestampTz('start_time');
            $table->timestampTz('end_time')->nullable();
            $table->string('source_app')->default('com.sec.android.app.shealth');
            $table->string('source_record_id')->nullable();
            $table->string('sync_key', 64);
            $table->timestamps();

            $table->unique(['user_id', 'sync_key']);
            $table->index(['user_id', 'metric_type', 'start_time']);
            $table->index(['source_app', 'source_record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_metrics');
    }
};

