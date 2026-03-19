<?php

use App\Enums\BusinessCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('business_types', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('business_type_id')
                ->nullable()
                ->after('description')
                ->constrained('business_types')
                ->nullOnDelete();
            $table->string('phone')->nullable()->after('business_type_id');
            $table->string('email')->nullable()->after('phone');
            $table->string('address')->nullable()->after('email');
            $table->string('logo')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_type_id');
            $table->dropColumn(['phone', 'email', 'address', 'logo']);
        });

        Schema::dropIfExists('business_types');
    }
};
