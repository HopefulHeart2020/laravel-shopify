<?php

namespace OhMyBrew\ShopifyApp\Contracts\Queries;

use Illuminate\Support\Collection;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;

/**
 * Reprecents a queries for shops.
 */
interface Shop
{
    /**
     * Get by ID.
     *
     * @param ShopId $shopId      The shop ID.
     * @param array  $with        The relations to eager load.
     * @param bool   $withTrashed Include trashed shops?
     *
     * @return IShopModel|null
     */
    public function getById(ShopId $shopId, array $with = [], bool $withTrashed = false): ?IShopModel;

    /**
     * Get by domain.
     *
     * @param ShopDomain $domain      The shop domain.
     * @param array      $with        The relations to eager load.
     * @param bool       $withTrashed Include trashed shops?
     *
     * @return IShopModel|null
     */
    public function getByDomain(ShopDomainValue $domain, array $with = [], bool $withTrashed = false): ?IShopModel;

    /**
     * Get all records.
     *
     * @param array $with The relations to eager load.
     *
     * @return Collection IShopModel[]
     */
    public function getAll(array $with = []): Collection;
}
