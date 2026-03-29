<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('platform_conversation_id');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->string('sender_type', 32)->nullable()->after('direction');
            $table->json('raw_payload')->nullable()->after('sender_type');
            $table->json('attachments')->nullable()->after('raw_payload');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['sender_type', 'raw_payload', 'attachments']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
