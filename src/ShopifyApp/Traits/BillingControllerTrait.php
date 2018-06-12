<?php

namespace OhMyBrew\ShopifyApp\Traits;

use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Libraries\BillingPlan;
use OhMyBrew\ShopifyApp\Models\Charge;
use Carbon\Carbon;

trait BillingControllerTrait
{
    /**
     * Redirects to billing screen for Shopify.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get the confirmation URL
        $plan = new BillingPlan(ShopifyApp::shop(), $this->chargeType());
        $plan->setDetails($this->planDetails());

        // Do a fullpage redirect
        return view('shopify-app::billing.fullpage_redirect', [
            'url' => $plan->getConfirmationUrl(),
        ]);
    }

    /**
     * Processes the response from the customer.
     *
     * @return void
     */
    public function process()
    {
        // Setup the shop and get the charge ID passed in
        $shop = ShopifyApp::shop();
        $chargeId = request('charge_id');

        // Setup the plan and get the charge
        $plan = new BillingPlan($shop, $this->chargeType());
        $plan->setChargeId($chargeId);
        $status = $plan->getCharge()->status;

        // Grab the plan detailed used
        $planDetails = $this->planDetails();
        unset($planDetails['return_url']);

        // Create a charge (regardless of the status)
        $charge = new Charge();
        $charge->type = $this->chargeType() === 'recurring' ? Charge::CHARGE_RECURRING : Charge::CHARGE_ONETIME;
        $charge->charge_id = $chargeId;
        $charge->status = $status;

        // Check the customer's answer to the billing
        if ($status === 'accepted') {
            // Activate and add details to our charge
            $response = $plan->activate();
            $charge->status = $response->status;
            $charge->billing_on = $response->billing_on;
            $charge->trial_ends_on = $response->trial_ends_on;
            $charge->activated_on = $response->activated_on;
        } else {
            // Customer declined the charge
            $charge->cancelled_on = Carbon::today()->format('Y-m-d');
        }

        // Merge in the plan details since the fields match the database columns
        foreach ($planDetails as $key => $value) {
            $charge->{$key} = $value;
        }

        // Save and link to the shop
        $shop->charges()->save($charge);

        if ($status === 'declined') {
            // Show the error... don't allow access
            return abort(403, 'It seems you have declined the billing charge for this application');
        }

        // All good... go to homepage of app
        return redirect()->route('home');
    }

    /**
     * Base plan to use for billing.
     * Setup as a function so its patchable.
     *
     * @return array
     */
    protected function planDetails()
    {
        $plan = [
            'name'       => config('shopify-app.billing_plan'),
            'price'      => config('shopify-app.billing_price'),
            'test'       => config('shopify-app.billing_test'),
            'trial_days' => config('shopify-app.billing_trial_days'),
            'return_url' => url(config('shopify-app.billing_redirect')),
        ];

        // Handle capped amounts for UsageCharge API
        if (config('shopify-app.billing_capped_amount')) {
            $plan['capped_amount'] = config('shopify-app.billing_capped_amount');
            $plan['terms'] = config('shopify-app.billing_terms');
        }

        return $plan;
    }

    /**
     * Base charge type (single or recurring).
     * Setup as a function so its patchable.
     *
     * @return string
     */
    protected function chargeType()
    {
        return config('shopify-app.billing_type');
    }
}
