<?php

namespace Osiset\ShopifyApp;

/**
 * HMAC creation helper.
 *
 * @param array  $opts   The options for building the HMAC.
 * @param string $secret The app secret key.
 *
 * @return string
 */
function createHmac(array $opts, string $secret): string
{
    // Setup defaults
    $data = $opts['data'];
    $raw = $opts['raw'] ?? false;
    $buildQuery = $opts['buildQuery'] ?? false;
    $buildQueryWithJoin = $opts['buildQueryWithJoin'] ?? false;
    $encode = $opts['encode'] ?? false;

    if ($buildQuery) {
        //Query params must be sorted and compiled
        ksort($data);
        $queryCompiled = [];
        foreach ($data as $key => $value) {
            $queryCompiled[] = "{$key}=".(is_array($value) ? implode(',', $value) : $value);
        }
        $data = implode(
            ($buildQueryWithJoin ? '&' : ''),
            $queryCompiled
        );
    }

    // Create the hmac all based on the secret
    $hmac = hash_hmac('sha256', $data, $secret, $raw);

    // Return based on options
    return $encode ? base64_encode($hmac) : $hmac;
}

/**
 * Parse query strings the same way as Rack::Until in Ruby. (This is a port from Rack 2.3.0.).
 *
 * From Shopify's docs, they use Rack::Util.parse_query, which does *not* parse array parameters properly.
 * Array parameters such as `name[]=value1&name[]=value2` becomes `['name[]' => ['value1', 'value2']] in Shopify.
 * See: https://github.com/rack/rack/blob/f9ad97fd69a6b3616d0a99e6bedcfb9de2f81f6c/lib/rack/query_parser.rb#L36
 *
 * @param string $qs The query string.
 * @param string $d  The delimiter.
 *
 * @return mixed
 */
function parseQueryString(string $qs, string $d = null): array
{
    $COMMON_SEP = [';' => '/[;]\s*/', ';,' => '/[;,]\s*/', '&' => '/[&]\s*/'];
    $DEFAULT_SEP = '/[&;]\s*/';

    $params = [];
    $split = preg_split($d ? ($COMMON_SEP[$d] || '/['.$d.']\s*/') : $DEFAULT_SEP, $qs ?? '');

    foreach ($split as $p) {
        if (! $p) {
            continue;
        }

        [$k, $v] = strpos($p, '=') !== false ? explode('=', $p, 2) : [$p, null];

        $k = urldecode($k);
        $v = $v !== null ? urldecode($v) : $v;

        if (isset($params[$k])) {
            $cur = $params[$k];

            if (is_array($cur)) {
                $params[$k][] = $v;
            } else {
                $params[$k] = [$cur, $v];
            }
        } else {
            $params[$k] = $v;
        }
    }

    return $params;
}

/**
 * URL-safe Base64 encoding.
 *
 * Replaces `+` with `-` and `/` with `_` and trims padding `=`.
 *
 * @param string $data The data to be encoded.
 *
 * @return string
 */
function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * URL-safe Base64 decoding.
 *
 * Replaces `-` with `+` and `_` with `/`.
 *
 * Adds padding `=` if needed.
 *
 * @param string $data The data to be decoded.
 *
 * @return string
 */
function base64url_decode($data)
{
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

/**
 * Checks if the route should be registered or not.
 *
 * @param string     $routeToCheck The route name to check.
 * @param bool|array $routesToExclude The routes which are to be excluded.
 *
 * @return bool
 */
function registerPackageRoute(string $routeToCheck, $routesToExclude): bool
{
    if ($routesToExclude === false) {
        return true;
    }

    if ($routesToExclude === true) {
        throw new \LogicException('Excluded routes can be false, or an array');
    }

    if (is_array($routesToExclude) === false) {
        throw new \LogicException('Excluded routes must be an array');
    }

    return in_array($routeToCheck, $routesToExclude, true) === false;
}
