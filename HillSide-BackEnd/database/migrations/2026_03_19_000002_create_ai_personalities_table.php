<?php

use App\Enums\AiResponseStyle;
use App\Enums\AiTone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_personalities', function (Blueprint $table) {
            $table->id();
            $table->enum('tone', array_column(AiTone::cases(), 'value'))->default(AiTone::PROFESSIONAL->value);
            $table->enum('response_style', array_column(AiResponseStyle::cases(), 'value'))->default(AiResponseStyle::BALANCED->value);
            $table->string('language')->default('en');
            $table->text('greeting_message')->nullable();
            $table->text('farewell_message')->nullable();
            $table->text('custom_instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_personalities');
    }
};
