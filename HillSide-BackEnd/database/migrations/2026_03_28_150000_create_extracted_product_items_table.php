<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extracted_product_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('sku', 128)->nullable();
            $table->string('category', 255)->nullable();
            $table->longText('extracted_text')->nullable();
            $table->string('source_type', 32);
            $table->json('metadata')->nullable();
            $table->string('file_disk', 32)->nullable();
            $table->string('file_path', 512)->nullable();
            $table->timestamps();

            $table->index(['business_id', 'source_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extracted_product_items');
    }
};
