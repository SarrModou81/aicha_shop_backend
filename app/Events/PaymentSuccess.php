<?php

namespace App\Events;

use App\Models\Paiement;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccess
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;

    /**
     * Create a new event instance.
     */
    public function __construct(Paiement $payment)
    {
        $this->payment = $payment;
    }
}
