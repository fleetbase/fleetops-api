<?php

namespace Fleetbase\FleetOps\Observers;

use Fleetbase\FleetOps\Models\PurchaseRate;
use Fleetbase\FleetOps\Models\Transaction;
use Fleetbase\FleetOps\Models\TransactionItem;

class PurchaseRateObserver
{
    /**
     * Handle the PurchaseRate "creating" event.
     * Create transactions accordingly.
     *
     * @param  \Fleetbase\FleetOps\Models\PurchaseRate  $purchaseRate
     * @return void
     */
    public function creating(PurchaseRate $purchaseRate)
    {
        $purchaseRate->load(['serviceQuote.items', 'serviceQuote.serviceRate']);

        // create transaction and transaction items
        $transaction = Transaction::create([
            'company_uuid' => $purchaseRate->company_uuid ?? session('company'),
            'customer_uuid' => $purchaseRate->customer_uuid,
            'customer_type' => $purchaseRate->customer_type,
            'gateway_transaction_id' => $purchaseRate->getMeta('transaction_id') ?? Transaction::generateNumber(),
            'gateway' => 'internal',
            'amount' => $purchaseRate->serviceQuote->amount ?? 0,
            'currency' => $purchaseRate->serviceQuote->currency ?? 'SGD',
            'description' => 'Dispatch order',
            'type' => 'dispatch',
            'status' => 'success',
        ]);

        if (isset($purchaseRate->serviceQuote)) {
            $purchaseRate->serviceQuote->items->each(function ($serviceQuoteItem) use ($transaction, $purchaseRate) {
                TransactionItem::create([
                    'transaction_uuid' => $transaction->uuid,
                    'amount' => $serviceQuoteItem->amount ?? 0,
                    'currency' => $purchaseRate->serviceQuote->currency ?? 'SGD',
                    'details' => $serviceQuoteItem->details ?? 'Internal dispatch',
                    'code' => $serviceQuoteItem->code ?? 'internal',
                ]);
            });
        }

        $purchaseRate->transaction_uuid = $transaction->uuid;
    }
}
