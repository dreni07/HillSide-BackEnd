<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('keyword_responses');
        Schema::dropIfExists('automation_rules');
    }

    public function down(): void
    {
        // Ripërdor migrimet origjinale nëse duhet rikthimi manual.
    }
};
