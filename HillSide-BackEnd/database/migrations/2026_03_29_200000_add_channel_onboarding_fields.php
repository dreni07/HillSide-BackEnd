<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('whatsapp_business_account_id')->nullable()->after('whatsapp_phone_number_id');
            $table->string('whatsapp_display_phone_number')->nullable()->after('whatsapp_business_account_id');
            $table->timestamp('meta_token_expires_at')->nullable()->after('meta_access_token');
            $table->timestamp('viber_webhook_registered_at')->nullable()->after('webhook_verify_token');
            $table->text('connection_error')->nullable()->after('viber_webhook_registered_at');
            $table->timestamp('connection_error_at')->nullable()->after('connection_error');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_business_account_id',
                'whatsapp_display_phone_number',
                'meta_token_expires_at',
                'viber_webhook_registered_at',
                'connection_error',
                'connection_error_at',
            ]);
        });
    }
};
