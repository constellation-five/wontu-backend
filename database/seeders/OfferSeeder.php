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
        // Buat user dummy sebagai seller jika belum ada
        $seller = User::first() ?? User::create([
            'user_id' => (string) Str::uuid(),
            'name' => 'Wontu Seller',
            'username' => 'wontuseller',
            'email' => 'seller@wontu.com',
            'google_id' => '123456789',
        ]);

        // Offer 1: Martabak (Untuk test search merchant)
        $offer1 = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'food',
            'merchant_name' => 'Martabakku Love',
            'closing_time' => now()->addHours(2),
            'arrival_time' => now()->addHours(5),
            'has_cod_payment' => true,
            'is_completed' => false,
        ]);

        $offer1->items()->createMany([
            [
                'item_name' => 'Martabak Manis Cokelat',
                'item_price' => 18000.00,
                'slot' => 10,
                'current_slot' => 0,
            ],
            [
                'item_name' => 'Martabak Telur',
                'item_price' => 25000.00,
                'slot' => 5,
                'current_slot' => 1,
            ]
        ]);

        // Offer 2: Kopi (Untuk test search item name)
        $offer2 = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'food',
            'merchant_name' => 'Kenangan Terindah',
            'closing_time' => now()->addHours(1),
            'arrival_time' => now()->addHours(3),
            'has_cod_payment' => false,
        ]);

        $offer2->items()->create([
            'item_name' => 'Kopi Susu Gula Aren',
            'item_price' => 20000.00,
            'slot' => 20,
            'current_slot' => 5,
        ]);
    }
}