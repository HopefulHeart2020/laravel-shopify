<?php

namespace OhMyBrew\ShopifyApp\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Jenssegers\Agent\Agent;
use OhMyBrew\ShopifyApp\Models\Shop;
use stdClass;

/**
 * Responsible for handling session retreival and storage.
 */
class ShopSession
{
    /**
     * The session key for Shopify domain.
     *
     * @var string
     */
    const DOMAIN = 'shopify_domain';

    /**
     * The session key for Shopify associated user.
     *
     * @var string
     */
    const USER = 'shopify_user';

    /**
     * The (session/database) key for Shopify access token.
     *
     * @var string
     */
    const TOKEN = 'shopify_token';

    /**
     * The offline grant key.
     *
     * @var string
     */
    const GRANT_OFFLINE = 'offline';

    /**
     * The per-user grant key.
     *
     * @var string
     */
    const GRANT_PERUSER = 'per-user';

    /**
     * The shop.
     *
     * @var \OhMyBrew\ShopifyApp\Models\Shop|null
     */
    protected $shop;

    /**
     * Constructor for shop session class.
     *
     * @param object|null $shop The shop.
     *
     * @return self
     */
    public function __construct($shop = null)
    {
        $this->setShop($shop);
        $this->setCookiePolicy();
    }

    /**
     * Sets the shop.
     *
     * @param object|null $shop The shop.
     *
     * @return self
     */
    public function setShop($shop = null)
    {
        $this->shop = $shop;

        return $this;
    }

    /**
     * Determines the type of access.
     *
     * @return string
     */
    public function getType()
    {
        $config = Config::get('shopify-app.api_grant_mode');
        if ($config === self::GRANT_PERUSER) {
            return self::GRANT_PERUSER;
        }

        return self::GRANT_OFFLINE;
    }

    /**
     * Determines if the type of access matches.
     *
     * @param string $type The type of access to check.
     *
     * @return string
     */
    public function isType(string $type)
    {
        return $this->getType() === $type;
    }

    /**
     * Sets the Shopify domain to session.
     * `expire_on_close` must be set to avoid issue of cookies being deleted too early.
     *
     * @param string $shopDomain The Shopify domain.
     *
     * @return self
     */
    public function setDomain(string $shopDomain)
    {
        $this->fixLifetime();
        Session::put(self::DOMAIN, $shopDomain);

        return $this;
    }

    /**
     * Gets the Shopify domain in session.
     *
     * @return void
     */
    public function getDomain()
    {
        return Session::get(self::DOMAIN);
    }

    /**
     * Stores the access token and user (if any).
     * Uses database for acess token if it was an offline authentication.
     *
     * @param stdClass $access
     *
     * @return self
     */
    public function setAccess(stdClass $access)
    {
        // Grab the token
        $token = $access->access_token;

        // Per-User
        if (property_exists($access, 'associated_user')) {
            // We have a user, so access will live only in session
            $this->user = $access->associated_user;

            $this->fixLifetime();
            Session::put(self::USER, $this->user);
            Session::put(self::TOKEN, $token);

            return $this;
        }

        // Offline
        $this->shop->{self::TOKEN} = $token;
        $this->shop->save();

        return $this;
    }

    /**
     * Gets the access token in use.
     *
     * @param bool $strict Return the token matching the grant type (default: use either).
     *
     * @return string
     */
    public function getToken(bool $strict = false)
    {
        // Tokens
        $tokens = [
            self::GRANT_PERUSER => Session::get(self::TOKEN),
            self::GRANT_OFFLINE => $this->shop->{self::TOKEN},
        ];

        if ($strict) {
            // We need the token matching the type
            return $tokens[$this->getType()];
        }

        // We need a token either way...
        return $tokens[self::GRANT_PERUSER] ?? $tokens[self::GRANT_OFFLINE];
    }

    /**
     * Gets the associated user (if any).
     *
     * @return stfClass|null
     */
    public function getUser()
    {
        return Session::get(self::USER);
    }

    /**
     * Determines if there is an associated user.
     *
     * @return bool
     */
    public function hasUser()
    {
        return $this->getUser() !== null;
    }

    /**
     * Forgets anything in session.
     *
     * @return self
     */
    public function forget()
    {
        $keys = [self::DOMAIN, self::USER, self::TOKEN];
        foreach ($keys as $key) {
            Session::forget($key);
        }

        return $this;
    }

    /**
     * Checks if the package has everything it needs in session.
     *
     * @return bool
     */
    public function isValid()
    {
        // No token set or domain in session?
        $result = !empty($this->getToken(true))
            && $this->getDomain() !== null
            && $this->getDomain() == $this->shop->shopify_domain;

        return $result;
    }

    /**
     * Fixes the lifetime of the session.
     *
     * @return void
     */
    protected function fixLifetime()
    {
        Config::set('session.expire_on_close', true);
    }

    /**
     * Sets the cookie policy.
     *
     * From Chrome 80+ there is a new requirement that the SameSite
     * cookie flag be set to `none` and the cookies be marked with
     * `secure`.
     *
     * Reference: https://www.chromium.org/updates/same-site/incompatible-clients
     *
     * Enables SameSite none and Secure cookies on:
     *
     * - Chrome v67+
     * - Safari on OSX 10.14+
     * - iOS 13+
     * - UCBrowser 12.13+
     *
     * @return null
     */
    public function setCookiePolicy()
    {
        if ($this->checkSameSiteNoneCompatible()) {
            config([
                'session.secure'    => true,
                'session.same_site' => 'none',
            ]);
        }
    }

    /**
     * Checks to see if the current browser session should be
     * using the SameSite=none cookie policy.
     *
     * @return bool
     */
    private function checkSameSiteNoneCompatible()
    {
        $compatible = false;

        $this->agent = new Agent();

        try {
            $browser = $this->getBrowserDetails();
            $platform = $this->getPlatformDetails();

            if ($this->agent->is('Chrome') && $browser['major'] >= 67) {
                $compatible = true;
            }

            if ($this->agent->is('iOS') && $platform['major'] > 12) {
                $compatible = true;
            }

            if ($this->agent->is('OS X') &&
                ($this->agent->is('Safari') && !$this->agent->is('iOS')) &&
                $platform['float'] > 10.14
            ) {
                $compatible = true;
            }

            if ($this->agent->is('UCBrowser') &&
                $browser['float'] > 12.13
            ) {
                $compatible = true;
            }

            return $compatible;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Returns details about the current web browser.
     *
     * @return array
     */
    private function getBrowserDetails()
    {
        $version = $this->agent->version($this->agent->browser());
        $pieces = explode('.', str_replace('_', '.', $version));

        return [
            'major' => $pieces[0],
            'minor' => $pieces[1],
            'float' => (float) sprintf('%s.%s', $pieces[0], $pieces[1]),
        ];
    }

    /**
     * Returns details about the current operating system.
     *
     * @return array
     */
    private function getPlatformDetails()
    {
        $version = $this->agent->version($this->agent->platform());
        $pieces = explode('.', str_replace('_', '.', $version));

        return [
            'major' => $pieces[0],
            'minor' => $pieces[1],
            'float' => (float) sprintf('%s.%s', $pieces[0], $pieces[1]),
        ];
    }
}
