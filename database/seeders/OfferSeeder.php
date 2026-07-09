<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::create([
            'user_id' => (string) Str::uuid(),
            'name' => 'Wontu Seller',
            'username' => 'wontuseller',
            'email' => 'seller@wontu.com',
            'google_id' => '123456789',
            'avatar' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1769437538/main-sample.png',
        ]);

        $offer1 = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'food',
            'merchant_name' => 'Martabak Pecenongan',
            'location_label' => 'Jl. Pecenongan No.72, Jakarta Pusat',
            'location' => Offer::makePoint(-6.1665, 106.8236),
            'closing_time' => now()->addHours(2),
            'arrival_time' => now()->addHours(5),
            'has_cod_payment' => true,
            'is_completed' => false,
        ]);

        $offer1->items()->createMany([
            [
                'item_name' => 'Martabak Manis Keju Susu',
                'item_price' => 20000.00,
                'slot' => 10,
                'current_slot' => 0,
                'image_url' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1777538246/Screenshot_2026-04-30_153544_x3lclh.png',
            ],
            [
                'item_name' => 'Martabak Telur',
                'item_price' => 25000.00,
                'slot' => 5,
                'current_slot' => 1,
                'image_url' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1777538246/Screenshot_2026-04-30_153646_fabyul.png',
            ],
            [
                'item_name' => 'Martabak Manis Cokelat Kacang',
                'item_price' => 22000.00,
                'slot' => 8,
                'current_slot' => 2,
                'image_url' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1777538246/Screenshot_2026-04-30_153544_x3lclh.png',
            ],
            [
                'item_name' => 'Martabak Tipker (Tipis Kering)',
                'item_price' => 18000.00,
                'slot' => 12,
                'current_slot' => 0,
                'image_url' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1777538246/Screenshot_2026-04-30_153544_x3lclh.png',
            ]
        ]);

        $offer2 = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'food',
            'merchant_name' => 'Teazzi',
            'location_label' => 'Jl. Kemang Raya No.8, Jakarta Selatan',
            'location' => Offer::makePoint(-6.2608, 106.8135),
            'closing_time' => now()->addHours(1),
            'arrival_time' => now()->addHours(3),
            'has_cod_payment' => false,
            'is_completed' => false,
        ]);

        $offer2->items()->create([
            'item_name' => 'Deep Roast Oloong Milk Tea',
            'item_price' => 20000.00,
            'slot' => 20,
            'current_slot' => 5,
            'image_url' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1777538313/Screenshot_2026-04-30_153820_prneqm.png',
        ]);

        $offer3 = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'other',
            'merchant_name' => 'Clean & Fresh Laundry',
            'location_label' => 'Jl. Boulevard Raya, Kelapa Gading, Jakarta Utara',
            'location' => Offer::makePoint(-6.1588, 106.9056),
            'closing_time' => now()->addHours(4),
            'arrival_time' => now()->addDays(1),
            'has_cod_payment' => true,
            'is_completed' => false,
        ]);

        $offer3->items()->create([
            'item_name' => 'Cuci Kiloan 5kg',
            'item_price' => 35000.00,
            'slot' => 5,
            'current_slot' => 0,
            'image_url' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1777538467/Screenshot_2026-04-30_154024_rzsp8b.png',
        ]);
    }
}
