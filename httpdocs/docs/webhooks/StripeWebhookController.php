<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\{Organization,Subscription,Plan};

class StripeWebhookController extends Controller {
  public function handle(Request $request){
    $payload = $request->getContent();
    $sig = $request->header('Stripe-Signature');
    // Optionally verify with STRIPE_WEBHOOK_SECRET using \Stripe\Webhook

    $event = json_decode($payload, true);
    Log::info('Stripe webhook', ['type'=>$event['type'] ?? 'unknown']);

    switch($event['type'] ?? ''){
      case 'checkout.session.completed':
      case 'customer.subscription.created':
      case 'customer.subscription.updated':
      case 'invoice.paid':
        // TODO: map customer to organization/user and update Subscription model
        break;
      case 'invoice.payment_failed':
        // mark as past_due
        break;
    }
    return new Response('ok', 200);
  }
}
