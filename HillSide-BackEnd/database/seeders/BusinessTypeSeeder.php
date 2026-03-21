<?php

namespace Database\Seeders;

use App\Enums\BusinessCategory;
use App\Models\BusinessType;
use Illuminate\Database\Seeder;

class BusinessTypeSeeder extends Seeder
{
    /** Slugs që mbeten të aktivë dhe shfaqen në onboarding / dropdown. */
    private const ACTIVE_SLUGS = ['ecommerce', 'service'];

    public function run(): void
    {
        $types = [
            [
                'category' => BusinessCategory::COMMERCE,
                'name' => 'Ecommerce',
                'slug' => 'ecommerce',
                'description' => 'Online stores, marketplaces, and digital retail.',
                'is_active' => true,
            ],
            [
                'category' => BusinessCategory::SERVICES,
                'name' => 'Service',
                'slug' => 'service',
                'description' => 'General service-based businesses.',
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            BusinessType::updateOrCreate(
                ['slug' => $type['slug']],
                $type,
            );
        }

        BusinessType::whereNotIn('slug', self::ACTIVE_SLUGS)->update(['is_active' => false]);
    }
}
