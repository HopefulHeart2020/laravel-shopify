<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Services\AuthShopHandler;
use OhMyBrew\ShopifyApp\Services\ShopSession;

/**
 * Response for ensuring an authenticated shop.
 */
class AuthShop
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $validation = $this->validateShop($request);
        if ($validation !== true) {
            return $validation;
        }

        return $next($request);
    }

    /**
     * Checks we have a valid shop.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool|\Illuminate\Http\RedirectResponse
     */
    protected function validateShop(Request $request)
    {
        // Setup the session service
        $session = new ShopSession();

        $shopDomain = $this->getShopDomain($request, $session);

        // Get the shop based on domain and update the session service
        $shopModel = Config::get('shopify-app.shop_model');
        $shop = $shopModel::withTrashed()
            ->where(['shopify_domain' => $shopDomain])
            ->first();

        $session->setShop($shop);

        $flowType = null;
        if ($shop === null || $shop->trashed()) {
            // We need to do a full flow
            $flowType = AuthShopHandler::FLOW_FULL;
        } elseif (!$session->isValid()) {
            // Just a session issue, do a partial flow if we can...
            $flowType = $session->isType(ShopSession::GRANT_PERUSER) ?
                AuthShopHandler::FLOW_FULL :
                AuthShopHandler::FLOW_PARTIAL;
        }

        if ($flowType !== null) {
            // We have a bad session
            return $this->handleBadSession(
                $flowType,
                $session,
                $request,
                $shopDomain
            );
        }

        // Everything is fine!
        return true;
    }

    /**
     * Grab the shop's myshopify domain from query, referer or session.
     *
     * Getting the domain for the shop from session is unreliable
     * because if 2 shops have the same app open in the same browser
     * (e.g. someone is managing the same app on 2 stores at the same
     * time) then the sessions can bleed into each other due to the
     * cookies being run on the same domain (the domain of the app,
     * not the individual shops admin dashboard).
     *
     * To get around this select a domain based on other information
     * available, and make sure to verify the input before use. This is
     * still not 100% reliable.
     *
     * Order of precedence is:
     *
     *  - GET variable
     *  - Referer
     *  - Session
     *
     * @param \Illuminate\Http\Request                  $request
     * @param \OhMyBrew\ShopifyApp\Services\ShopSession $session
     *
     * @throws Exception
     *
     * @return bool|string
     */
    private function getShopDomain(Request $request, ShopSession $session)
    {
        // Query variable is highest priority
        $shopDomainParam = $request->get('shop');
        if ($shopDomainParam) {
            return ShopifyApp::sanitizeShopDomain($shopDomainParam);
        }

        // Then the value in the referer header (if validated)
        // See issue https://github.com/ohmybrew/laravel-shopify/issues/295
        $shopRefererParam = $this->getRefererDomain($request);
        if ($shopRefererParam) {
            return ShopifyApp::sanitizeShopDomain($shopRefererParam);
        }

        // If neither are available then pull from the session
        $shopDomainSession = $session->getDomain();
        if ($shopDomainSession) {
            return ShopifyApp::sanitizeShopDomain($shopDomainSession);
        }

        // No domain :(
        throw new Exception('Unable to get shop domain.');
    }

    /**
     * Get the referer shopify domain from the request and validate.
     *
     * It is dangerous to blindly trust user input so we need to
     * check and confirm the validity upfront before we return the
     * value to anything.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool|string
     */
    private function getRefererDomain(Request $request)
    {
        // Extract the referer
        $referer = $request->header('referer');

        if (!$referer) {
            return false;
        }

        // Get the values of the referer query params as an array
        $url = parse_url($referer, PHP_URL_QUERY);
        parse_str($url, $refererQueryParams);

        if (!$refererQueryParams) {
            return false;
        }

        if (!isset($refererQueryParams['shop']) || !isset($refererQueryParams['hmac'])) {
            return false;
        }

        // Make sure there is no param spoofing attempt
        if (ShopifyApp::api()->verifyRequest($refererQueryParams)) {
            return $refererQueryParams['shop'];
        }

        return false;
    }

    /**
     * Handles a bad shop session.
     *
     * @param string                                    $type       The auth flow to perform.
     * @param \OhMyBrew\ShopifyApp\Services\ShopSession $session    The session service for the shop.
     * @param \Illuminate\Http\Request                  $request    The incoming request.
     * @param string|null                               $shopDomain The incoming shop domain.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleBadSession(
        string $type,
        ShopSession $session,
        Request $request,
        string $shopDomain = null
    ) {
        // Clear all session variables (domain, token, user, etc)
        $session->forget();

        // Set the return-to path so we can redirect after successful authentication
        Session::put('return_to', $request->fullUrl());

        // Depending on the type of grant mode, we need to do a full auth or partial
        return Redirect::route(
            'authenticate',
            array_merge(
                $request->all(),
                [
                    'type' => $type,
                    'shop' => $shopDomain,
                ]
            )
        );
    }
}
