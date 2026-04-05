<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('whatsapp_phone_number_id')->nullable()->after('meta_access_token');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->string('platform_conversation_id')->nullable()->after('status');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->unique(['channel_id', 'platform_conversation_id'], 'conversations_channel_platform_conv_unique');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->string('platform_message_id')->nullable()->after('conversation_id');
            $table->string('direction', 8)->nullable()->after('is_from_user');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->unique(['conversation_id', 'platform_message_id'], 'messages_conv_platform_mid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropUnique('messages_conv_platform_mid_unique');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['platform_message_id', 'direction']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique('conversations_channel_platform_conv_unique');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('platform_conversation_id');
        });

        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('whatsapp_phone_number_id');
        });
    }
};
