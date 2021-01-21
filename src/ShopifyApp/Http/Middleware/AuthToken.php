<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use function Osiset\ShopifyApp\base64url_decode;
use function Osiset\ShopifyApp\base64url_encode;
use Osiset\ShopifyApp\Exceptions\HttpException;
use function Osiset\ShopifyApp\getShopifyConfig;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Services\ShopSession;

class AuthToken
{
    /**
     * The shop session helper.
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * Constructor.
     *
     * @param ShopSession $shopSession The shop session helper.
     *
     * @return void
     */
    public function __construct(ShopSession $shopSession)
    {
        $this->shopSession = $shopSession;
    }

    /**
     * Handle an incoming request.
     *
     * Get the bearer token, validate and verify, and create a
     * session based on the contents.
     *
     * The token is "url safe" (`+` is `-` and `/` is `_`) base64.
     *
     * @param Request  $request The request object.
     * @param \Closure $next    The next action.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $now = time();

        $token = $request->bearerToken();

        if (! $token) {
            throw new HttpException('Missing authentication token', 401);
        }

        // The header is fixed so include it here
        if (! preg_match('/^eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9\.[A-Za-z0-9\-\_=]+\.[A-Za-z0-9\-\_\=]*$/', $token)) {
            throw new HttpException('Malformed token', 400);
        }

        if (! $this->checkSignature($token)) {
            throw new HttpException('Unable to verify signature', 400);
        }

        $parts = explode('.', $token);
        $body = json_decode(base64url_decode($parts[1]));

        if (! $body ||
            ! isset($body->iss) ||
            ! isset($body->dest) ||
            ! isset($body->aud) ||
            ! isset($body->sub) ||
            ! isset($body->exp) ||
            ! isset($body->nbf) ||
            ! isset($body->iat) ||
            ! isset($body->jti) ||
            ! isset($body->sid)) {
            throw new HttpException('Malformed token', 400);
        }

        if (($now > $body->exp) || ($now < $body->nbf) || ($now < $body->iat)) {
            throw new HttpException('Expired token', 403);
        }

        if (! stristr($body->iss, $body->dest)) {
            throw new HttpException('Invalid token', 400);
        }

        if ($body->aud !== getShopifyConfig('api_key')) {
            throw new HttpException('Invalid token', 400);
        }

        // All is well, login
        $url = parse_url($body->dest);

        $this->shopSession->make(ShopDomain::fromNative($url['host']));
        $this->shopSession->setSessionToken($body->sid);

        return $next($request);
    }

    /**
     * Checks the validity of the signature sent with the token.
     *
     * @param string $token The token to check.
     *
     * @return bool
     */
    private function checkSignature($token)
    {
        // Get the signature data
        $parts = explode('.', $token);
        $signature = array_pop($parts);
        $check = implode('.', $parts);

        // Get the shop
        $shop = null;
        $body = json_decode(base64url_decode($parts[1]));
        if (isset($body->dest)) {
            $url = parse_url($body->dest);
            $shop = isset($url['host']) ? $url['host'] : null;
        }

        $secret = getShopifyConfig('api_secret', $shop);
        $hmac = hash_hmac('sha256', $check, $secret, true);
        $encoded = base64url_encode($hmac);

        return $encoded === $signature;
    }
}
