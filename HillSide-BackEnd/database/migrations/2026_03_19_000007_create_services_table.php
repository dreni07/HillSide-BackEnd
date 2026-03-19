<?php

use App\Enums\PriceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->enum('price_type', array_column(PriceType::cases(), 'value'))->default(PriceType::FIXED->value);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_available')->default(true);
            $table->json('attributes')->nullable();
            $table->text('extra_context')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
