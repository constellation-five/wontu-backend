<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\OfferBuyer;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds one completed Order for the first real (non-dummy) user,
 * so that the "Rate" button in the Activity → History tab can be tested.
 *
 * Run with: php artisan db:seed --class=CompletedOrderTestSeeder
 */
class HistorySeeder extends Seeder
{
    public function run(): void
    {
        // Resolve the buyer — first real (non-dummy) user in the system.
        $dummyEmails = ['sarah.amelia@wontu.dummy', 'budi.santoso@wontu.dummy', 'rina.wulandari@wontu.dummy'];
        $buyer = User::whereNotIn('email', $dummyEmails)->orderBy('created_at')->first();

        if (! $buyer) {
            $this->command->error('No real user found. Please register an account first.');

            return;
        }

        // Dummy seller
        $seller = User::where('email', 'sarah.amelia@wontu.dummy')->first();

        if (! $seller) {
            $this->command->error('Dummy seller not found. Please run OfferSeeder first.');

            return;
        }

        $paymentMethod = PaymentMethod::where('user_id', $seller->user_id)->first();
        if (! $paymentMethod) {
            $paymentMethod = PaymentMethod::create([
                'user_id' => $seller->user_id,
                'bank_name' => 'Bank Central Asia',
                'account_name' => $seller->name,
                'account_number' => '1234567890',
            ]);
        }

        // Create a completed offer (closed_at and arrived_at both set).
        $offer = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'food',
            'merchant_name' => 'Soto Betawi Pak Kumis',
            'location_label' => 'BCA Learning Institute',
            'location' => Offer::makePoint(-6.585841, 106.882002),
            'closing_time' => now()->subHours(5),
            'closed_at' => now()->subHours(5),
            'arrival_time' => now()->subHours(2),
            'arrived_at' => now()->subHours(2),
            'payments_confirmed_at' => now()->subHours(3),
            'has_cod_payment' => true,
            'is_completed' => true,
        ]);

        $item = $offer->items()->create([
            'item_name' => 'Soto Betawi Komplit',
            'item_price' => 25000.00,
            'slot' => 10,
            'current_slot' => 10,
            'image_url' => 'https://res.cloudinary.com/ditdykukf/image/upload/v1777538467/Screenshot_2026-04-30_154024_rzsp8b.png',
        ]);

        // Sync seller payment methods to the offer.
        $offer->paymentMethods()->sync([$paymentMethod->payment_method_id]);

        // Create the OfferBuyer (= the Order) for our real user, fully paid.
        $offerBuyer = OfferBuyer::create([
            'offer_id' => $offer->offer_id,
            'buyer_id' => $buyer->user_id,
            'is_confirmed' => true,
            'confirmed_at' => now()->subHours(3),
            'payment_submitted_at' => now()->subHours(4),
        ]);

        $offerBuyer->items()->create([
            'item_id' => $item->item_id,
            'quantity' => 2,
            'notes' => null,
        ]);

        $this->command->info("✅ Created completed order for [{$buyer->name}] at offer [{$offer->merchant_name}] (offer_id: {$offer->offer_id})");
    }
}
