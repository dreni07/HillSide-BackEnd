<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('viber_bot_id')->nullable()->after('meta_access_token');
            $table->string('webhook_verify_token')->nullable()->after('viber_bot_id');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['viber_bot_id', 'webhook_verify_token']);
        });
    }
};
