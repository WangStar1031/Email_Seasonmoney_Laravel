<?php
namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Acelle\Model\Payment;
use Acelle\Library\Log as PaymentLog;
use Illuminate\Support\Facades\Log as LaravelLog;
use File;
use Auth;

class PaymentController extends Controller
{

    /**
     * Check billing information exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkBillingInformation(Request $request, $subscription)
    {
        if (null == $request->session()->get('billing_information') && $subscription->isTaxBillingRequired()) {
            return action('PaymentController@billingInformation', $subscription->uid);
        } else {
            return true;
        }
    }

    /**
     * Subscription billing information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function billingInformation(Request $request, $subscription_uid)
    {
        $customer = $request->user()->customer;
        $subscription = \Acelle\Model\Subscription::findByUid($subscription_uid);
        $billing_information = [];

        // Get information from contact
        $contact = $request->user()->customer->contact;
        if (is_object($contact)) {
            $billing_information['tax_number'] = $contact->tax_number;
            $billing_information['billing_address'] = $contact->billing_address;
        }

        // From session if exist
        if (null !== $request->session()->get('billing_information')) {
            $billing_information = $request->session()->get('billing_information');
        }

        // Get old post values
        if (!empty($request->old())) {
            $billing_information = $request->old();
        }

        $rules = [
            'tax_number' => 'required',
            'billing_address' => 'required',
        ];

        // validate and save billing information to session
        if ($request->isMethod('post')) {
            $this->validate($request, $rules);

            $billing_information = $request->all();
            $request->session()->put('billing_information', $billing_information);

            // Write payment info to file
            \File::put('billing-information-' . $subscription->uid . '.log', json_encode($billing_information));

            return redirect()->away($request->session()->get('current_payment_link'));
        }

        return view('payments.billing_information', [
            'subscription' => $subscription,
            'rules' => $rules,
            'billing_information' => $billing_information
        ]);
    }

    /**
     * Subscription pay by PayPal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function paypal(Request $request, $subscription_uid)
    {
        $subscription = \Acelle\Model\Subscription::findByUid($subscription_uid);
        $order_id = $subscription->getOrderID();

        // Check billing information
        if ($this->checkBillingInformation($request, $subscription) !== true) {
            return redirect()->away($this->checkBillingInformation($request, $subscription));
        }

        $payment_method = \Acelle\Model\PaymentMethod::getByType(\Acelle\Model\PaymentMethod::TYPE_PAYPAL);

        // validate and save posted data
        if ($request->isMethod('post')) {
            try {
                //$access_token = $payment_method->getPayPalAccessToken();
                //$result = $payment_method->checkPayPalPaymentSuccess($request->paymentID, $request->payerID, $access_token, $subscription);


                //server side payment without recurring payment
                $result = $payment_method->executePayment($request->paymentID, $request->payerID, $subscription);
                //server side payment with recurring payment
                //$result = $payment_method->recurringPayment($request->paymentID, $request->payerID, $subscription, $subscription_uid);

                $payment = new Payment();
                $payment->subscription_id = $subscription->id;
                $payment->payment_method_id = $payment_method->id;
                $payment->data = serialize($result);
                $payment->status = $result->success ? \Acelle\Model\Payment::STATUS_SUCCESS : \Acelle\Model\Payment::STATUS_FAILED;
                $payment->action = \Acelle\Model\Payment::ACTION_PAID;
                $payment->payment_method_name = trans('messages.' . $payment_method->type);
                $payment->order_id = $order_id;

                // billing information
                $billing_information = $request->session()->get('billing_information');
                if (isset($billing_information)) {
                    $payment->tax_number = $billing_information['tax_number'];
                    $payment->billing_address = $billing_information['billing_address'];
                    $request->session()->forget('billing_information');
                }

                $payment->save();

                if ($result->success) {
                    $subscription->setPaid();

                    // try enabling the subscription, proceed anyway if failed
                    try {
                        $subscription->enable();
                    } catch (\Exception $ex) {
                        // just suppress the error and leave the subscription disabled
                        LaravelLog::warning("Cannot enable subscription {$subscription->id}, proceed anyway");
                    }

                    return redirect()->action('PaymentController@success', $subscription->uid);
                } else {
                    throw new \Exception($result->error);
                }
            } catch (\Exception $e) {
                PaymentLog::error(trans('messages.something_went_wrong_with_payment') . ': ' .$e->getMessage());
                return view('somethingWentWrong', ['message' => trans('messages.something_went_wrong_with_payment', ['error' => $e->getMessage()])]);
            }
        }

        return view('payments.paypal', [
            'subscription' => $subscription,
            'order_id' => $order_id,
            'payment_method' => $payment_method,
        ]);
    }

    /**
     * Subscription pay by Braintree credit card.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function braintree_credit_card(Request $request, $subscription_uid)
    {
        $subscription = \Acelle\Model\Subscription::findByUid($subscription_uid);
        $result = null;

        // Check billing information
        if ($this->checkBillingInformation($request, $subscription) !== true) {
            return redirect()->away($this->checkBillingInformation($request, $subscription));
        }

        $payment_method = \Acelle\Model\PaymentMethod::getByType(\Acelle\Model\PaymentMethod::TYPE_BRAINTREE_CREDIT_CARD);

        try {
            $clientToken =  $payment_method->getBraintreeClientToken();

            $order_id = $subscription->getOrderID();

            // validate and save posted data
            if ($request->isMethod('post')) {
                $nonceFromTheClient = $request->payment_method_nonce;

                $result = \Braintree_Transaction::sale([
                    'amount' => $subscription->price,
                    'paymentMethodNonce' => $nonceFromTheClient,
                    'merchantAccountId' => $payment_method->getOption('merchantAccountID'),
                    "orderId" => $order_id,
                    'options' => [
                      'submitForSettlement' => true
                    ]
                ]);

                $payment = new Payment();
                $payment->subscription_id = $subscription->id;
                $payment->payment_method_id = $payment_method->id;
                $payment->data = serialize($result);
                $payment->status = $result->success ? \Acelle\Model\Payment::STATUS_SUCCESS : \Acelle\Model\Payment::STATUS_FAILED;
                $payment->action = \Acelle\Model\Payment::ACTION_PAID;
                $payment->payment_method_name = trans('messages.' . $payment_method->type);
                $payment->order_id = $order_id;

                // billing information
                $billing_information = $request->session()->get('billing_information');
                if (isset($billing_information)) {
                    $payment->tax_number = $billing_information['tax_number'];
                    $payment->billing_address = $billing_information['billing_address'];
                    $request->session()->forget('billing_information');
                }

                $payment->save();

                if ($result->success) {
                    $subscription->setPaid();

                    // try enabling the subscription, proceed anyway if failed
                    try {
                        $subscription->enable();
                    } catch (\Exception $ex) {
                        // just suppress the error and leave the subscription disabled
                        LaravelLog::warning("Cannot enable subscription {$subscription->id}, proceed anyway");
                    }

                    return redirect()->action('PaymentController@success', $subscription->uid);
                }
            }
        } catch (\Exception $e) {
            PaymentLog::error(trans('messages.something_went_wrong_with_payment') . ': ' .$e->getMessage());
            return view('somethingWentWrong', ['message' => trans('messages.something_went_wrong_with_payment', ['error' => $e->getMessage()])]);
        }

        return view('payments.braintree_credit_card', [
            'subscription' => $subscription,
            'clientToken' => $clientToken,
            'result' => $result,
            'payment_method' => $payment_method,
        ]);
    }

    /**
     * Subscription pay by Braintree credit card.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function braintree_paypal(Request $request, $subscription_uid)
    {
        $subscription = \Acelle\Model\Subscription::findByUid($subscription_uid);
        $result = null;

        // Check billing information
        if ($this->checkBillingInformation($request, $subscription) !== true) {
            return redirect()->away($this->checkBillingInformation($request, $subscription));
        }

        $payment_method = \Acelle\Model\PaymentMethod::getByType(\Acelle\Model\PaymentMethod::TYPE_BRAINTREE_PAYPAL);

        try {
            $clientToken =  $payment_method->getBraintreeClientToken();

            $order_id = $subscription->getOrderID();

            // validate and save posted data
            if ($request->isMethod('post')) {
                $nonceFromTheClient = $request->payment_method_nonce;

                $result = \Braintree_Transaction::sale([
                    "amount" => $subscription->price,
                    "paymentMethodNonce" => $nonceFromTheClient,
                    'merchantAccountId' => $payment_method->getOption('merchantAccountID'),
                    "orderId" => $order_id,
                    'options' => [
                      'submitForSettlement' => true
                    ]
                ]);

                $payment = new Payment();
                $payment->subscription_id = $subscription->id;
                $payment->data = serialize($result);
                $payment->payment_method_id = $payment_method->id;
                $payment->status = $result->success ? \Acelle\Model\Payment::STATUS_SUCCESS : \Acelle\Model\Payment::STATUS_FAILED;
                $payment->action = \Acelle\Model\Payment::ACTION_PAID;
                $payment->payment_method_name = trans('messages.' . $payment_method->type);
                $payment->order_id = $order_id;

                // billing information
                $billing_information = $request->session()->get('billing_information');
                if (isset($billing_information)) {
                    $payment->tax_number = $billing_information['tax_number'];
                    $payment->billing_address = $billing_information['billing_address'];
                    $request->session()->forget('billing_information');
                }

                $payment->save();

                if ($result->success) {
                    $subscription->setPaid();

                    // try enabling the subscription, proceed anyway if failed
                    try {
                        $subscription->enable();
                    } catch (\Exception $ex) {
                        // just suppress the error and leave the subscription disabled
                        LaravelLog::warning("Cannot enable subscription {$subscription->id}, proceed anyway");
                    }

                    return redirect()->action('PaymentController@success', $subscription->uid);
                }
            }
        } catch (\Exception $e) {
            PaymentLog::error(trans('messages.something_went_wrong_with_payment') . ': ' .$e->getMessage());
            return view('somethingWentWrong', ['message' => trans('messages.something_went_wrong_with_payment', ['error' => $e->getMessage()])]);
        }

        return view('payments.braintree_paypal', [
            'subscription' => $subscription,
            'clientToken' => $clientToken,
            'result' => $result,
            'payment_method' => $payment_method,
        ]);
    }

    /**
     * Payment success page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function success(Request $request, $subscription_uid)
    {
        $subscription = \Acelle\Model\Subscription::findByUid($subscription_uid);
        return view('payments.success', [
            'subscription' => $subscription
        ]);
    }

    /**
     * Subscription update paid status from Service.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function paymentStatus(Request $request)
    {
        if ($request->tx) {
            if ($payment=Payment::where('transaction_id', $request->tx)->first()) {
                $payment_id=$payment->id;
            } else {
                $payment=new Payment;
                $payment->item_number = $request->item_number;
                $payment->transaction_id = $request->tx;
                $payment->currency_code = $request->cc;
                $payment->payment_status = $request->st;

                // billing information
                $billing_information = $request->session()->get('billing_information');
                if (isset($billing_information)) {
                    $payment->tax_number = $billing_information['tax_number'];
                    $payment->billing_address = $billing_information['billing_address'];
                    $request->session()->forget('billing_information');
                }

                $payment->save();

                $payment_id=$payment->id;
            }

            return 'Pyament has been done and your payment id is : ' . $payment_id;
        } else {
            return 'Payment has failed';
        }
    }

    /**
     * Subscription pay bay cash.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function cash(Request $request, $subscription_uid)
    {
        $subscription = \Acelle\Model\Subscription::findByUid($subscription_uid);
        $payment_method = \Acelle\Model\PaymentMethod::getByType(\Acelle\Model\PaymentMethod::TYPE_CASH);

        // Check billing information
        if ($this->checkBillingInformation($request, $subscription) !== true) {
            return redirect()->away($this->checkBillingInformation($request, $subscription));
        }

        $payment = new Payment();
        $payment->subscription_id = $subscription->id;
        $payment->payment_method_id = $payment_method->id;
        $payment->status = \Acelle\Model\Payment::STATUS_CASH_MANUAL_CONFIRMATION;
        $payment->action = \Acelle\Model\Payment::ACTION_PAID;
        $payment->payment_method_name = trans('messages.' . $payment_method->type);
        $payment->order_id = $subscription->getOrderID();

        // billing information
        $billing_information = $request->session()->get('billing_information');
        if (isset($billing_information)) {
            $payment->tax_number = $billing_information['tax_number'];
            $payment->billing_address = $billing_information['billing_address'];
            $request->session()->forget('billing_information');
        }

        $payment->save();

        $request->session()->flash('alert-success', trans('messages.subscription.cash.created'));
        return redirect()->action('AccountController@subscription');
    }

    /**
     * Subscription pay by Stripe credit card.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function stripe_credit_card(Request $request, $subscription_uid)
    {
        $subscription = \Acelle\Model\Subscription::findByUid($subscription_uid);
        $order_id = $subscription->getOrderID();
        $result = null;

        // Check billing information
        if ($this->checkBillingInformation($request, $subscription) !== true) {
            return redirect()->away($this->checkBillingInformation($request, $subscription));
        }

        $payment_method = \Acelle\Model\PaymentMethod::getByType(\Acelle\Model\PaymentMethod::TYPE_STRIPE_CREDIT_CARD);
        $apiSecretKey = $payment_method->getOption('api_secret_key');
        $apiPublishableKey = $payment_method->getOption('api_publishable_key');

        try {
            \Stripe\Stripe::setApiKey($apiSecretKey);

            // validate and save posted data
            if ($request->isMethod('post')) {

                // Token is created using Stripe.js or Checkout!
                // Get the payment token submitted by the form:
                $token = $request->stripeToken;

                // Charge the user's card:
                $result = \Stripe\Charge::create(array(
                    "amount" => $subscription->stripePrice(),
                    "currency" => $subscription->currency_code,
                    "description" => trans('messages.stripe_checkout_description', ['order' => $order_id]),
                    "source" => $token,
                ));

                $payment = new Payment();
                $payment->subscription_id = $subscription->id;
                $payment->payment_method_id = $payment_method->id;
                $payment->data = serialize($result);
                $payment->status = 'success';
                $payment->action = \Acelle\Model\Payment::ACTION_PAID;
                $payment->payment_method_name = trans('messages.' . $payment_method->type);
                $payment->order_id = $order_id;

                // billing information
                $billing_information = $request->session()->get('billing_information');
                if (isset($billing_information)) {
                    $payment->tax_number = $billing_information['tax_number'];
                    $payment->billing_address = $billing_information['billing_address'];
                    $request->session()->forget('billing_information');
                }

                $payment->save();
                $subscription->setPaid();

                // try enabling the subscription, proceed anyway if failed
                try {
                    $subscription->enable();
                } catch (\Exception $ex) {
                    // just suppress the error and leave the subscription disabled
                    LaravelLog::warning("Cannot enable subscription {$subscription->id}, proceed anyway");
                }

                return redirect()->action('PaymentController@success', $subscription->uid);
            }
        } catch (\Stripe_CardError $e) {
            $error_message = "";
            // Since it's a decline, Stripe_CardError will be caught
            $body = $e->getJsonBody();
            $err  = $body['error'];

            $error_message .= 'Status is:' . $e->getHttpStatus() . "\n";
            $error_message .= 'Type is:' . $err['type'] . "\n";
            $error_message .= 'Code is:' . $err['code'] . "\n";
            // param is '' in this case
            $error_message .= 'Param is:' . $err['param'] . "\n";
            $error_message .= 'Message is:' . $err['message'] . "\n";
        } catch (\Stripe_InvalidRequestError $e) {
            $error_message = $e->getMessage();
        } catch (\Stripe_AuthenticationError $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            $error_message = $e->getMessage();
        } catch (\Stripe_ApiConnectionError $e) {
            // Network communication with Stripe failed
        } catch (\Stripe_Error $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            $error_message = $e->getMessage();
        } catch (\Exception $e) {
            // Something else happened, completely unrelated to Stripe
            $error_message = $e->getMessage();
        }

        if (isset($error_message)) {
            PaymentLog::error(trans('messages.something_went_wrong_with_payment') . ': ' .$error_message);
            return view('somethingWentWrong', ['message' => trans('messages.something_went_wrong_with_payment', ['error' => $error_message])]);
        }

        return view('payments.stripe_credit_card', [
            'subscription' => $subscription,
            'apiPublishableKey' => $apiPublishableKey,
            'result' => $result,
            'payment_method' => $payment_method,
        ]);
    }

    /**
     * Subscription pay by Paddle card.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function paddle_card(Request $request, $subscription_uid)
    {
        $subscription = \Acelle\Model\Subscription::findByUid($subscription_uid);
        $orderId = $subscription->getOrderID();
        $result = null;

        // billing information
        $billing_information = $request->session()->get('billing_information');
        if (isset($billing_information)) {
            // Write payment info to file
            \File::put('billing-information-' . $subscription->uid . '.log', json_encode([
                "tax_number" => $billing_information['tax_number'],
                "billing_address" => $billing_information['billing_address'],
            ]));
        }

        // Check billing information
        if ($this->checkBillingInformation($request, $subscription) !== true) {
            return redirect()->away($this->checkBillingInformation($request, $subscription));
        }

        $payment_method = \Acelle\Model\PaymentMethod::getByType(\Acelle\Model\PaymentMethod::TYPE_PADDLE_CARD);
        $vendorId = $payment_method->getOption('vendor_id');

        // Checkout url
        $data['vendor_id'] = $vendorId;
        $data['vendor_auth_code'] = $payment_method->getOption('vendor_auth_code');
        if ($subscription->plan->paddle_plan_id!="" || $subscription->plan->$subscription->plan->paddle_plan_id!=null) {
            $data['product_id'] =$subscription->plan->paddle_plan_id;//'520029';
        }

        $data['title'] = trans('messages.paddle_checkout_title', ['plan' => $subscription->plan_name]); //'Plan: ' . $subscription->plan_name ; // name of product
        //$data['webhook_url'] = action('PaymentController@paddle_card_hook', $subscription->uid); // URL to call when product is purchased
        $data['quantity_variable'] = 0; //Specifies if the user is allowed to alter the quantity of the checkout, accepts 0 or 1 (default: 1).
        // You must provide at least one price for the checkout, here we are setting multiple for different currencies.
        $data['prices'] = [
            $subscription->currency_code . ':' . $subscription->price
        ];
        //If you leave this field empty, then the default prices of the Plan will be used.If this field is provided, you can set the recurring price for any of the currencies of your Plan, but you must always provide the recurring price in the main currency of your Plan.
        $data['recurring_prices'] = [
            $subscription->currency_code . ':' . $subscription->price
        ];
        //$data['recurring_affiliate_limit'] = 1;
        // Setting some other (optional) data.
        $data['custom_message'] = trans('messages.paddle_order_id') . ': ' . $orderId;
        $data['return_url'] = action('PaymentController@success', $subscription->uid);
        $data['passthrough'] =$subscription->customer_id.'/'.$subscription_uid;//customer id that pay the payment also add subscription_uid
        // Here we make the request to the Paddle API
        $url = 'https://vendors.paddle.com/api/2.0/product/generate_pay_link';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        try {
            $response = curl_exec($ch);

            if ($response) {
                // And handle the response...
                $data = json_decode($response);
                if ($data->success) {
                    $checkoutUrl = $data->response->url;
                } else {
                    throw new \Exception($data->error->message);
                }
            } else {
                throw new \Exception('Cannot connect to Paddle generate pay link!');
            }
        } catch (\Exception $e) {
            PaymentLog::error(trans('messages.something_went_wrong_with_payment') . ': ' .$e->getMessage());
            return view('somethingWentWrong', ['message' => trans('messages.something_went_wrong_with_payment', ['error' => $e->getMessage()])]);
        }

        return view('payments.paddle_card', [
            'subscription' => $subscription,
            'payment_method' => $payment_method,
            'vendorId' => $vendorId,
            'orderId' => $orderId,
            'checkoutUrl' => $checkoutUrl,
        ]);
    }

    /**
     * Subscription pay by Paddle card hook.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function paddle_card_hook(Request $request, $subscription_uid)
    {
        $arr = $request->all();
        $arr['time'] = \Carbon\Carbon::now();
        \File::put('paddle-process.log', json_encode($arr));
        $subscription = \Acelle\Model\Subscription::findByUid($subscription_uid);
        $order_id = $subscription->getOrderID();
        $payment_method = \Acelle\Model\PaymentMethod::getByType(\Acelle\Model\PaymentMethod::TYPE_PADDLE_CARD);

        $verified = $this->paddleVerifySignature($payment_method->getOption('public_key'), $request->all());

        if ($verified) {
            $result = $request->all();

            $payment = new Payment();
            $payment->subscription_id = $subscription->id;
            $payment->payment_method_id = $payment_method->id;
            $payment->data = serialize($result);
            $payment->action = \Acelle\Model\Payment::ACTION_PAID;
            $payment->payment_method_name = trans('messages.' . $payment_method->type);
            $payment->order_id = $order_id;

            // billing information
            if ($subscription->isTaxBillingRequired() && file_exists('billing-information-' . $subscription->uid . '.log')) {
                $billing_information = json_decode(file_get_contents('billing-information-' . $subscription->uid . '.log'), true);
            }
            if (isset($billing_information)) {
                $payment->tax_number = $billing_information['tax_number'];
                $payment->billing_address = $billing_information['billing_address'];
                $request->session()->forget('billing_information');
            }

            $payment->status = \Acelle\Model\Payment::STATUS_SUCCESS; // : \Acelle\Model\Payment::STATUS_FAILED;

            $payment->save();
            $subscription->setPaid();

            // try enabling the subscription, proceed anyway if failed
            try {
                $subscription->enable();
            } catch (\Exception $ex) {
                // just suppress the error and leave the subscription disabled
                LaravelLog::warning("Cannot enable subscription {$subscription->id}, proceed anyway");
            }
        } else {
            LaravelLog::error("Cannot verify Paddle signature " . $request->p_signature);
        }
    }

    /**
     * Verify paddle signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function paddleVerifySignature($public_key, $post)
    {
        // Get the p_signature parameter & base64 decode it.
        $signature = base64_decode($post['p_signature']);

        // Get the fields sent in the request, and remove the p_signature parameter
        $fields = $post;
        unset($fields['p_signature']);

        // ksort() and serialize the fields
        ksort($fields);
        foreach ($fields as $k => $v) {
            if (!in_array(gettype($v), array('object', 'array'))) {
                $fields[$k] = "$v";
            }
        }
        $data = serialize($fields);

        // Veirfy the signature
        $verification = openssl_verify($data, $signature, $public_key, OPENSSL_ALGO_SHA1);

        if ($verification == 1) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Subscription pay by PayU Money.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function payumoney(Request $request, $subscription_uid)
    {
        $subscription = \Acelle\Model\Subscription::findByUid($subscription_uid);
        $data = [];
        // Check billing information
        if ($this->checkBillingInformation($request, $subscription) !== true) {
            return redirect()->away($this->checkBillingInformation($request, $subscription));
        }

        $payment_method = \Acelle\Model\PaymentMethod::getByType(\Acelle\Model\PaymentMethod::TYPE_PAYU_MONEY);
        $rules = [
            'name' => 'required',
            'emails' => 'required',
            'phones'=>'required',
        ];
        try {
            $order_id = $subscription->getOrderID();
            $hash = '';
            $payu_link='';
            // Hash Sequence
            $hashSequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";
            // validate and save posted data
            if ($request->isMethod('post')) {
                //$this->validate($request, $rules);
                $data = [
                  'key'=>$payment_method->getOption('merchant_key'),
                  'txnid' => substr(hash('sha256', mt_rand() . microtime()), 0, 20), # Transaction ID.
                  'amount' => $subscription->price, # Amount to be charged.
                  'productinfo' => "subscription",
                  'firstname' => $request->name, # Payee Name.
                  'email' => $request->emails, # Payee Email Address.
                  'phone' => $request->phones, # Payee Phone Number.
                  'surl'=>url('/').'/payments/payumoney-success/'.$subscription_uid,
                  'furl'=>url('/').'/payments/payumoney-fail/'.$subscription_uid,
                  'service_provider'=>'payu_paisa',
              ];
                $hashVarsSeq = explode('|', $hashSequence);
                $hash_string = '';
                foreach ($hashVarsSeq as $hash_var) {
                    $hash_string .= isset($data[$hash_var]) ? $data[$hash_var] : '';
                    $hash_string .= '|';
                }
                $hash_string .= $payment_method->getOption('salt');
                $hash = strtolower(hash('sha512', $hash_string));
                $payu_link = $payment_method->getOption('payu_base_url') . '/_payment';
            }
        } catch (\Exception $e) {
            PaymentLog::error(trans('messages.something_went_wrong_with_payment') . ': ' .$e->getMessage());
            return view('somethingWentWrong', ['message' => trans('messages.something_went_wrong_with_payment', ['error' => $e->getMessage()])]);
        }
        return view('payments.payumoney', [
                'subscription' => $subscription,
                'data' => $data,
                'hash'=>$hash,
                'payu_link'=>$payu_link,
                'payment_method' => $payment_method,
            ]);
    }

    /**
     * Subscription pay by PayU Money Success.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function payumoney_success(Request $request, $subscription_uid)
    {
        $subscription = \Acelle\Model\Subscription::findByUid($subscription_uid);
        $result = null;
        // Check billing information
        if ($this->checkBillingInformation($request, $subscription) !== true) {
            return redirect()->away($this->checkBillingInformation($request, $subscription));
        }

        $payment_method = \Acelle\Model\PaymentMethod::getByType(\Acelle\Model\PaymentMethod::TYPE_PAYU_MONEY);

        try {
            $order_id = $subscription->getOrderID();

            // validate and save posted data
            if ($request->isMethod('post')) {
                $result = $request->all();
                $payment = new Payment();
                $payment->subscription_id = $subscription->id;
                $payment->data = serialize($result);
                $payment->payment_method_id = $payment_method->id;
                $payment->status = \Acelle\Model\Payment::STATUS_SUCCESS;
                $payment->action = \Acelle\Model\Payment::ACTION_PAID;
                $payment->payment_method_name = trans('messages.' . $payment_method->type);
                $payment->order_id = $order_id;

                // billing information
                $billing_information = $request->session()->get('billing_information');
                if (isset($billing_information)) {
                    $payment->tax_number = $billing_information['tax_number'];
                    $payment->billing_address = $billing_information['billing_address'];
                    $request->session()->forget('billing_information');
                }

                $payment->save();

                if ($result['status']) {
                    $subscription->setPaid();

                    // try enabling the subscription, proceed anyway if failed
                    try {
                        $subscription->enable();
                    } catch (\Exception $ex) {
                        // just suppress the error and leave the subscription disabled
                        LaravelLog::warning("Cannot enable subscription {$subscription->id}, proceed anyway");
                    }

                    return redirect()->action('PaymentController@success', $subscription->uid);
                }
            }
        } catch (\Exception $e) {
            PaymentLog::error(trans('messages.something_went_wrong_with_payment') . ': ' .$e->getMessage());
            return view('somethingWentWrong', ['message' => trans('messages.something_went_wrong_with_payment', ['error' => $e->getMessage()])]);
        }
    }

    /**
     * Subscription pay by PayU Money failure.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function payumoney_fail(Request $request, $subscription_uid)
    {
        $subscription = \Acelle\Model\Subscription::findByUid($subscription_uid);
        $result = null;
        // Check billing information
        if ($this->checkBillingInformation($request, $subscription) !== true) {
            return redirect()->away($this->checkBillingInformation($request, $subscription));
        }

        $payment_method = \Acelle\Model\PaymentMethod::getByType(\Acelle\Model\PaymentMethod::TYPE_PAYU_MONEY);

        try {
            $order_id = $subscription->getOrderID();

            // validate and save posted data
            if ($request->isMethod('post')) {
                $result = $request->all();
                $payment = new Payment();
                $payment->subscription_id = $subscription->id;
                $payment->data = serialize($result);
                $payment->payment_method_id = $payment_method->id;
                $payment->status = \Acelle\Model\Payment::STATUS_FAILED;
                $payment->action = \Acelle\Model\Payment::ACTION_PAID;
                $payment->payment_method_name = trans('messages.' . $payment_method->type);
                $payment->order_id = $order_id;

                // billing information
                $billing_information = $request->session()->get('billing_information');
                if (isset($billing_information)) {
                    $payment->tax_number = $billing_information['tax_number'];
                    $payment->billing_address = $billing_information['billing_address'];
                    $request->session()->forget('billing_information');
                }

                $payment->save();
                //  $subscription->setPaid();
                return redirect()->action('PaymentController@failure', $subscription->uid);
                if ($result['status']=="failure") {
                    $subscription->setPaid();
                }
            }
        } catch (\Exception $e) {
            PaymentLog::error(trans('messages.something_went_wrong_with_payment') . ': ' .$e->getMessage());
            return view('somethingWentWrong', ['message' => trans('messages.something_went_wrong_with_payment', ['error' => $e->getMessage()])]);
        }
    }

    /**
     * Payment failure page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function failure(Request $request, $subscription_uid)
    {
        $subscription = \Acelle\Model\Subscription::findByUid($subscription_uid);

        return view('payments.failure', [
            'subscription' => $subscription
        ]);
    }

    /**
     * Subscription pay by paypal_create.
     *
     */
    public function create_payment(Request $request, $subscription_uid)
    {
        $access_token = $payment_method->getPayPalAccessToken();

        $PAYMENT='{
        "intent": "sale",
        "redirect_urls": {
          "return_url": "http://example.com/your_redirect_url.html",
          "cancel_url": "http://example.com/your_cancel_url.html"
        },
        "payer": {
          "payment_method":"paypal"
        },
        "transactions": [
          {
            "amount":{
              "total":"7.47",
              "currency":"USD"
            }
          }
        ]
      }';
        $ch = curl_init();

