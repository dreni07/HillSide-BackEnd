<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class BusinessService
{
    public function getBusinessesForUser(User $user): Collection
    {
        return $user->businesses()->with('businessType')->get();
    }

    public function findBusinessForUser(User $user, int $businessId): ?Business
    {
        return $user->businesses()->with('businessType')->find($businessId);
    }

    public function createBusiness(User $user, array $data): Business
    {
        $data['user_id'] = $user->id;

        return Business::create($data);
    }

    public function updateBusiness(Business $business, array $data): Business
    {
        $business->update($data);

        return $business->refresh();
    }

    public function assignBusinessType(Business $business, int $businessTypeId): Business
    {
        $businessType = BusinessType::where('id', $businessTypeId)
            ->where('is_active', true)
            ->firstOrFail();

        $business->update(['business_type_id' => $businessType->id]);

        return $business->load('businessType');
    }
}
