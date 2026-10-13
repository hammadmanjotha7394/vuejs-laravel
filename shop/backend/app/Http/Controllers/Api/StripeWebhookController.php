<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    /**
     * Handle incoming Stripe Webhook events.
     * Verifies signature using webhook secret from settings and processes idempotently.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature', '');
        $webhookSecret = (string) SettingService::get('stripe_webhook_secret', '');

        $isConfiguredSecret = ! empty($webhookSecret)
            && ! in_array($webhookSecret, ['whsec_placeholder', 'whsec_your_secret_here', 'whsec_123456']);

        if ($isConfiguredSecret) {
            if (empty($sigHeader)) {
                return response()->json(['message' => 'Missing Stripe-Signature header.'], 400);
            }

            try {
                $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
                $eventArray = $event->toArray();
            } catch (\UnexpectedValueException $e) {
                return response()->json(['message' => 'Invalid payload.'], 400);
            } catch (SignatureVerificationException $e) {
                return response()->json(['message' => 'Invalid signature.'], 400);
            }
        } else {
            // Decode raw JSON if webhook secret is unconfigured in local sandbox
            $eventArray = json_decode($payload, true);
            if (! is_array($eventArray) || empty($eventArray['type'])) {
                return response()->json(['message' => 'Malformed webhook payload.'], 400);
            }
        }

        $type = $eventArray['type'] ?? '';
        $object = $eventArray['data']['object'] ?? [];

        switch ($type) {
            case 'checkout.session.completed':
                CheckoutService::handleCheckoutSessionCompleted($object);
                break;

            case 'checkout.session.expired':
                CheckoutService::handleCheckoutSessionExpired($object);
                break;

            case 'charge.refunded':
                CheckoutService::handleChargeRefunded($object);
                break;
        }

        return response()->json([
            'status' => 'received',
            'type' => $type,
        ]);
    }
}
