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
        // 1. Buat user dummy sebagai seller jika belum ada
        $seller = User::first() ?? User::create([
            'user_id' => (string) Str::uuid(),
            'name' => 'Wontu Seller',
            'username' => 'wontuseller',
            'email' => 'seller@wontu.com',
            'google_id' => '123456789',
            'avatar' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1777530871/martabak-manis_umffen.jpg',
        ]);

        // --- OFFER 1: MARTABAK ---
        $offer1 = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'food',
            'merchant_name' => 'Martabak Pecenongan',
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
            ]
        ]);

        // --- OFFER 2: Teazzi ---
        $offer2 = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'food',
            'merchant_name' => 'Teazzi',
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

        // --- OFFER 3: LAUNDRY / OTHER (Untuk test filter kategori) ---
        $offer3 = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'other',
            'merchant_name' => 'Clean & Fresh Laundry',
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