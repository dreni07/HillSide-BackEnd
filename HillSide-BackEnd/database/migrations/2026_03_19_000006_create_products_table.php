<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('sku')->nullable();
            $table->string('category')->nullable();
            $table->integer('stock_quantity')->nullable();
            $table->boolean('is_available')->default(true);
            $table->json('images')->nullable();
            $table->json('attributes')->nullable();
            $table->text('extra_context')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
