<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llm_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hashed_token')->unique();
            $table->json('abilities')->nullable();
            $table->integer('rate_limit')->default(60);
            $table->integer('monthly_quota')->default(1000000);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_tokens');
    }
};
