<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $event = json_decode($request->getContent(), true) ?: [];
        $type = $event['type'] ?? 'unknown';
        $object = $event['data']['object'] ?? [];

        Log::info('Stripe webhook received', ['type' => $type]);

        $subscription = $this->findSubscription($object);

        if ($subscription) {
            match ($type) {
                'checkout.session.completed',
                'customer.subscription.created',
                'customer.subscription.updated',
                'invoice.paid' => $subscription->update([
                    'status' => 'active',
                    'stripe_id' => $object['subscription'] ?? $object['id'] ?? $subscription->stripe_id,
                    'current_period_end' => isset($object['current_period_end'])
                        ? now()->createFromTimestamp($object['current_period_end'])
                        : $subscription->current_period_end,
                ]),
                'invoice.payment_failed' => $subscription->update(['status' => 'past_due']),
                'customer.subscription.deleted' => $subscription->update(['status' => 'canceled']),
                default => null,
            };
        }

        return new Response('ok', 200);
    }

    protected function findSubscription(array $object): ?Subscription
    {
        $stripeSubscriptionId = $object['subscription'] ?? $object['id'] ?? null;

        if ($stripeSubscriptionId) {
            $subscription = Subscription::where('stripe_id', $stripeSubscriptionId)->first();
            if ($subscription) {
                return $subscription;
            }
        }

        $customerId = $object['customer'] ?? null;
        if (! $customerId) {
            return null;
        }

        $user = User::where('stripe_id', $customerId)->first();
        if (! $user?->organization_id) {
            return null;
        }

        return Subscription::where('organization_id', $user->organization_id)->latest('id')->first();
    }
}
