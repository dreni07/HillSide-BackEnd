<?php

namespace Database\Seeders;

use App\Enums\BusinessCategory;
use App\Models\BusinessType;
use Illuminate\Database\Seeder;

class BusinessTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'category' => BusinessCategory::COMMERCE,
                'name' => 'Clothing & Fashion',
                'slug' => 'clothing-fashion',
                'description' => 'Apparel, accessories, and fashion retail businesses.',
            ],
            [
                'category' => BusinessCategory::COMMERCE,
                'name' => 'Food & Beverages',
                'slug' => 'food-beverages',
                'description' => 'Restaurants, cafes, bakeries, and food delivery businesses.',
            ],
            [
                'category' => BusinessCategory::COMMERCE,
                'name' => 'Electronics & Technology',
                'slug' => 'electronics-technology',
                'description' => 'Consumer electronics, gadgets, and tech product retailers.',
            ],
            [
                'category' => BusinessCategory::COMMERCE,
                'name' => 'Beauty & Cosmetics',
                'slug' => 'beauty-cosmetics',
                'description' => 'Skincare, makeup, and personal care product businesses.',
            ],
            [
                'category' => BusinessCategory::COMMERCE,
                'name' => 'Home & Furniture',
                'slug' => 'home-furniture',
                'description' => 'Home decor, furniture, and household goods retailers.',
            ],
            [
                'category' => BusinessCategory::COMMERCE,
                'name' => 'General Retail',
                'slug' => 'general-retail',
                'description' => 'Multi-category or general merchandise retail stores.',
            ],
            [
                'category' => BusinessCategory::COMMERCE,
                'name' => 'Health & Supplements',
                'slug' => 'health-supplements',
                'description' => 'Vitamins, supplements, and health product retailers.',
            ],
            [
                'category' => BusinessCategory::COMMERCE,
                'name' => 'Sports & Outdoors',
                'slug' => 'sports-outdoors',
                'description' => 'Sporting goods, outdoor gear, and athletic equipment.',
            ],

            // Services
            [
                'category' => BusinessCategory::SERVICES,
                'name' => 'Travel Agency',
                'slug' => 'travel-agency',
                'description' => 'Travel planning, tours, and vacation package providers.',
            ],
            [
                'category' => BusinessCategory::SERVICES,
                'name' => 'Hotel & Hospitality',
                'slug' => 'hotel-hospitality',
                'description' => 'Hotels, resorts, hostels, and accommodation services.',
            ],
            [
                'category' => BusinessCategory::SERVICES,
                'name' => 'Real Estate',
                'slug' => 'real-estate',
                'description' => 'Property sales, rentals, and real estate agencies.',
            ],
            [
                'category' => BusinessCategory::SERVICES,
                'name' => 'Healthcare',
                'slug' => 'healthcare',
                'description' => 'Clinics, dental offices, and medical service providers.',
            ],
            [
                'category' => BusinessCategory::SERVICES,
                'name' => 'Education & Tutoring',
                'slug' => 'education-tutoring',
                'description' => 'Schools, tutoring centers, and online education platforms.',
            ],
            [
                'category' => BusinessCategory::SERVICES,
                'name' => 'Consulting',
                'slug' => 'consulting',
                'description' => 'Business, IT, marketing, and management consulting firms.',
            ],
            [
                'category' => BusinessCategory::SERVICES,
                'name' => 'Fitness & Wellness',
                'slug' => 'fitness-wellness',
                'description' => 'Gyms, yoga studios, spas, and wellness centers.',
            ],
            [
                'category' => BusinessCategory::SERVICES,
                'name' => 'Automotive',
                'slug' => 'automotive',
                'description' => 'Car dealerships, repair shops, and auto service businesses.',
            ],
            [
                'category' => BusinessCategory::SERVICES,
                'name' => 'Legal Services',
                'slug' => 'legal-services',
                'description' => 'Law firms, notaries, and legal consultation providers.',
            ],
            [
                'category' => BusinessCategory::SERVICES,
                'name' => 'Financial Services',
                'slug' => 'financial-services',
                'description' => 'Accounting, insurance, and financial advisory firms.',
            ],
        ];

        foreach ($types as $type) {
            BusinessType::updateOrCreate(
                ['slug' => $type['slug']],
                $type,
            );
        }
    }
}
