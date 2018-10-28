<?php

/**
 * PaymentMethod class.
 *
 * Model class for payment methods
 *
 * LICENSE: This product includes software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   MVC Model
 *
 * @author     N. Pham <n.pham@acellemail.com>
 * @author     L. Pham <l.pham@acellemail.com>
 * @copyright  Acelle Co., Ltd
 * @license    Acelle Co., Ltd
 *
 * @version    1.0
 *
 * @link       http://acellemail.com
 */

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    // PaymentMethod status
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ACTIVE = 'active';

    // PaymentMethod type
    const TYPE_CASH = 'cash';
    const TYPE_PAYPAL = 'paypal';
    const TYPE_BRAINTREE_PAYPAL = 'braintree_paypal';
    const TYPE_BRAINTREE_CREDIT_CARD = 'braintree_credit_card';
    const TYPE_STRIPE_CREDIT_CARD = 'stripe_credit_card';
    const TYPE_PADDLE_CARD = 'paddle_card';
    const TYPE_PAYU_MONEY = 'payumoney';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type', 'options', 'status', 'admin_id',
    ];

    /**
     * The rules for validation.
     *
     * @var array
     */
    public function rules()
    {
        $rules = array(
            'status' => 'required',
        );

        if (\Auth::user()->can('create', $this)) {
            $rules['type'] = 'required';
        }

        if ($this->type == self::TYPE_PAYPAL) {
            $rules['options.environment'] = 'required';
            $rules['options.clientID'] = 'required';
            $rules['options.secret'] = 'required';
        }

        if ($this->type == self::TYPE_BRAINTREE_PAYPAL || $this->type == self::TYPE_BRAINTREE_CREDIT_CARD) {
            $rules['options.environment'] = 'required';
            $rules['options.merchantId'] = 'required';
            $rules['options.publicKey'] = 'required';
            $rules['options.privateKey'] = 'required';
            $rules['options.merchantAccountID'] = 'required';
        }

        if ($this->type == self::TYPE_STRIPE_CREDIT_CARD) {
            $rules['options.api_secret_key'] = 'required';
            $rules['options.api_publishable_key'] = 'required';
        }

        if ($this->type == self::TYPE_PADDLE_CARD) {
            $rules['options.vendor_id'] = 'required';
            $rules['options.vendor_auth_code'] = 'required';
            $rules['options.public_key'] = 'required';
        }

        if ($this->type == self::TYPE_PAYU_MONEY) {
            $rules['options.merchant_key'] = 'required';
            $rules['options.salt'] = 'required';
            $rules['options.payu_base_url'] = 'required';
        }

        # Check if payment method is valid
        if (!$this->isValid()) {
            $rules['payment_method_not_valid'] = 'required';
        }

        return $rules;
    }

    /**
     * Find item by uid.
     *
     * @return object
     */
    public static function findByUid($uid)
    {
        return self::where('uid', '=', $uid)->first();
    }

    public function admin()
    {
        return $this->belongsTo('Acelle\Model\Admin');
    }

    /**
     * Bootstrap any application services.
     */
    public static function boot()
    {
        parent::boot();

        // Create uid when creating list.
        static::creating(function ($item) {
            // Create new uid
            $uid = uniqid();
            while (PaymentMethod::where('uid', '=', $uid)->count() > 0) {
                $uid = uniqid();
            }
            $item->uid = $uid;

            // Update custom order
            PaymentMethod::getAll()->increment('custom_order', 1);
            $item->custom_order = 0;
        });
    }

    /**
     * Get all items.
     *
     * @return collect
     */
    public static function getAll()
    {
        return self::select('*');
    }

    /**
     * Items per page.
     *
     * @var array
     */
    public static $itemsPerPage = 25;

    /**
     * Filter items.
     *
     * @return collect
     */
    public static function filter($request)
    {
        $query = self::select('payment_methods.*');

        // Keyword
        if (!empty(trim($request->keyword))) {
            foreach (explode(' ', trim($request->keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('payment_methods.name', 'like', '%'.$keyword.'%');
                });
            }
        }

        // filters
        $filters = $request->filters;

        if (!empty($request->admin_id)) {
            $query = $query->where('payment_methods.admin_id', '=', $request->admin_id);
        }

        return $query;
    }

    /**
     * Search items.
     *
     * @return collect
     */
    public static function search($request)
    {
        $query = self::filter($request);

        if (!empty($request->sort_order)) {
            $query = $query->orderBy($request->sort_order, $request->sort_direction);
        } else {
            $query = $query->orderBy('payment_methods.type', 'asc');
        }

        return $query;
    }

    /**
     * Disable payment_method.
     *
     * @return bool
     */
    public function disable()
    {
        $this->status = self::STATUS_INACTIVE;

        return $this->save();
    }

    /**
     * Enable payment_method.
     *
     * @return bool
     */
    public function enable()
    {
        $this->status = self::STATUS_ACTIVE;

        return $this->save();
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function getOptions()
    {
        return json_decode($this->options, true);
    }

    /**
     * Get option.
     *
     * @return string
     */
    public function getOption($name)
    {
        $options = $this->getOptions();

        return isset($options[$name]) ? $options[$name] : null;
    }

    /**
     * Get customer select2 select options.
     *
     * @return array
     */
    public static function select2($request)
    {
        $data = ['items' => [], 'more' => true];

        $query = \Acelle\Model\PaymentMethod::getAll()->orderBy('custom_order', 'asc');
        if (isset($request->q)) {
            $keyword = $request->q;
            $query = $query->where(function ($q) use ($keyword) {
                $q->orwhere('payment_methods.name', 'like', '%'.$keyword.'%');
            });
        }
        foreach ($query->limit(20)->get() as $payment_method) {
            $data['items'][] = ['id' => $payment_method->uid, 'text' => trans('messages.payment_method_type_'.$payment_method->type)];
        }

        return json_encode($data);
    }

    /**
     * Get all items.
     *
     * @return collect
     */
    public static function getAllActive()
    {
        return self::getAll()
            ->where('payment_methods.status', '=', self::STATUS_ACTIVE)
            ->orderBy('custom_order', 'asc')->get();
    }

    /**
     * Payment methos types select options.
     *
     * @return array
     */
    public static function typeSelectOptions()
    {
        return [
            ['value' => self::TYPE_CASH, 'text' => trans('messages.'.self::TYPE_CASH)],
            ['value' => self::TYPE_BRAINTREE_PAYPAL, 'text' => trans('messages.'.self::TYPE_BRAINTREE_PAYPAL)],
            ['value' => self::TYPE_BRAINTREE_CREDIT_CARD, 'text' => trans('messages.'.self::TYPE_BRAINTREE_CREDIT_CARD)],
            ['value' => self::TYPE_STRIPE_CREDIT_CARD, 'text' => trans('messages.'.self::TYPE_STRIPE_CREDIT_CARD)],
        ];
    }

    /**
     * Set Braintree auth info.
     */
    public function getBraintreeClientToken()
    {
        \Braintree_Configuration::environment($this->getOption('environment'));
        \Braintree_Configuration::merchantId($this->getOption('merchantId'));
        \Braintree_Configuration::publicKey($this->getOption('publicKey'));
        \Braintree_Configuration::privateKey($this->getOption('privateKey'));

        return \Braintree_ClientToken::generate();
    }

    /**
     * Set Braintree auth info.
     */
    public function getBraintreeMerchantAccounts()
    {
        \Braintree_Configuration::environment($this->getOption('environment'));
        \Braintree_Configuration::merchantId($this->getOption('merchantId'));
        \Braintree_Configuration::publicKey($this->getOption('publicKey'));
        \Braintree_Configuration::privateKey($this->getOption('privateKey'));

        $gateway = \Braintree_Configuration::gateway();
        $merchantAccountIterator = $gateway->merchantAccount()->all();

        $accounts = [];
        foreach ($merchantAccountIterator as $merchantAccount) {
            $accounts[] = $merchantAccount;
        }

        return $accounts;
    }

    /**
     * Set Braintree auth info.
     */
    public function getBraintreeMerchantAccountSelectOptions($accounts)
    {
        $options = [];
        foreach ($accounts as $merchantAccount) {
            $options[] = ['value' => $merchantAccount->id, 'text' => $merchantAccount->id.' / '.$merchantAccount->currencyIsoCode.($merchantAccount->default ? ' ('.trans('messages.default').')' : '')];
        }

        return $options;
    }

    /**
     * Get Braintree merchant account by ID.
     */
    public function getBraintreeMerchantAccountByID($accounts, $id)
    {
        $options = [];
        foreach ($accounts as $merchantAccount) {
            if ($merchantAccount->id == $id) {
                return $merchantAccount;
            }
        }

        return $accounts[0];
    }

    /**
     * Get payment method by type.
     *
     * @return object
     */
    public static function getByType($type)
    {
        return self::where('payment_methods.type', '=', $type)
            ->first();
    }

    /**
     * Check payment method setting is valid.
     *
     * @return bool
     */
    public function isValid()
    {
        try {
            if ($this->type == self::TYPE_BRAINTREE_PAYPAL || $this->type == self::TYPE_BRAINTREE_CREDIT_CARD) {
                $this->getBraintreeClientToken();
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Payment methods status select options.
     *
     * @return array
     */
    public static function statusSelectOptions()
    {
        return [
            ['value' => self::STATUS_ACTIVE, 'text' => trans('messages.payment_method_status_'.self::STATUS_ACTIVE)],
            ['value' => self::STATUS_INACTIVE, 'text' => trans('messages.payment_method_status_'.self::STATUS_INACTIVE)],
        ];
    }

    /**
     * Get PayPal Api Access token.
     *
     * @return array
     */
    public function getPayPalAccessToken()
    {
        $ch = curl_init();

        // Get options from payment method PayPal
        $clientId = $this->getOption('clientID');
        $secret = $this->getOption('secret');
        // Request to PayPal enpoint
        curl_setopt($ch, CURLOPT_URL, 'https://api'.($this->getOption('environment') == 'sandbox' ? '.sandbox' : '').'.paypal.com/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId.':'.$secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');

        $result = curl_exec($ch);
        // Check the result
        if (!empty($result)) {
            $json = json_decode($result);
            // Token success
            if (isset($json->access_token)) {
                return ['success' => true, 'token' => $json->access_token];
                // Check if has any error
            } elseif (isset($json->error)) {
                throw new \Exception($json->error.': '.$json->error_description);
            }
        }

        throw new \Exception(trans('messages.paypal_error_cannot_get_access_token'));
    }

    /**
     * Check PayPal Payment success.
     *
     * @return array
     */
    public function checkPayPalPaymentSuccess($paymentID, $payerID, $access_token, $subscription)
    {
        $ch = curl_init();

        // Request to PayPal enpoint
        curl_setopt($ch, CURLOPT_URL, 'https://api'.($this->getOption('environment') == 'sandbox' ? '.sandbox' : '').".paypal.com/v1/payments/payment/$paymentID");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type:application/json',
            'Authorization: Bearer '.$access_token,
        ]);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        // Check the result
        if (!empty($result)) {
            $json = json_decode($result);

            // Check if payment state is not approved
            if (!isset($json->state) || $json->state != 'approved') {
                return (object) ['success' => false, 'data' => $json, 'error' => trans('messages.paypal_error_payment_not_approved')];
            }

            // Check if payment amount is not equal to the subscription amount
            if ((float) $json->transactions[0]->amount->details->subtotal != (float) $subscription->price) {
                return (object) ['success' => false, 'data' => $json, 'error' => trans('messages.paypal_error_amount_not_equal')];
            }

            // Return success if nothing wrong
            return (object) ['success' => true, 'data' => $result];
        }

        // Uncatchable error
        throw new \Exception(trans('messages.paypal_error_unknown'));
    }

    /**
     * executePayment server side  - one time payment without recurring payment.
     *
     * @return array
     */
    public function executePayment($paymentID, $payerID, $subscription)
    {
        $ch = curl_init();

        // Get options from payment method PayPal
        $clientId = $this->getOption('clientID');
        $secret = $this->getOption('secret');
        // Request to PayPal enpoint
        curl_setopt($ch, CURLOPT_URL, 'https://api'.($this->getOption('environment') == 'sandbox' ? '.sandbox' : '').'.paypal.com/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId.':'.$secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');

        $result = curl_exec($ch);
        // Check the result
        if (!empty($result)) {
            $json = json_decode($result);
            $access_token = $json->access_token;
            $ch = curl_init();
            $json_body = '{
                          "payer_id": "'.$payerID.'"
                        }';
            // Request to PayPal enpoint
            curl_setopt($ch, CURLOPT_URL, 'https://api'.($this->getOption('environment') == 'sandbox' ? '.sandbox' : '').".paypal.com/v1/payments/payment/$paymentID/execute/");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                  'Content-Type:application/json',
                  'Authorization: Bearer '.$access_token,
              ]);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);
            $result = curl_exec($ch);

            // Check the result
            if (!empty($result)) {
                $json = json_decode($result);
                // Check if payment state is not approved
                if (!isset($json->state) || $json->state != 'approved') {
                    return (object) ['success' => false, 'data' => $json, 'error' => trans('messages.paypal_error_payment_not_approved')];
                }

                // Check if payment amount is not equal to the subscription amount
                if ((float) $json->transactions[0]->amount->total != (float) $subscription->price) {
                    return (object) ['success' => false, 'data' => $json, 'error' => trans('messages.paypal_error_amount_not_equal')];
                }

                // Return success if nothing wrong
                return (object) ['success' => true, 'data' => $result];
            }

            // Uncatchable error
            throw new \Exception(trans('messages.paypal_error_unknown'));
        }
        throw new \Exception(trans('messages.paypal_error_cannot_get_access_token'));
    }

    /**
     * recurringPayment server side  - one time payment with recurring payment.
     *
     * @return array
     */
    public function recurringPayment($paymentID, $payerID, $subscription, $subscription_uid)
    {
        $ch = curl_init();

        // Get options from payment method PayPal
        $clientId = $this->getOption('clientID');
        $secret = $this->getOption('secret');
        // Request to PayPal enpoint
        curl_setopt($ch, CURLOPT_URL, 'https://api'.($this->getOption('environment') == 'sandbox' ? '.sandbox' : '').'.paypal.com/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId.':'.$secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');

        $result = curl_exec($ch);
        // Check the result
        if (!empty($result)) {
            $json = json_decode($result);
            $access_token = $json->access_token;
            $result1 = $this->createPlan($access_token, $subscription, $subscription_uid);
            // Check the result
            if (!empty($result1)) {

                // Check if payment state is not approved
                if (!isset($json->state) || $json->state != 'approved') {
                    return (object) ['success' => false, 'data' => $json, 'error' => trans('messages.paypal_error_payment_not_approved')];
                }

                // Check if payment amount is not equal to the subscription amount
                if ((float) $json->transactions[0]->amount->total != (float) $subscription->price) {
                    return (object) ['success' => false, 'data' => $json, 'error' => trans('messages.paypal_error_amount_not_equal')];
                }

                // Return success if nothing wrong
                return (object) ['success' => true, 'data' => $result];
            }

            // Uncatchable error
            throw new \Exception(trans('messages.paypal_error_unknown'));
        }
        throw new \Exception(trans('messages.paypal_error_cannot_get_access_token'));
    }

    public function createPlan($access_token, $subscription, $subscription_uid)
    {
        $amout = array(
           'value' => "$subscription->price",
           'currency' => "$subscription->currency_code",
        );
        $shippingandtax = array(
            'value' => '0',
            'currency' => 'USD',
        );

        $charge_models = array([
            'type' => 'SHIPPING',
            'amount' => $shippingandtax,
          ],
          [
            'type' => 'TAX',
            'amount' => $shippingandtax,
        ], );

        $payment_definitions_creation = array();
        array_push($payment_definitions_creation, [
          'name' => $subscription->plan_name,
          'type' => 'REGULAR',
          'frequency' => 'DAY',
          'frequency_interval' => '1',
          'amount' => $amout,
          'cycles' => '0',
          'charge_models' => $charge_models,
       ]);

        $merchant_preferences_temp = array(
          'value' => '0',
          'currency' => 'USD',
        );
        $merchant_preferences = array(
          'setup_fee' => $merchant_preferences_temp,
          'return_url' => url('/').'/payments/paypal/'.$subscription_uid,
          'cancel_url' => url('/').'/payments/paypal/'.$subscription_uid.'/paypal_cancel',
          'auto_bill_amount' => 'YES',
          'initial_fail_amount_action' => 'CONTINUE',
          'max_fail_attempts' => '3',
        );
        $name = $subscription->plan_name;
        $body = array(
          'name' => $name,
          'description' => 'Subscribtion'.$name,
          'type' => 'INFINITE',
          'payment_definitions' => $payment_definitions_creation,
          'merchant_preferences' => $merchant_preferences,
        );
        $myJSON = json_encode($body);

        $plan_url = 'https://api'.($this->getOption('environment') == 'sandbox' ? '.sandbox' : '').'.paypal.com/v1/payments/billing-plans/';
        //open connection
        $ch = curl_init();

        //set connection properties
        curl_setopt($ch, CURLOPT_URL, $plan_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Content-Type:application/json',
          'Authorization: Bearer '.$access_token,
      ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $myJSON);

        //execute post
        $response = curl_exec($ch);

        //close connection
        curl_close($ch);

        return $response;
    }

    public function activePlan($access_token, $subscription, $subscription_uid)
    {
        $amout = array(
           'value' => "$subscription->price",
           'currency' => "$subscription->currency_code",
        );
        $shippingandtax = array(
            'value' => '0',
            'currency' => 'USD',
        );

        $charge_models = array([
            'type' => 'SHIPPING',
            'amount' => $shippingandtax,
          ],
          [
            'type' => 'TAX',
            'amount' => $shippingandtax,
        ], );

        $payment_definitions_creation = array();
        array_push($payment_definitions_creation, [
          'name' => $subscription->plan_name,
          'type' => 'REGULAR',
          'frequency' => 'DAY',
          'frequency_interval' => '1',
          'amount' => $amout,
          'cycles' => '0',
          'charge_models' => $charge_models,
       ]);

        $merchant_preferences_temp = array(
          'value' => '0',
          'currency' => 'USD',
        );
        $merchant_preferences = array(
          'setup_fee' => $merchant_preferences_temp,
          'return_url' => url('/').'payments/paypal/'.$subscription_uid,
          'cancel_url' => url('/').'payments/paypal/'.$subscription_uid.'/paypal_cancel',
          'auto_bill_amount' => 'YES',
          'initial_fail_amount_action' => 'CONTINUE',
          'max_fail_attempts' => '3',
        );
        $name = $subscription->plan_name;
        $body = array(
          'name' => $name,
          'description' => 'Subscribtion'.$name,
          'type' => 'INFINITE',
          'payment_definitions' => $payment_definitions_creation,
          'merchant_preferences' => $merchant_preferences,
        );
        $myJSON = json_encode($body);

        $plan_url = 'https://api'.($this->getOption('environment') == 'sandbox' ? '.sandbox' : '').'.paypal.com/v1/payments/billing-plans/';
        //open connection
        $ch = curl_init();

        //set connection properties
        curl_setopt($ch, CURLOPT_URL, $plan_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Content-Type:application/json',
          'Authorization: Bearer '.$access_token,
      ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $myJSON);

        //execute post
        $response = curl_exec($ch);

        //close connection
        curl_close($ch);

        return 1;
    }

    /***
    **function: createPlanOnServer
    ** $payment_method: get payment method details by id
    ** $type:  plan type - paypal, stripe , paddle
    ** use: create all plan on server and update plan table with plan id
    **/
    public function createPlanOnServer($payment_method)
    {
        if ($payment_method->type == self::TYPE_PADDLE_CARD) {
            foreach (\Acelle\Model\Plan::getAllActiveWithDefault()->get() as $plan) {
                $vendorId = $payment_method->getOption('vendor_id');
                $vendor_auth_code = $payment_method->getOption('vendor_auth_code');
                // plan_type: accepts day, week, month, year
                $json_body = 'vendor_id='.$vendorId.'&vendor_auth_code='.$vendor_auth_code.'&plan_name='.$plan->name.'&plan_trial_days=0&plan_type='.$plan->frequency_unit.'&plan_length='.$plan->frequency_amount.'&main_currency_code=USD&initial_price_usd=0.00&recurring_price_usd='.$plan->price;
                // create plan if paddle plan id blank
                if ($plan->paddle_plan_id == '' || $plan->paddle_plan_id == null) {
                    $plan->createPaddlePlan($json_body, $plan);
                }
            }
        }

        return true;
    }
}
