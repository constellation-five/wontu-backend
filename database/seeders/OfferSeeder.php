<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Offer;
use App\Models\OfferBuyer;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OfferSeeder extends Seeder
{
    /** @var array<int, User> */
    private array $dummyUsers = [];

    public function run(): void
    {
        $this->dummyUsers = $this->createDummyUsers();

        $offer1 = Offer::create([
            'seller_id' => $this->dummyUsers[0]->user_id,
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
            ],
        ]);
        $this->syncSellerPaymentMethods($offer1);

        $offer2 = Offer::create([
            'seller_id' => $this->dummyUsers[1]->user_id,
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
        $this->syncSellerPaymentMethods($offer2);

        $offer3 = Offer::create([
            'seller_id' => $this->dummyUsers[2]->user_id,
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
        $this->syncSellerPaymentMethods($offer3);

        $offer4 = Offer::create([
            'seller_id' => $this->dummyUsers[0]->user_id,
            'category' => 'food',
            'merchant_name' => 'Martabak Legit',
            'location_label' => 'BCA Learning Institute',
            'location' => Offer::makePoint(-6.585841, 106.882002),
            'closing_time' => now()->addHours(2),
            'arrival_time' => now()->addHours(5),
            'has_cod_payment' => true,
            'is_completed' => false,
        ]);

        $offer4->items()->createMany([
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
            ],
        ]);
        $this->syncSellerPaymentMethods($offer4);

        $offer5 = Offer::create([
            'seller_id' => $this->dummyUsers[1]->user_id,
            'category' => 'food',
            'merchant_name' => 'Tea',
            'location_label' => 'BCA Learning Institute',
            'location' => Offer::makePoint(-6.585841, 106.882002),
            'closing_time' => now()->addHours(1),
            'arrival_time' => now()->addHours(3),
            'has_cod_payment' => false,
            'is_completed' => false,
        ]);

        $offer5->items()->create([
            'item_name' => 'Deep Roast Oloong Milk Tea',
            'item_price' => 20000.00,
            'slot' => 20,
            'current_slot' => 5,
            'image_url' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1777538313/Screenshot_2026-04-30_153820_prneqm.png',
        ]);
        $this->syncSellerPaymentMethods($offer5);

        $offer6 = Offer::create([
            'seller_id' => $this->dummyUsers[2]->user_id,
            'category' => 'food',
            'merchant_name' => 'Martabak Orins',
            'location_label' => 'Rumah Talenta BCA',
            'location' => Offer::makePoint(-6.588640, 106.882475),
            'closing_time' => now()->addHours(2),
            'arrival_time' => now()->addHours(5),
            'has_cod_payment' => true,
            'is_completed' => false,
        ]);

        $offer6->items()->createMany([
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
            ],
        ]);
        $this->syncSellerPaymentMethods($offer6);

        $offer7 = Offer::create([
            'seller_id' => $this->dummyUsers[0]->user_id,
            'category' => 'food',
            'merchant_name' => 'Es Teh Indonesia',
            'location_label' => 'Rumah Talenta BCA',
            'location' => Offer::makePoint(-6.588640, 106.882475),
            'closing_time' => now()->addHours(1),
            'arrival_time' => now()->addHours(3),
            'has_cod_payment' => false,
            'is_completed' => false,
        ]);

        $offer7->items()->create([
            'item_name' => 'Deep Roast Oloong Milk Tea',
            'item_price' => 20000.00,
            'slot' => 20,
            'current_slot' => 5,
            'image_url' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1777538313/Screenshot_2026-04-30_153820_prneqm.png',
        ]);
        $this->syncSellerPaymentMethods($offer7);

        $this->seedOffersForFirstRealUser();
    }

    /**
     * A handful of dummy sellers, each with a fake avatar and payment method
     * so the frontend has real data to render (Manage Offer's payment
     * methods pane, buyer-side checkout payment methods, etc).
     *
     * @return array<int, User>
     */
    private function createDummyUsers(): array
    {
        $dummies = [
            [
                'name' => 'Sarah Amelia',
                'username' => 'sarahamelia',
                'email' => 'sarah.amelia@wontu.dummy',
                'avatar' => 'https://i.pravatar.cc/300?u=sarahamelia',
                'bank_name' => 'Bank Negara Indonesia',
                'account_number' => '1020304050',
            ],
            [
                'name' => 'Budi Santoso',
                'username' => 'budisantoso',
                'email' => 'budi.santoso@wontu.dummy',
                'avatar' => 'https://i.pravatar.cc/300?u=budisantoso',
                'bank_name' => 'Bank Central Asia',
                'account_number' => '2233445566',
            ],
            [
                'name' => 'Rina Wulandari',
                'username' => 'rinawulandari',
                'email' => 'rina.wulandari@wontu.dummy',
                'avatar' => 'https://i.pravatar.cc/300?u=rinawulandari',
                'bank_name' => 'Bank Mandiri',
                'account_number' => '7788990011',
            ],
        ];

        return array_map(function (array $dummy) {
            $user = User::create([
                'user_id' => (string) Str::uuid(),
                'name' => $dummy['name'],
                'username' => $dummy['username'],
                'email' => $dummy['email'],
                'google_id' => (string) random_int(100000000, 999999999),
                'avatar' => $dummy['avatar'],
            ]);

            PaymentMethod::create([
                'user_id' => $user->user_id,
                'bank_name' => $dummy['bank_name'],
                'account_name' => $dummy['name'],
                'account_number' => $dummy['account_number'],
            ]);

            return $user;
        }, $dummies);
    }

    private function syncSellerPaymentMethods(Offer $offer): void
    {
        $paymentMethodIds = PaymentMethod::where('user_id', $offer->seller_id)
            ->pluck('payment_method_id');

        $offer->paymentMethods()->sync($paymentMethodIds);
    }

    /**
     * Two offers sold by the first non-dummy user in the table (typically
     * the "Test User" created in DatabaseSeeder before this seeder runs),
     * with every dummy user as a paying customer — so there's a seller
     * account with real incoming orders to manage.
     */
    private function seedOffersForFirstRealUser(): void
    {
        $dummyUserIds = array_map(fn (User $u) => $u->user_id, $this->dummyUsers);

        $realSeller = User::whereNotIn('user_id', $dummyUserIds)
            ->orderBy('created_at')
            ->first();

        if (! $realSeller) {
            return;
        }

        if (! PaymentMethod::where('user_id', $realSeller->user_id)->exists()) {
            PaymentMethod::create([
                'user_id' => $realSeller->user_id,
                'bank_name' => 'Bank Negara Indonesia',
                'account_name' => $realSeller->name,
                'account_number' => '5566778899',
            ]);
        }

        $offer8 = Offer::create([
            'seller_id' => $realSeller->user_id,
            'category' => 'food',
            'merchant_name' => 'Warung Nasi Kebon Sirih',
            'location_label' => 'BCA Learning Institute',
            'location' => Offer::makePoint(-6.585841, 106.882002),
            'closing_time' => now()->addHours(3),
            'arrival_time' => now()->addHours(6),
            'has_cod_payment' => true,
            'is_completed' => false,
        ]);

        $offer8Items = $offer8->items()->createMany([
            [
                'item_name' => 'Nasi Ayam Geprek',
                'item_price' => 18000.00,
                'slot' => 15,
                'current_slot' => 0,
                'image_url' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1777538467/Screenshot_2026-04-30_154024_rzsp8b.png',
            ],
            [
                'item_name' => 'Nasi Ayam Bakar',
                'item_price' => 20000.00,
                'slot' => 15,
                'current_slot' => 0,
                'image_url' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1777538467/Screenshot_2026-04-30_154024_rzsp8b.png',
            ],
        ]);
        $this->syncSellerPaymentMethods($offer8);
        $this->seedDummyOrders($offer8, $offer8Items);

        $offer9 = Offer::create([
            'seller_id' => $realSeller->user_id,
            'category' => 'other',
            'merchant_name' => 'Fix It Phone Service',
            'location_label' => 'BCA Learning Institute',
            'location' => Offer::makePoint(-6.585841, 106.882002),
            'closing_time' => now()->addHours(5),
            'arrival_time' => now()->addDays(1),
            'has_cod_payment' => false,
            'is_completed' => false,
        ]);

        $offer9Items = $offer9->items()->createMany([
            [
                'item_name' => 'Ganti Layar HP',
                'item_price' => 150000.00,
                'slot' => 6,
                'current_slot' => 0,
                'image_url' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1777538246/Screenshot_2026-04-30_153544_x3lclh.png',
            ],
            [
                'item_name' => 'Ganti Baterai HP',
                'item_price' => 90000.00,
                'slot' => 8,
                'current_slot' => 0,
                'image_url' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1777538246/Screenshot_2026-04-30_153544_x3lclh.png',
            ],
        ]);
        $this->syncSellerPaymentMethods($offer9);
        $this->seedDummyOrders($offer9, $offer9Items);
    }

    /**
     * @param  Collection<int, Item>  $items
     */
    private function seedDummyOrders(Offer $offer, Collection $items): void
    {
        foreach ($this->dummyUsers as $index => $buyer) {
            $item = $items[$index % $items->count()];
            $quantity = $index + 1;

            $offerBuyer = OfferBuyer::create([
                'offer_id' => $offer->offer_id,
                'buyer_id' => $buyer->user_id,
            ]);

            $offerBuyer->items()->create([
                'item_id' => $item->item_id,
                'quantity' => $quantity,
                'notes' => null,
            ]);

            $item->current_slot = min($item->slot, $item->current_slot + $quantity);
            $item->save();
        }
    }
}
