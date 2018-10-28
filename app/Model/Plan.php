<?php

/**
 * Plan class.
 *
 * Model class for Plan
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

class Plan extends Model
{
    // Plan status
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ACTIVE = 'active';

    // Plan status
    const SENDING_SERVER_OPTION_SYSTEM = 'system';
    const SENDING_SERVER_OPTION_OWN = 'own';
    const SENDING_SERVER_OPTION_SUBACCOUNT = 'subaccount';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'frequency_amount', 'frequency_unit', 'price', 'color', 'currency_id', 'tax_billing_required', 'paddle_plan_id', 'admin_id',
    ];

    /**
     * The rules for validation.
     *
     * @var array
     */
    public function rules()
    {
        $rules = array(
            'name' => 'required',
            'currency_id' => 'required',
            'frequency_amount' => 'required|min:1',
            'frequency_unit' => 'required',
            'price' => 'required|min:0',
            'color' => 'required',
        );

        $options = self::defaultOptions();
        foreach ($options as $type => $option) {
            if ($type != 'sending_server_subaccount_uid') {
                $rules['options.'.$type] = 'required';
            }
        }

        if ($this->getOption('sending_server_option') == \Acelle\Model\Plan::SENDING_SERVER_OPTION_SUBACCOUNT) {
            $rules['options.sending_server_subaccount_uid'] = 'required';
        }

        return $rules;
    }

    /**
     * The rules for validation.
     *
     * @var array
     */
    public function apiRules()
    {
        $rules = array(
            'name' => 'required',
            'currency_id' => 'required',
            'frequency_amount' => 'required|min:1',
            'frequency_unit' => 'required',
            'price' => 'required|min:0',
            'color' => 'required',
        );

        if ($this->getOption('sending_server_option') == \Acelle\Model\Plan::SENDING_SERVER_OPTION_SUBACCOUNT) {
            $rules['options.sending_server_subaccount_uid'] = 'required';
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

    public function plansSendingServers()
    {
        return $this->hasMany('Acelle\Model\PlansSendingServer');
    }

    public function currency()
    {
        return $this->belongsTo('Acelle\Model\Currency');
    }

    public function subscriptions()
    {
        return $this->hasMany('Acelle\Model\Subscription');
    }

    public function plansEmailVerificationServers()
    {
        return $this->hasMany('Acelle\Model\PlansEmailVerificationServer');
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
            while (Plan::where('uid', '=', $uid)->count() > 0) {
                $uid = uniqid();
            }
            $item->uid = $uid;

            // Update custom order
            Plan::getAll()->increment('custom_order', 1);
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
        $query = self::select('plans.*');

        // Keyword
        if (!empty(trim($request->keyword))) {
            foreach (explode(' ', trim($request->keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('plans.name', 'like', '%'.$keyword.'%');
                });
            }
        }

        // filters
        $filters = $request->filters;

        if (!empty($request->admin_id)) {
            $query = $query->where('plans.admin_id', '=', $request->admin_id);
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
        }

        return $query;
    }

    /**
     * Disable plan.
     *
     * @return bool
     */
    public function disable()
    {
        $this->status = PaymentMethod::STATUS_INACTIVE;

        return $this->save();
    }

    /**
     * Enable plan.
     *
     * @return bool
     */
    public function enable()
    {
        $this->status = PaymentMethod::STATUS_ACTIVE;

        return $this->save();
    }

    /**
     * Color array.
     *
     * @return array
     */
    public static function colors($default)
    {
        return [
            ['value' => '#1482a0', 'text' => trans('messages.blue')],
            ['value' => '#008c6e', 'text' => trans('messages.green')],
            ['value' => '#917319', 'text' => trans('messages.brown')],
            ['value' => '#aa5064', 'text' => trans('messages.pink')],
            ['value' => '#555', 'text' => trans('messages.grey')],
        ];
    }

    /**
     * Frequency time unit options.
     *
     * @return array
     */
    public static function timeUnitOptions()
    {
        return [
            ['value' => 'day', 'text' => trans('messages.day')],
            ['value' => 'week', 'text' => trans('messages.week')],
            ['value' => 'month', 'text' => trans('messages.month')],
            ['value' => 'year', 'text' => trans('messages.year')],
            ['value' => 'unlimited', 'text' => trans('messages.plan_time_unlimited')],
        ];
    }

    /**
     * Default options for new plan.
     *
     * @return array
     */
    public static function defaultOptions()
    {
        $options = [
            'email_max' => '-1',
            'list_max' => '-1',
            'subscriber_max' => '-1',
            'subscriber_per_list_max' => '-1',
            'segment_per_list_max' => '-1',
            'campaign_max' => '-1',
            'automation_max' => '-1',
            'sending_quota' => '-1',
            'sending_quota_time' => '-1',
            'sending_quota_time_unit' => 'day',
            'max_process' => '1',
            'all_sending_servers' => 'yes',
            'max_size_upload_total' => '500',
            'max_file_size_upload' => '5',
            'unsubscribe_url_required' => 'yes',
            'access_when_offline' => 'no',
            //'create_sending_servers' => 'no',
            'create_sending_domains' => 'no',
            'sending_servers_max' => '-1',
            'sending_domains_max' => '-1',
            'all_email_verification_servers' => 'yes',
            'create_email_verification_servers' => 'no',
            'email_verification_servers_max' => '-1',
            'list_import' => 'yes',
            'list_export' => 'yes',
            'all_sending_server_types' => 'yes',
            'sending_server_types' => [],
            'sending_server_option' => self::SENDING_SERVER_OPTION_SYSTEM,
            'sending_server_subaccount_uid' => null,
            'api_access' => 'yes',
        ];

        // Sending server types
        foreach (\Acelle\Model\SendingServer::types() as $key => $type) {
            $options['sending_server_types'][$key] = 'yes';
        }

        return $options;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function getOptions()
    {
        if (empty($this->options)) {
            return self::defaultOptions();
        } else {
            $defaul_options = self::defaultOptions();
            $saved_options = json_decode($this->options, true);
            foreach ($defaul_options as $x => $group) {
                if (isset($saved_options[$x])) {
                    $defaul_options[$x] = $saved_options[$x];
                }
            }

            return $defaul_options;
        }
    }

    /**
     * Get option.
     *
     * @return string
     */
    public function getOption($name)
    {
        return $this->getOptions()[$name];
    }

    /**
     * Update sending servers.
     *
     * @return array
     */
    public function updateSendingServers($servers)
    {
        $this->plansSendingServers()->delete();
        foreach ($servers as $key => $param) {
            if ($param['check']) {
                $server = SendingServer::findByUid($key);
                $row = new PlansSendingServer();
                $row->plan_id = $this->id;
                $row->sending_server_id = $server->id;
                $row->fitness = $param['fitness'];
                $row->save();
            }
        }
    }

    /**
     * Multi process select options.
     *
     * @return array
     */
    public static function multiProcessSelectOptions()
    {
        $options = [['value' => 1, 'text' => trans('messages.one_single_process')]];
        for ($i = 2; $i < 101; ++$i) {
            $options[] = ['value' => $i, 'text' => $i];
        }

        return $options;
    }

    /**
     * Display group quota.
     *
     * @return array
     */
    public function displayQuota()
    {
        if ($this->getOption('sending_quota') == -1) {
            return trans('messages.unlimited');
        } elseif ($this->getOption('sending_quota_time') == -1) {
            return $this->getOption('sending_quota');
        } else {
            return strtolower(\Acelle\Library\Tool::format_number($this->getOption('sending_quota')).' '.trans('messages.'.\Acelle\Library\Tool::getPluralPrase('email', $this->getOption('sending_quota'))).' / '.$this->getOption('sending_quota_time').' '.trans('messages.'.\Acelle\Library\Tool::getPluralPrase($this->getOption('sending_quota_time_unit'), $this->getOption('sending_quota'))));
        }
    }

    /**
     * Display total quota.
     *
     * @return array
     */
    public function displayTotalQuota()
    {
        if ($this->getOption('email_max') == -1) {
            return trans('messages.unlimited');
        } else {
            return \Acelle\Library\Tool::format_number($this->getOption('email_max'));
        }
    }

    /**
     * Display max lists.
     *
     * @return array
     */
    public function displayMaxList()
    {
        if ($this->getOption('list_max') == -1) {
            return trans('messages.unlimited');
        } else {
            return \Acelle\Library\Tool::format_number($this->getOption('list_max'));
        }
    }

    /**
     * Display max subscribers.
     *
     * @return array
     */
    public function displayMaxSubscriber()
    {
        if ($this->getOption('subscriber_max') == -1) {
            return trans('messages.unlimited');
        } else {
            return \Acelle\Library\Tool::format_number($this->getOption('subscriber_max'));
        }
    }

    /**
     * Display max campaign.
     *
     * @return array
     */
    public function displayMaxCampaign()
    {
        if ($this->getOption('campaign_max') == -1) {
            return trans('messages.unlimited');
        } else {
            return \Acelle\Library\Tool::format_number($this->getOption('campaign_max'));
        }
    }

    /**
     * Display max campaign.
     *
     * @return array
     */
    public function displayMaxSizeUploadTotal()
    {
        if ($this->getOption('max_size_upload_total') == -1) {
            return trans('messages.unlimited');
        } else {
            return \Acelle\Library\Tool::format_number($this->getOption('max_size_upload_total'));
        }
    }

    /**
     * Display max campaign.
     *
     * @return array
     */
    public function displayFileSizeUpload()
    {
        if ($this->getOption('max_file_size_upload') == -1) {
            return trans('messages.unlimited');
        } else {
            return $this->getOption('max_file_size_upload');
        }
    }

    /**
     * Display sending ervers permission.
     *
     * @return array
     */
    public function displayAllowCreateSendingServer()
    {
        if ($this->getOption('sending_server_option') != \Acelle\Model\Plan::SENDING_SERVER_OPTION_OWN) {
            return trans('messages.feature_not_allow');
        }

        if ($this->getOption('sending_servers_max') == -1) {
            return trans('messages.unlimited');
        } else {
            return $this->getOption('sending_servers_max');
        }
    }

    /**
     * Display sending domains permission.
     *
     * @return array
     */
    public function displayAllowCreateSendingDomain()
    {
        if ($this->getOption('create_sending_domains') == 'no') {
            return trans('messages.feature_not_allow');
        }

        if ($this->getOption('sending_domains_max') == -1) {
            return trans('messages.unlimited');
        } else {
            return $this->getOption('sending_domains_max');
        }
    }

    /**
     * Get customer select2 select options.
     *
     * @return array
     */
    public static function select2($request)
    {
        $data = ['items' => [], 'more' => true];

        $query = \Acelle\Model\Plan::getAllActive()->orderBy('custom_order', 'asc');
        if (isset($request->q)) {
            $keyword = $request->q;
            $query = $query->where(function ($q) use ($keyword) {
                $q->orwhere('plans.name', 'like', '%'.$keyword.'%');
            });
        }

        // Read all check
        if ($request->user()->admin && !$request->user()->admin->can('readAll', new \Acelle\Model\Plan())) {
            $query = $query->where('plans.admin_id', '=', $request->user()->admin->id);
        }

        foreach ($query->limit(20)->get() as $plan) {
            $data['items'][] = ['id' => $plan->uid, 'text' => htmlspecialchars($plan->name).'|||'.htmlspecialchars(\Acelle\Library\Tool::format_price($plan->price, $plan->currency->format))];
        }

        return json_encode($data);
    }

    /**
     * Get all items.
     *
     * @return collect
     */
    public static function getAllActiveWithDefault($admin = null)
    {
        $query = self::getAll()
            ->where('plans.status', '=', self::STATUS_ACTIVE);

        if (isset($admin) && !$admin->can('readAll', new \Acelle\Model\Plan())) {
            $query = $query->where('plans.admin_id', '=', $admin->id);
        }

        $query = $query->orderBy('custom_order', 'asc');

        return $query;
    }

    /**
     * Get all items.
     *
     * @return collect
     */
    public static function getAllActive($admin = null)
    {
        $query = self::getAllActiveWithDefault()->where('plans.is_default', '=', false);

        return $query;
    }

    /**
     * Display plan time.
     *
     * @return collect
     */
    public function displayFrequencyTime()
    {
        // unlimited
        if ($this->isTimeUnlimited()) {
            return trans('messages.plan_time_unlimited');
        }

        return $this->frequency_amount.' '.\Acelle\Library\Tool::getPluralPrase($this->frequency_unit, $this->frequency_amount);
    }

    /**
     * Subscriptions count.
     *
     * @return int
     */
    public function subscriptionsCount()
    {
        return $this->subscriptions()->count();
    }

    /**
     * Customers count.
     *
     * @return int
     */
    public function cutomersCount()
    {
        return $this->subscriptions()->distinct('customer_id')->count('customer_id');
    }

    /**
     * Frequency time unit options.
     *
     * @return array
     */
    public static function quotaTimeUnitOptions()
    {
        return [
            ['value' => 'minute', 'text' => trans('messages.minute')],
            ['value' => 'hour', 'text' => trans('messages.hour')],
            ['value' => 'day', 'text' => trans('messages.day')],
        ];
    }

    /**
     * Check if plan time is unlimited.
     *
     * @return bool
     */
    public function isTimeUnlimited()
    {
        return $this->frequency_unit == 'unlimited';
    }

    /**
     * Get default-system plan.
     *
     * @return Plan
     */
    public static function getDefaultPlan()
    {
        return self::where('is_default', '=', true)->first();
    }

    /**
     * Fill email verification servers.
     */
    public function fillPlansEmailVerificationServers($params)
    {
        $this->plansEmailVerificationServers = collect([]);
        foreach ($params as $key => $param) {
            if ($param['check']) {
                $server = \Acelle\Model\EmailVerificationServer::findByUid($key);
                $row = new \Acelle\Model\PlansEmailVerificationServer();
                $row->plan_id = $this->id;
                $row->server_id = $server->id;
                $this->plansEmailVerificationServers->push($row);
            }
        }
    }

    /**
     * Update email verification servers.
     *
     * @return array
     */
    public function updateEmailVerificationServers($servers)
    {
        $this->plansEmailVerificationServers()->delete();
        foreach ($servers as $key => $param) {
            if ($param['check']) {
                $server = \Acelle\Model\EmailVerificationServer::findByUid($key);
                $row = new \Acelle\Model\PlansEmailVerificationServer();
                $row->plan_id = $this->id;
                $row->server_id = $server->id;
                $row->save();
            }
        }
    }

    /**
     * Display sending ervers permission.
     *
     * @return array
     */
    public function displayAllowCreateEmailVerificationServer()
    {
        if ($this->getOption('create_email_verification_servers') == 'no') {
            return trans('messages.feature_not_allow');
        }

        if ($this->getOption('email_verification_servers_max') == -1) {
            return trans('messages.unlimited');
        } else {
            return $this->getOption('email_verification_servers_max');
        }
    }

    /**
     * Check if plan is default.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->is_default;
    }

    /**
     * Set option.
     */
    public function setOption($name, $value)
    {
        $options = json_decode($this->options, true);
        $options[$name] = $value;

        $this->options = json_encode($options);
        $this->save();
    }

    /**
     * Fill option from request.
     */
    public function fillOptions($options)
    {
        $defaultOptions = self::defaultOptions();
        $dbOptions = $this->options ? json_decode($this->options, true) : $defaultOptions;
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $dbOptions[$key] = $value;
            }
        }
        $this->options = json_encode($dbOptions);
    }

    /**
     * Copy new plan.
     */
    public function copy($name)
    {
        $copy = $this->replicate(['cache', 'last_error', 'run_at']);
        $copy->name = $name;
        $copy->created_at = \Carbon\Carbon::now();
        $copy->updated_at = \Carbon\Carbon::now();
        $copy->status = PaymentMethod::STATUS_ACTIVE;
        $copy->custom_order = 0;
        $copy->save();
    }

    /**
     **All Payment Methods: create plan on server.
     **/
    public function createPlanOnServer($data, $plan)
    {
        $planMethodlist = \Acelle\Model\PaymentMethod::getAllActive();
        if (count($planMethodlist) > 0) {
            foreach ($planMethodlist as $payment_method) {
                if ($payment_method->type == PaymentMethod::TYPE_PADDLE_CARD) {
                    if ($data !== 'unlimited') {
                        $vendorId = $payment_method->getOption('vendor_id');
                        $vendor_auth_code = $payment_method->getOption('vendor_auth_code');
                        // plan_type: accepts day, week, month, year
                        $json_body = 'vendor_id='.$vendorId.'&vendor_auth_code='.$vendor_auth_code.'&plan_name='.$data['name'].'&plan_trial_days=0&plan_type='.$data['frequency_unit'].'&plan_length='.$data['frequency_amount'].'&main_currency_code=USD&initial_price_usd=0.00&recurring_price_usd='.$data['price'];
                        $this->createPaddlePlan($json_body, $plan);
                    }
                }
            }
        }

        return true;
    }

    /**
     ** Create paddle plan on servers.
     **/
    public function createPaddlePlan($json_body, $plan)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://vendors.paddle.com/api/2.0/subscription/plans_create');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);
        curl_setopt($ch, CURLOPT_POST, 1);
        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        //close connection
        curl_close($ch);
        $result = json_decode($result);
        if ($result->success) {
            $paddle_plan_id = $result->response->product_id;
            $plan->paddle_plan_id = $paddle_plan_id;
            $plan->save();
        }

        return true;
    }
}
