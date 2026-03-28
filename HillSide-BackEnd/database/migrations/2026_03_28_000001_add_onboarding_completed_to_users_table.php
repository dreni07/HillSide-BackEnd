<?php

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('onboarding_completed')->default(false)->after('is_admin');
        });

        User::query()->where('is_admin', true)->update(['onboarding_completed' => true]);

        foreach (User::query()->where('is_admin', false)->get() as $user) {
            $complete = $user->businesses->contains(
                fn (Business $b) => $b->isProfileCompleteForOnboarding()
            );
            if ($complete) {
                $user->onboarding_completed = true;
                $user->save();
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarding_completed');
        });
    }
};
