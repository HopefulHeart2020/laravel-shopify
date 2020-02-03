<?php

namespace OhMyBrew\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;

/**
 * Responsible for ensuring a proper app proxy request.
 */
class AuthProxy
{
    /**
     * Handle an incoming request to ensure it is valid.
     *
     * @param Request  $request The request object.
     * @param \Closure $next    The next action.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Grab the query parameters we need, remove signature since its not part of the signature calculation
        $query = $request->query->all();
        $signature = $query['signature'];
        unset($query['signature']);

        // Build a local signature
        $signatureLocal = ShopifyApp::createHmac(['data' => $query, 'buildQuery' => true]);
        if ($signature !== $signatureLocal || !isset($query['shop'])) {
            // Issue with HMAC or missing shop header
            return Response::make('Invalid proxy signature.', 401);
        }

        // Save shop domain to session
        Session::put('shopify_domain', new ShopDomain($request->get('shop')));

        // All good, process proxy request
        return $next($request);
    }
}
