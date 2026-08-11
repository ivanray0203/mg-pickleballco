<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives PayMongo payment webhooks. This endpoint is public (providers can't
 * authenticate as a user) and therefore CSRF-exempt, so authenticity rests
 * entirely on verifying the provider signature before trusting any payload.
 *
 * A booking is confirmed only from here, after a verified successful payment —
 * never because a browser reached a success URL.
 */
class PayMongoWebhookController extends Controller
{
    public function __construct(
        private PaymentGateway $gateway,
        private PaymentService $payments,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Paymongo-Signature', '');

        if (! $this->gateway->verifyWebhook($payload, $signature)) {
            Log::warning('Rejected PayMongo webhook with invalid signature.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return response()->json(['message' => 'Invalid payload.'], 422);
        }

        $event = $this->gateway->parseWebhook($decoded);

        // Acknowledge events we don't act on so the provider stops retrying.
        if ($event === null) {
            return response()->json(['message' => 'Ignored.']);
        }

        logger("Success");

        $this->payments->processWebhook($event);

        return response()->json(['message' => 'Processed.']);
    }
}
