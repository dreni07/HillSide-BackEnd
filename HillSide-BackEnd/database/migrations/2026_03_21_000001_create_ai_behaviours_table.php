<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void
    {
        Schema::create('ai_behaviours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('orchestration_title')->default('Agent Orchestration Studio');
            $table->string('orchestration_subtitle')->nullable();

            $table->text('insight_banner_message')->nullable();

            $table->string('active_workflow_tab', 64)->nullable();
            $table->longText('flow_graph_json')->nullable();

            $table->string('selected_palette_item_id', 128)->nullable();
            $table->json('inspector_detail_json')->nullable();

            $table->text('personality_summary')->nullable();
            $table->text('customer_restriction_rules')->nullable();
            $table->text('sales_objectives')->nullable();

            $table->string('voice_tone', 64)->nullable();
            $table->string('implementation_method', 32)->nullable();

            $table->json('scenario_flags_json')->nullable();
            $table->json('goals_maintain_json')->nullable();
            $table->json('goals_minimize_json')->nullable();
            $table->text('constraints_notes')->nullable();

            $table->json('perception_modules_snapshot_json')->nullable();
            $table->json('selector_modules_snapshot_json')->nullable();
            $table->json('skill_modules_snapshot_json')->nullable();

            $table->boolean('is_published')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_behaviours');
    }
};
