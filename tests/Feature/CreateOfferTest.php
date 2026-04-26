<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_an_offer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/offers', [
            'category' => 'food',
            'merchant_name' => 'Local Market',
            'closing_time' => '2026-05-01 12:00:00',
            'arrival_time' => '2026-05-01 13:00:00',
            'has_cod_payment' => true,
            'items' => [
                [
                    'item_name' => 'Bread',
                    'item_price' => 4.50,
                    'item_url' => 'https://example.com/bread',
                    'slot' => 5,
                    'current_slot' => 'slot',
                    'image_url' => 'https://example.com/bread.jpg',
                ],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'message',
            'offer' => [
                'offer_id',
                'seller_id',
                'category',
                'merchant_name',
                'closing_time',
                'arrival_time',
                'has_cod_payment',
                'is_completed',
                'items',
            ],
        ]);

        $this->assertDatabaseHas('offers', [
            'seller_id' => $user->user_id,
            'category' => 'food',
            'merchant_name' => 'Local Market',
            'has_cod_payment' => 1,
        ]);
    }

    public function test_offer_creation_requires_valid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/offers', [
            'category' => 'invalid',
            'merchant_name' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category', 'merchant_name', 'closing_time', 'arrival_time']);
    }
}
