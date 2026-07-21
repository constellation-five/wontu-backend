<?php

namespace Database\Seeders;

use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RequestSeeder extends Seeder
{
    public function run(): void
    {
        $requester = User::create([
            'user_id' => (string) Str::uuid(),
            'name' => 'Wontu Requester',
            'username' => 'wonturequester',
            'email' => 'requester@wontu.com',
            'google_id' => '987654321',
            'avatar' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1769437538/main-sample.png',
        ]);

        RequestModel::create([
            'requester_id' => $requester->user_id,
            'item_name' => 'Martabak Keju',
            'category' => 'food',
            'location_label' => 'BCA Learning Institute',
            'location' => RequestModel::makePoint(-6.585841, 106.882002),
            'arrival_time' => now()->addHours(2),
            'total_votes' => 8,
        ]);

        RequestModel::create([
            'requester_id' => $requester->user_id,
            'item_name' => 'Avocado Juice',
            'category' => 'food',
            'location_label' => 'BCA Learning Institute',
            'location' => RequestModel::makePoint(-6.585841, 106.882002),
            'arrival_time' => now()->addHours(6),
            'total_votes' => 15,
        ]);

        RequestModel::create([
            'requester_id' => $requester->user_id,
            'item_name' => 'Kabel Charger Type C',
            'category' => 'other',
            'location_label' => 'Rumah Talenta BCA',
            'location' => RequestModel::makePoint(-6.588640, 106.882475),
            'arrival_time' => now()->addDays(1)->addHours(2),
            'total_votes' => 3,
        ]);

        RequestModel::create([
            'requester_id' => $requester->user_id,
            'item_name' => 'MCD Paket Panas',
            'category' => 'food',
            'location_label' => 'Rumah Talenta BCA',
            'location' => RequestModel::makePoint(-6.588640, 106.882475),
            'arrival_time' => now()->addDays(3),
            'total_votes' => 25,
        ]);
    }
}
