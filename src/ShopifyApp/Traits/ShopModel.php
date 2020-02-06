<?php

namespace OhMyBrew\ShopifyApp\Traits;

use OhMyBrew\ShopifyApp\Storage\Models\Plan;
use Illuminate\Database\Eloquent\SoftDeletes;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Storage\Models\Charge;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OhMyBrew\ShopifyApp\Objects\Values\AccessToken;
use OhMyBrew\ShopifyApp\Storage\Scopes\Namespacing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OhMyBrew\ShopifyApp\Objects\Values\NullablePlanId;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use OhMyBrew\ShopifyApp\Storage\Models\Charge as ChargeModel;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;

/**
 * Responsible for reprecenting a shop record.
 */
trait ShopModel
{
    use SoftDeletes;

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(new Namespacing());
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ShopId
    {
        return new ShopId($this->id);
    }

    /**
     * {@inheritdoc}
     */
    public function getDomain(): ShopDomainValue
    {
        return new ShopDomain($this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): AccessTokenValue
    {
        return new AccessToken($this->password);
    }

    /**
     * {@inheritdoc}
     */
    public function isGrandfathered(): bool
    {
        return ((bool) $this->shopify_grandfathered) === true;
    }

    /**
     * {@inheritdoc}
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCharges(): bool
    {
        return $this->charges->isNotEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * {@inheritdoc}
     */
    public function isFreemium(): bool
    {
        return ((bool) $this->shopify_freemium) === true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOfflineAccess(): bool
    {
        return !$this->getToken()->isNull();
    }

    /**
     * {@inheritdoc}
     */
    public function api(bool $returnInstance = false)
    {
        // Make an instance
        $instance = resolve(IApiHelper::class)->make();
        $api = $instance->getApi();

        // Set the session
        $api->setSession(
            $this->getDomain()->toNative(),
            $this->getToken()->toNative()
        );
        
        return $returnInstance ? $instance : $api;
    }

    /**
     * {@inheritdoc}
     */
    public function planCharge(NullablePlanId $planId = null): ?ChargeModel
    {
        return $this
            ->charges()
            ->withTrashed()
            ->whereIn('type', [ChargeType::RECURRING()->toNative(), ChargeType::CHARGE()->toNative()])
            ->where('plan_id', $planId ?? $this->plan_id)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
