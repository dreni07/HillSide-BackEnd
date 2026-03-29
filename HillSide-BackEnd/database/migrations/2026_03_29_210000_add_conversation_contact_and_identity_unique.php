<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('contact_id')->nullable()->after('user_id')->constrained('contacts')->nullOnDelete();
        });

        Schema::table('contact_identities', function (Blueprint $table) {
            $table->unique(['channel_id', 'platform_user_id'], 'contact_identities_channel_platform_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('contact_identities', function (Blueprint $table) {
            $table->dropUnique('contact_identities_channel_platform_user_unique');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_id');
        });
    }
};
