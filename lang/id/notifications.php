<?php

return [
    'NOTIF_BUYER_JOINED' => [
        'title' => 'Pembeli Baru Bergabung',
        'description' => ':buyer_name bergabung dengan offer :merchant_name Anda.',
    ],
    'NOTIF_BUYER_REMOVED_FROM_OFFER' => [
        'title' => 'Order Anda Dihapus',
        'description' => 'Order Anda di offer :merchant_name dihapus karena penjual mengurangi stok yang tersedia (:items).',
    ],
    'NOTIF_ITEMS_ARRIVED' => [
        'title' => 'Barang Telah Tiba',
        'description' => 'Barang untuk offer :merchant_name yang Anda ikuti telah tiba.',
    ],
    'NOTIF_OFFER_AUTO_CLOSED_SOLD_OUT' => [
        'title' => 'Offer Habis Terjual dan Ditutup',
        'description' => 'Offer :merchant_name Anda mencapai waktu penutupan dengan habis terjual sepenuhnya dan telah ditutup secara otomatis.',
    ],
    'NOTIF_OFFER_CLOSED' => [
        'title' => 'Offer Ditutup',
        'description' => 'Offer :merchant_name yang Anda ikuti telah ditutup.',
    ],
    'NOTIF_OFFER_COMPLETED' => [
        'title' => 'Offer Selesai!',
        'description' => 'Offer :merchant_name sekarang telah selesai. Silakan lanjutkan dengan pembayaran.',
    ],
    'NOTIF_OFFER_DELETED' => [
        'title' => 'Offer Dihapus',
        'description' => 'Offer :merchant_name yang Anda ikuti telah dihapus oleh penjual. Order Anda telah dibatalkan.',
    ],
    'NOTIF_OFFER_SOLD_OUT_EARLY' => [
        'title' => 'Offer Habis Terjual',
        'description' => 'Offer :merchant_name Anda habis terjual sebelum waktu penutupannya. Offer akan ditutup secara otomatis ketika mencapai waktu penutupannya.',
    ],
    'NOTIF_ORDER_CANCELLED' => [
        'title' => 'Order Dibatalkan',
        'description' => ':buyer_name membatalkan order mereka di offer :merchant_name Anda.',
    ],
    'NOTIF_ORDER_PLACED' => [
        'title' => 'Order Baru Dibuat',
        'description' => ':buyer_name membuat order di offer :merchant_name Anda.',
    ],
    'NOTIF_ORDER_UPDATED' => [
        'title' => 'Order Diperbarui',
        'description' => ':buyer_name memperbarui order mereka di offer :merchant_name Anda.',
    ],
    'NOTIF_PAYMENT_CONFIRMED' => [
        'title' => 'Pembayaran Dikonfirmasi',
        'description' => 'Pembayaran Anda untuk offer :merchant_name telah dikonfirmasi oleh penjual.',
    ],
    'NOTIF_PAYMENT_PROOF_UPLOADED' => [
        'title' => 'Bukti Pembayaran Diunggah',
        'description' => ':buyer_name mengunggah bukti pembayaran untuk offer :merchant_name Anda.',
    ],
    'NOTIF_USER_FOLLOWED' => [
        'title' => 'Follower Baru',
        'description' => ':follower_name mulai following Anda.',
    ],
    'NOTIF_ITEM_ADJUSTED' => [
        'title' => 'Order Anda Disesuaikan',
        'description' => 'Penjual mengurangi stok yang tersedia untuk \':itemName\' di offer :merchant_name, sehingga kuantitas Anda disesuaikan menjadi :newQuantity.',
    ],
    'NOTIF_OFFER_EDITED_DISRUPTIVE' => [
        'title' => 'Perubahan Offer Penting',
        'description' => 'Offer :merchant_name yang Anda ikuti diubah dengan cara yang mungkin memengaruhi order Anda. Silakan tinjau.',
    ],
    'NOTIF_OFFER_EDITED' => [
        'title' => 'Offer Diperbarui',
        'description' => 'Offer :merchant_name yang Anda ikuti diperbarui oleh penjual.',
    ],
    'NOTIF_OFFER_CLOSING_REACHED_NOT_SOLD_OUT' => [
        'title' => 'Offer Ditutup',
        'description' => 'Waktu penutupan offer :merchant_name telah tercapai. Offer tersebut tidak habis terjual, jadi akan tetap terbuka hingga Anda menutupnya secara manual.',
    ],
    'NOTIF_NEW_CHAT_MESSAGE' => [
        'title' => 'Pesan baru dari :sender',
        'description' => ':preview', // Note: Preview logic handled dynamically on frontend
    ],
    'SYS_OFFER_CLOSED' => [
        'title' => 'Offer Ditutup',
        'description' => 'Offer :merchant_name telah ditutup oleh penjual dan tidak lagi menerima order.',
    ],
    'SYS_ITEMS_ARRIVED' => [
        'title' => 'Barang Telah Tiba',
        'description' => 'Barang untuk offer :merchant_name telah tiba. Chat ini akan ditutup untuk pesan baru pada :chat_closes_at.',
    ],
    'SYS_BUYER_JOINED' => [
        'title' => 'Pembeli Bergabung',
        'description' => ':user_name bergabung dengan offer :merchant_name.',
    ],
    'SYS_OFFER_COMPLETED' => [
        'title' => 'Offer Selesai',
        'description' => 'Offer :merchant_name telah ditandai sebagai selesai.',
    ],
    'SYS_BUYER_LEFT' => [
        'title' => 'Pembeli Keluar',
        'description' => ':user_name keluar dari offer :merchant_name.',
    ],
    'SYS_OFFER_UPDATED' => [
        'title' => 'Offer Diperbarui',
        'description' => 'Offer :merchant_name diperbarui oleh penjual.',
    ],
    'NOTIF_FOLLOWING_NEW_OFFER' => [
        'title' => 'Offer Baru dari :user_name',
        'description' => ':user_name baru saja membuat offer baru: :offer_name.',
    ],
    'NOTIF_FOLLOWING_NEW_REQUEST' => [
        'title' => 'Request Baru dari :user_name',
        'description' => ':user_name baru saja membuat request baru: :request_name.',
    ],
];
