<?php

use App\Enums\SalesApproach;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_salesmen', function (Blueprint $table) {
            $table->id();
            $table->string('sales_approach')->default(SalesApproach::CONSULTATIVE->value);
            $table->boolean('upsell_enabled')->default(false);
            $table->text('product_knowledge')->nullable();
            $table->text('pricing_info')->nullable();
            $table->text('call_to_action')->nullable();
            $table->text('objection_handling')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_salesmen');
    }
};
