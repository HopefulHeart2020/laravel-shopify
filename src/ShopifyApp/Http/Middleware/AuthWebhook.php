<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Osiset\ShopifyApp\Traits\ConfigAccessible;

use function Osiset\ShopifyApp\createHmac;

/**
 * Response for ensuring a proper webhook request.
 */
class AuthWebhook
{
    use ConfigAccessible;

    /**
     * Handle an incoming request to ensure webhook is valid.
     *
     * @param Request  $request The request object.
     * @param \Closure $next    The next action.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $hmac = $request->header('x-shopify-hmac-sha256') ?: '';
        $shop = $request->header('x-shopify-shop-domain');
        $data = $request->getContent();
        $hmacLocal = createHmac(['data' => $data, 'raw' => true, 'encode' => true], $this->getConfig('api_secret'));

        if (!hash_equals($hmac, $hmacLocal) || empty($shop)) {
            // Issue with HMAC or missing shop header
            return Response::make('Invalid webhook signature.', 401);
        }

        // All good, process webhook
        return $next($request);
    }
}
