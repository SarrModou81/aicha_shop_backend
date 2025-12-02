<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Paiement;
use App\Models\Notification;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class PaiementController extends Controller
{
    /**
     * Initier un paiement
     */
    public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commande_id' => 'required|exists:commandes,id',
            'payment_method' => 'required|in:wave,orange_money,free_money,carte,especes',
            'phone_number' => 'required_if:payment_method,wave,orange_money,free_money|string',
            'card_number' => 'required_if:payment_method,carte|string',
            'card_expiry' => 'required_if:payment_method,carte|string',
            'card_cvv' => 'required_if:payment_method,carte|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $commande = Commande::with('user')->findOrFail($request->commande_id);

        // Vérifier que la commande appartient à l'utilisateur
        if ($commande->user_id !== auth()->id()) {
            return response()->json(['message' => 'Commande non autorisée'], 403);
        }

        // Vérifier que la commande n'a pas déjà été payée
        if ($commande->paiement && $commande->paiement->status === 'paye') {
            return response()->json(['message' => 'Cette commande a déjà été payée'], 400);
        }

        // Créer ou mettre à jour le paiement
        $paiement = Paiement::updateOrCreate(
            ['commande_id' => $commande->id],
            [
                'payment_method' => $request->payment_method,
                'amount' => $commande->total,
                'status' => 'en_attente',
                'payment_details' => json_encode([
                    'phone_number' => $request->phone_number ?? null,
                    'card_last4' => $request->payment_method === 'carte' ? substr($request->card_number, -4) : null
                ])
            ]
        );

        // Traiter le paiement selon la méthode
        $result = match($request->payment_method) {
            'wave' => $this->processWavePayment($paiement, $request),
            'orange_money' => $this->processOrangeMoneyPayment($paiement, $request),
            'free_money' => $this->processFreeMoneyPayment($paiement, $request),
            'carte' => $this->processCardPayment($paiement, $request),
            'especes' => $this->processEspeces($paiement),
            default => ['success' => false, 'message' => 'Méthode de paiement non supportée']
        };

        if ($result['success']) {
            $paiement->status = 'paye';
            $paiement->transaction_id = $result['transaction_id'] ?? null;
            $paiement->save();

            // Mettre à jour le statut de la commande
            $commande->status = 'confirmee';
            $commande->save();

            // Créer notification
            Notification::create([
                'user_id' => $commande->user_id,
                'type' => 'payment_success',
                'message' => "Votre paiement pour la commande #{$commande->order_number} a été effectué avec succès"
            ]);

            // Logger l'action
            SecurityLog::logAction(
                auth()->id(),
                'payment_success',
                "Paiement réussi pour la commande #{$commande->order_number}",
                'info',
                ['amount' => $commande->total, 'method' => $request->payment_method]
            );

            return response()->json([
                'message' => 'Paiement effectué avec succès',
                'paiement' => $paiement,
                'commande' => $commande
            ]);
        }

        // En cas d'échec
        $paiement->status = 'echec';
        $paiement->save();

        // Logger l'échec
        SecurityLog::logAction(
            auth()->id(),
            'payment_failed',
            "Échec du paiement pour la commande #{$commande->order_number}",
            'warning',
            ['amount' => $commande->total, 'method' => $request->payment_method, 'error' => $result['message']]
        );

        return response()->json([
            'message' => $result['message'] ?? 'Échec du paiement',
            'paiement' => $paiement
        ], 400);
    }

    /**
     * Traiter le paiement Wave
     */
    protected function processWavePayment($paiement, $request)
    {
        try {
            // Configuration Wave API
            $waveApiKey = env('WAVE_API_KEY');
            $waveApiUrl = env('WAVE_API_URL', 'https://api.wave.com/v1');

            // Appel API Wave
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $waveApiKey,
                'Content-Type' => 'application/json'
            ])->post($waveApiUrl . '/checkout/sessions', [
                'amount' => $paiement->amount,
                'currency' => 'XOF', // Franc CFA
                'phone_number' => $request->phone_number,
                'reference' => $paiement->commande_id,
                'description' => "Paiement commande #{$paiement->commande->order_number}"
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'transaction_id' => $data['id'] ?? null,
                    'message' => 'Paiement Wave initié avec succès'
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur lors du paiement Wave: ' . $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur technique: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Traiter le paiement Orange Money
     */
    protected function processOrangeMoneyPayment($paiement, $request)
    {
        try {
            // Configuration Orange Money API
            $omApiKey = env('ORANGE_MONEY_API_KEY');
            $omApiUrl = env('ORANGE_MONEY_API_URL', 'https://api.orange.com/mobile-money/v1');

            // Appel API Orange Money
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $omApiKey,
                'Content-Type' => 'application/json'
            ])->post($omApiUrl . '/payments', [
                'amount' => $paiement->amount,
                'currency' => 'XOF',
                'subscriberMsisdn' => $request->phone_number,
                'orderId' => $paiement->commande_id,
                'description' => "Paiement commande #{$paiement->commande->order_number}"
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'transaction_id' => $data['transactionId'] ?? null,
                    'message' => 'Paiement Orange Money initié avec succès'
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur lors du paiement Orange Money: ' . $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur technique: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Traiter le paiement Free Money
     */
    protected function processFreeMoneyPayment($paiement, $request)
    {
        try {
            // Configuration Free Money API
            $fmApiKey = env('FREE_MONEY_API_KEY');
            $fmApiUrl = env('FREE_MONEY_API_URL', 'https://api.freemoney.sn/v1');

            // Appel API Free Money
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $fmApiKey,
                'Content-Type' => 'application/json'
            ])->post($fmApiUrl . '/transactions', [
                'amount' => $paiement->amount,
                'currency' => 'XOF',
                'phone' => $request->phone_number,
                'reference' => $paiement->commande_id,
                'description' => "Paiement commande #{$paiement->commande->order_number}"
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'transaction_id' => $data['transaction_id'] ?? null,
                    'message' => 'Paiement Free Money initié avec succès'
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur lors du paiement Free Money: ' . $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur technique: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Traiter le paiement par carte
     */
    protected function processCardPayment($paiement, $request)
    {
        try {
            // Utiliser Stripe ou tout autre processeur de carte
            $stripeKey = env('STRIPE_SECRET_KEY');

            // Exemple avec Stripe (nécessite stripe/stripe-php)
            // \Stripe\Stripe::setApiKey($stripeKey);
            // $charge = \Stripe\Charge::create([
            //     'amount' => $paiement->amount * 100, // en centimes
            //     'currency' => 'xof',
            //     'source' => $request->card_token,
            //     'description' => "Paiement commande #{$paiement->commande->order_number}"
            // ]);

            // Pour l'instant, simulation
            return [
                'success' => true,
                'transaction_id' => 'CARD_' . uniqid(),
                'message' => 'Paiement par carte effectué avec succès'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur technique: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Traiter le paiement en espèces (à la livraison)
     */
    protected function processEspeces($paiement)
    {
        // Paiement en attente jusqu'à la livraison
        return [
            'success' => true,
            'transaction_id' => 'CASH_' . uniqid(),
            'message' => 'Paiement en espèces à la livraison'
        ];
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkPaymentStatus($paiementId)
    {
        $paiement = Paiement::with('commande')->findOrFail($paiementId);

        // Vérifier que le paiement appartient à l'utilisateur
        if ($paiement->commande->user_id !== auth()->id()) {
            return response()->json(['message' => 'Paiement non autorisé'], 403);
        }

        return response()->json($paiement);
    }

    /**
     * Webhook pour les notifications de paiement (Wave, Orange Money, etc.)
     */
    public function paymentWebhook(Request $request)
    {
        // Vérifier la signature du webhook
        // ...

        $transactionId = $request->input('transaction_id');
        $status = $request->input('status');

        $paiement = Paiement::where('transaction_id', $transactionId)->first();

        if ($paiement) {
            $paiement->status = $status === 'success' ? 'paye' : 'echec';
            $paiement->save();

            if ($status === 'success') {
                $commande = $paiement->commande;
                $commande->status = 'confirmee';
                $commande->save();

                Notification::create([
                    'user_id' => $commande->user_id,
                    'type' => 'payment_success',
                    'message' => "Votre paiement pour la commande #{$commande->order_number} a été confirmé"
                ]);
            }
        }

        return response()->json(['message' => 'Webhook traité']);
    }

    /**
     * Rembourser un paiement
     */
    public function refundPayment(Request $request, $paiementId)
    {
        $paiement = Paiement::with('commande')->findOrFail($paiementId);

        // Vérifier les permissions (admin seulement)
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if ($paiement->status !== 'paye') {
            return response()->json(['message' => 'Ce paiement ne peut pas être remboursé'], 400);
        }

        // Traiter le remboursement selon la méthode de paiement
        // ...

        $paiement->status = 'rembourse';
        $paiement->save();

        // Mettre à jour la commande
        $commande = $paiement->commande;
        $commande->status = 'annulee';
        $commande->save();

        // Notification
        Notification::create([
            'user_id' => $commande->user_id,
            'type' => 'payment_refunded',
            'message' => "Votre paiement pour la commande #{$commande->order_number} a été remboursé"
        ]);

        return response()->json([
            'message' => 'Paiement remboursé avec succès',
            'paiement' => $paiement
        ]);
    }
}
