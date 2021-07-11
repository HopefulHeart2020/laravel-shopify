<?php

namespace Osiset\ShopifyApp\Objects\Values;

use Funeralzone\ValueObjects\NullTrait;
use Osiset\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;

/**
 * Value object for access token (null).
 */
final class NullAccessToken implements AccessTokenValue
{
    use NullTrait;

    /**
     * {@inheritdoc}
     */
    public function isEmpty(): bool
    {
        return true;
    }
}
