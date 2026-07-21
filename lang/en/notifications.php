<?php

return [
    'NOTIF_BUYER_JOINED' => [
        'title' => 'New Buyer Joined',
        'description' => ':buyer_name joined your :merchant_name offer.',
    ],
    'NOTIF_BUYER_REMOVED_FROM_OFFER' => [
        'title' => 'Your Order Was Removed',
        'description' => 'Your order in the :merchant_name offer was removed because the seller reduced the available stock (:items).',
    ],
    'NOTIF_ITEMS_ARRIVED' => [
        'title' => 'Items Have Arrived',
        'description' => 'The items for the :merchant_name offer you joined have arrived.',
    ],
    'NOTIF_OFFER_AUTO_CLOSED_SOLD_OUT' => [
        'title' => 'Offer Sold Out and Closed',
        'description' => 'Your :merchant_name offer reached its closing time fully sold out and has been closed automatically.',
    ],
    'NOTIF_OFFER_CLOSED' => [
        'title' => 'Offer Closed',
        'description' => 'The :merchant_name offer you joined has been closed.',
    ],
    'NOTIF_OFFER_COMPLETED' => [
        'title' => 'Offer Complete!',
        'description' => 'The :merchant_name offer is now complete. Please proceed with payment.',
    ],
    'NOTIF_OFFER_DELETED' => [
        'title' => 'Offer Deleted',
        'description' => 'The :merchant_name offer you joined was deleted by the seller. Your order has been cancelled.',
    ],
    'NOTIF_OFFER_SOLD_OUT_EARLY' => [
        'title' => 'Offer Sold Out',
        'description' => 'Your :merchant_name offer sold out before its closing time and has been closed automatically.',
    ],
    'NOTIF_ORDER_CANCELLED' => [
        'title' => 'Order Cancelled',
        'description' => ':buyer_name cancelled their order in your :merchant_name offer.',
    ],
    'NOTIF_ORDER_PLACED' => [
        'title' => 'New Order Placed',
        'description' => ':buyer_name placed an order in your :merchant_name offer.',
    ],
    'NOTIF_ORDER_UPDATED' => [
        'title' => 'Order Updated',
        'description' => ':buyer_name updated their order in your :merchant_name offer.',
    ],
    'NOTIF_PAYMENT_CONFIRMED' => [
        'title' => 'Payment Confirmed',
        'description' => 'Your payment for the :merchant_name offer has been confirmed by the seller.',
    ],
    'NOTIF_PAYMENT_PROOF_UPLOADED' => [
        'title' => 'Payment Proof Uploaded',
        'description' => ':buyer_name uploaded a payment proof for your :merchant_name offer.',
    ],
    'NOTIF_USER_FOLLOWED' => [
        'title' => 'New Follower',
        'description' => ':follower_name started following you.',
    ],
    'NOTIF_ITEM_ADJUSTED' => [
        'title' => 'Your Order Was Adjusted',
        'description' => 'The seller reduced available stock for \':itemName\' in the :merchant_name offer, so your quantity was adjusted to :newQuantity.',
    ],
    'NOTIF_OFFER_EDITED_DISRUPTIVE' => [
        'title' => 'Important Offer Changes',
        'description' => 'The :merchant_name offer you joined was changed in ways that may affect your order. Please review.',
    ],
    'NOTIF_OFFER_EDITED' => [
        'title' => 'Offer Updated',
        'description' => 'The :merchant_name offer you joined was updated by the seller.',
    ],
    'NOTIF_OFFER_CLOSING_REACHED_NOT_SOLD_OUT' => [
        'title' => 'Offer Closed',
        'description' => 'Your :merchant_name offer\'s closing time was reached and it has been closed automatically, not fully sold out.',
    ],
    'NOTIF_NEW_CHAT_MESSAGE' => [
        'title' => 'New message from :sender',
        'description' => ':preview', // Note: Preview logic handled dynamically on frontend
    ],
    'SYS_OFFER_CLOSED' => [
        'title' => 'Offer Closed',
        'description' => 'The :merchant_name offer has been closed by the seller and is no longer accepting orders.',
    ],
    'SYS_ITEMS_ARRIVED' => [
        'title' => 'Items Have Arrived',
        'description' => 'The items for the :merchant_name offer have arrived. This chat will close to new messages on :chat_closes_at.',
    ],
    'SYS_BUYER_JOINED' => [
        'title' => 'Buyer Joined',
        'description' => ':user_name joined the :merchant_name offer.',
    ],
    'SYS_OFFER_COMPLETED' => [
        'title' => 'Offer Completed',
        'description' => 'The :merchant_name offer has been marked as completed.',
    ],
    'SYS_BUYER_LEFT' => [
        'title' => 'Buyer Left',
        'description' => ':user_name left the :merchant_name offer.',
    ],
    'SYS_OFFER_UPDATED' => [
        'title' => 'Offer Updated',
        'description' => 'The :merchant_name offer was updated by the seller.',
    ],
];