        // Request to PayPal enpoint
        curl_setopt($ch, CURLOPT_URL, "https://api" . ($this->getOption('environment') == 'sandbox' ? ".sandbox" : ''). ".paypal.com/v1/payments/payment");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type:application/json',
            'Authorization: Bearer ' . $access_token
        ]);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, $PAYMENT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        return $result;
    }


    /**
     * Subscription pay by Paddle card hook.
     * All type of webhook - depend on alert_name
     * alert_name= subscription_created,subscription_updated,subscription_cancelled,subscription_payment_succeeded,subscription_payment_refunded
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function paddle_webhook(Request $request)
    {
        $result = $request->all();
        $result['time'] = \Carbon\Carbon::now();
        \File::put('paddle-process.log', json_encode($result));
        if ($result['alert_name']=="subscription_created") {
            $info=explode("/", $result['passthrough']);
            $customer_id=$info[0];
            $subscription_uid=$info[1];
            $subscription = \Acelle\Model\Subscription::findByUid($subscription_uid);
            $order_id = $subscription->getOrderID();
            $payment_method = \Acelle\Model\PaymentMethod::getByType(\Acelle\Model\PaymentMethod::TYPE_PADDLE_CARD);

            $payment = new Payment();
            $payment->subscription_id = $subscription->id;
            $payment->payment_method_id = $payment_method->id;
            $payment->data = serialize($result);
            $payment->action = \Acelle\Model\Payment::ACTION_PAID;
            $payment->payment_method_name = trans('messages.' . $payment_method->type);
            $payment->order_id = $order_id;

            // billing information
            if ($subscription->isTaxBillingRequired() && file_exists('billing-information-' . $subscription->uid . '.log')) {
                $billing_information = json_decode(file_get_contents('billing-information-' . $subscription->uid . '.log'), true);
            }
            if (isset($billing_information)) {
                $payment->tax_number = $billing_information['tax_number'];
                $payment->billing_address = $billing_information['billing_address'];
                $request->session()->forget('billing_information');
            }

            $payment->status = \Acelle\Model\Payment::STATUS_SUCCESS; // : \Acelle\Model\Payment::STATUS_FAILED;

            $payment->save();
            $subscription->setPaid();

            // try enabling the subscription, proceed anyway if failed
            try {
                $subscription->enable();
                //update subscription data column with server payment gateway response
                $subscription->data=serialize($result);
                $subscription->save();
            } catch (\Exception $ex) {
                // just suppress the error and leave the subscription disabled
                LaravelLog::warning("Cannot enable subscription {$subscription->id}, proceed anyway");
            }
        }
    }
}
