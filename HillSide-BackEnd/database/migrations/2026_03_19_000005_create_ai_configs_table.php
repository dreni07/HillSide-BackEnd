<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('ai_personality_id')->constrained('ai_personalities')->cascadeOnDelete();
            $table->foreignId('ai_restrictions_id')->constrained('ai_restrictions')->cascadeOnDelete();
            $table->foreignId('ai_salesman_id')->constrained('ai_salesmen')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_configs');
    }
};
