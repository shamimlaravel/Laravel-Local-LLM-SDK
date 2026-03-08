<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llm_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->constrained('llm_tokens')->onDelete('cascade');
            $table->string('driver');
            $table->string('model');
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->float('latency_ms')->default(0);
            $table->timestamps();

            $table->index(['token_id', 'created_at']);
            $table->index(['driver', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_usages');
    }
};
