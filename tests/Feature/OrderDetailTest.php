<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_view_order_detail(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $offer = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'food',
            'merchant_name' => 'Test Merchant',
            'closing_time' => now()->addHours(1),
            'arrival_time' => now()->addHours(2),
            'has_cod_payment' => false,
            'is_completed' => false,
        ]);

        // Buyer joins the offer with order metadata
        $offer->buyers()->attach($buyer->user_id, [
            'status' => 'pending',
            'notes' => 'Request keju dibanyakin',
            'total_amount' => 50000.00,
            'payment_proof_url' => 'https://example.com/proof.jpg',
        ]);

        OrderItem::create([
            'offer_id' => $offer->offer_id,
            'user_id' => $buyer->user_id,
            'item_name' => 'Martabak Keju',
            'item_price' => 25000.00,
            'quantity' => 1,
            'notes' => 'Kurang manis',
        ]);

        OrderItem::create([
            'offer_id' => $offer->offer_id,
            'user_id' => $buyer->user_id,
            'item_name' => 'Martabak Cokelat Keju',
            'item_price' => 25000.00,
            'quantity' => 1,
            'notes' => null,
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->getJson("/seller/offers/{$offer->offer_id}/buyers/{$buyer->user_id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'customer' => [
                    'name',
                    'email',
                    'avatar',
                ],
                'order' => [
                    'status',
                    'notes',
                    'total_amount',
                    'payment_proof_url',
                ],
                'items' => [
                    '*' => [
                        'item_name',
                        'item_price',
                        'quantity',
                        'subtotal',
                    ],
                ],
            ])
            ->assertJson([
                'order' => [
                    'status' => 'pending',
                    'notes' => 'Request keju dibanyakin',
                    'total_amount' => 50000.00,
                    'payment_proof_url' => 'https://example.com/proof.jpg',
                ],
            ]);

        $this->assertCount(2, $response->json('items'));
        $this->assertEquals('Martabak Keju', $response->json('items.0.item_name'));
        $this->assertEquals(25000.00, $response->json('items.0.item_price'));
        $this->assertEquals(1, $response->json('items.0.quantity'));
        $this->assertEquals(25000.00, $response->json('items.0.subtotal'));
    }

    public function test_non_seller_cannot_view_order_detail(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $otherUser = User::factory()->create();

        $offer = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'food',
            'merchant_name' => 'Test Merchant',
            'closing_time' => now()->addHours(1),
            'arrival_time' => now()->addHours(2),
            'has_cod_payment' => false,
            'is_completed' => false,
        ]);
        $offer->buyers()->attach($buyer->user_id);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/seller/offers/{$offer->offer_id}/buyers/{$buyer->user_id}");

        $response->assertStatus(403);
    }

    public function test_order_not_found_when_buyer_not_joined(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $offer = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'food',
            'merchant_name' => 'Test Merchant',
            'closing_time' => now()->addHours(1),
            'arrival_time' => now()->addHours(2),
            'has_cod_payment' => false,
            'is_completed' => false,
        ]);


        $response = $this->actingAs($seller, 'sanctum')
            ->getJson("/seller/offers/{$offer->offer_id}/buyers/{$buyer->user_id}");

        $response->assertStatus(404);
    }
}