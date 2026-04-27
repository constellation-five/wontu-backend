<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JoinOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_join_an_offer(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $offer = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'food',
            'merchant_name' => 'Local Market',
            'closing_time' => now()->addHours(2),
            'arrival_time' => now()->addHours(3),
            'has_cod_payment' => true,
        ]);

        $response = $this->actingAs($buyer, 'sanctum')->postJson("/offers/{$offer->offer_id}/join");

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'offer' => [
                'offer_id',
                'seller_id',
                'category',
                'merchant_name',
                'buyers',
            ],
        ]);

        $this->assertTrue($offer->buyers()->where('users.user_id', $buyer->user_id)->exists());
    }

    public function test_seller_cannot_join_their_own_offer(): void
    {
        $seller = User::factory()->create();

        $offer = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'food',
            'merchant_name' => 'Local Market',
            'closing_time' => now()->addHours(2),
            'arrival_time' => now()->addHours(3),
            'has_cod_payment' => true,
        ]);

        $response = $this->actingAs($seller, 'sanctum')->postJson("/offers/{$offer->offer_id}/join");

        $response->assertForbidden();
        $response->assertJson([
            'message' => 'You cannot join your own offer.',
        ]);

        $this->assertFalse($offer->buyers()->where('users.user_id', $seller->user_id)->exists());
    }

    public function test_user_cannot_join_same_offer_twice(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $offer = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'food',
            'merchant_name' => 'Local Market',
            'closing_time' => now()->addHours(2),
            'arrival_time' => now()->addHours(3),
            'has_cod_payment' => true,
        ]);

        // First join
        $this->actingAs($buyer, 'sanctum')->postJson("/offers/{$offer->offer_id}/join");

        // Second join attempt
        $response = $this->actingAs($buyer, 'sanctum')->postJson("/offers/{$offer->offer_id}/join");

        $response->assertOk();

        // Verify user is only joined once
        $this->assertEquals(1, $offer->buyers()->where('users.user_id', $buyer->user_id)->count());
    }

    public function test_unauthenticated_user_cannot_join_offer(): void
    {
        $seller = User::factory()->create();

        $offer = Offer::create([
            'seller_id' => $seller->user_id,
            'category' => 'food',
            'merchant_name' => 'Local Market',
            'closing_time' => now()->addHours(2),
            'arrival_time' => now()->addHours(3),
            'has_cod_payment' => true,
        ]);

        $response = $this->postJson("/offers/{$offer->offer_id}/join");

        $response->assertUnauthorized();
    }
}
