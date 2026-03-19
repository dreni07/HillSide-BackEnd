<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_restrictions', function (Blueprint $table) {
            $table->id();
            $table->json('allowed_topics')->nullable();
            $table->json('restricted_topics')->nullable();
            $table->json('blocked_words')->nullable();
            $table->unsignedInteger('max_response_length')->nullable();
            $table->text('content_guidelines')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_restrictions');
    }
};
