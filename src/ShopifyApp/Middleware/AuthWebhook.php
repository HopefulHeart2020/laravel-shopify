<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthWebhook
{
    /**
     * Handle an incoming request to ensure webhook is valid.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $hmac = request()->header('x-shopify-hmac-sha256') ?: '';
        $shop = request()->header('x-shopify-shop-domain');
        $data = request()->getContent();

        // From https://help.shopify.com/api/getting-started/webhooks#verify-webhook
        $hmacLocal = base64_encode(hash_hmac('sha256', $data, config('shopify-app.api_secret'), true));
        if (!hash_equals($hmac, $hmacLocal) || empty($shop)) {
            // Issue with HMAC or missing shop header
            abort(401, 'Invalid webhook signature');
        }

        // All good, process webhook
        return $next($request);
    }
}
