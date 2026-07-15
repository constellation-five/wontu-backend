<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Notifications\OfferAutoClosedSoldOutNotification;
use App\Notifications\OfferClosedNotification;
use App\Notifications\OfferClosingReachedNotSoldOutNotification;
use App\Notifications\OfferSoldOutEarlyNotification;
use Illuminate\Console\Command;

class ProcessOfferLifecycle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'offers:process-lifecycle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-close offers whose closing time has passed, and offers that have sold out early.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $now = now();

        // Offers whose closing_time has passed but aren't closed yet.
        Offer::whereNull('closed_at')->where('closing_time', '<=', $now)->with('items', 'seller', 'buyers')->get()
            ->each(function (Offer $offer) {
                $offer->closed_at = now();
                $offer->save();

                $soldOut = $offer->items->isNotEmpty() && $offer->items->every(fn ($i) => $i->current_slot >= $i->slot);

                $offer->seller->notify($soldOut
                    ? new OfferAutoClosedSoldOutNotification($offer)
                    : new OfferClosingReachedNotSoldOutNotification($offer));

                foreach ($offer->buyers as $buyer) {
                    $buyer->notify(new OfferClosedNotification($offer));
                }
            });

        // Offers not yet closed, still before closing_time, but every item sold out.
        Offer::whereNull('closed_at')->where('closing_time', '>', $now)->with('items', 'seller', 'buyers')->get()
            ->filter(fn (Offer $offer) => $offer->items->isNotEmpty() && $offer->items->every(fn ($i) => $i->current_slot >= $i->slot))
            ->each(function (Offer $offer) {
                $offer->closed_at = now();
                $offer->save();

                $offer->seller->notify(new OfferSoldOutEarlyNotification($offer));

                foreach ($offer->buyers as $buyer) {
                    $buyer->notify(new OfferClosedNotification($offer));
                }
            });
    }
}
