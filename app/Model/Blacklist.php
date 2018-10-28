<?php

/**
 * Blacklist class.
 *
 * Model for blacklisted email addresses
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

class Blacklist extends Model
{
    // Subscribers to import every time
    const IMPORT_STATUS_NEW = 'new';
    const IMPORT_STATUS_RUNNING = 'running';
    const IMPORT_STATUS_FAILED = 'failed';
    const IMPORT_STATUS_DONE = 'done';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'reason',
    ];

    /**
     * Get all items.
     *
     * @return collect
     */
    public static function getAll()
    {
        return self::select('blacklists.*');
    }

    /**
     * Get all items.
     *
     * @return collect
     */
    public function delist($customer = null)
    {
        if (is_null($customer)) {
            $sql = sprintf('UPDATE %s SET status = %s WHERE status = %s AND email = %s', table('subscribers'), db_quote(Subscriber::STATUS_SUBSCRIBED), db_quote(Subscriber::STATUS_BLACKLISTED), db_quote($this->email));
        } else {
            // slow: $sql = sprintf('UPDATE %s SET status = %s WHERE status = %s AND email = %s AND mail_list_id IN (SELECT id FROM %s WHERE customer_id = %s)', table('subscribers'), db_quote(Subscriber::STATUS_SUBSCRIBED), db_quote(Subscriber::STATUS_BLACKLISTED), db_quote($this->email), table('mail_lists'), $customer->id);
           $sql = sprintf('UPDATE %s s INNER JOIN %s m ON m.id = s.mail_list_id SET s.status = %s WHERE s.status = %s AND s.email = %s AND m.customer_id = %s', table('subscribers'), table('mail_lists'), db_quote(Subscriber::STATUS_SUBSCRIBED), db_quote(Subscriber::STATUS_BLACKLISTED), db_quote($this->email), $customer->id);
        }
        \DB::statement($sql);
    }

    /**
     * Blacklist all subscribers of the same email address.
     *
     * @return collect
     */
    public static function doBlacklist($customer = null)
    {
        // application wide blacklist
        // slow: $sql = sprintf('UPDATE %s s SET status = %s WHERE s.status = %s AND s.email IN (SELECT email FROM %s WHERE admin_id IS NOT NULL)', table('subscribers'), db_quote(Subscriber::STATUS_BLACKLISTED), db_quote(Subscriber::STATUS_SUBSCRIBED), table('blacklists'));
        $sql = sprintf('UPDATE %s s INNER JOIN %s b ON b.email = s.email SET status = %s WHERE s.status = %s AND b.admin_id IS NOT NULL', table('subscribers'), table('blacklists'), db_quote(Subscriber::STATUS_BLACKLISTED), db_quote(Subscriber::STATUS_SUBSCRIBED));
        \DB::statement($sql);

        // user wide blacklist
        if (!is_null($customer)) {
            // slow: $sql = sprintf('UPDATE %s s SET status = %s WHERE s.status = %s AND s.email IN (SELECT email FROM %s WHERE admin_id IS NULL) AND mail_list_id IN (SELECT id FROM %s WHERE customer_id = %s)', table('subscribers'), db_quote(Subscriber::STATUS_BLACKLISTED), db_quote(Subscriber::STATUS_SUBSCRIBED), table('blacklists'), table('mail_lists'), $customer->id);
            $sql = sprintf('UPDATE %s s INNER JOIN %s b ON b.email = s.email INNER JOIN %s m ON m.id = s.mail_list_id SET s.status = %s WHERE s.status = %s AND b.admin_id IS NULL AND m.customer_id = %s', table('subscribers'), table('blacklists'), table('mail_lists'), db_quote(Subscriber::STATUS_BLACKLISTED), db_quote(Subscriber::STATUS_SUBSCRIBED), $customer->id);
            \DB::statement($sql);
        }
    }

    /**
     * Filter items.
     *
     * @return collect
     */
    public static function filter($request)
    {
        $user = $request->user();
        $query = self::select('blacklists.*');

        // Keyword
        if (!empty(trim($request->keyword))) {
            foreach (explode(' ', trim($request->keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('blacklists.email', 'like', '%'.$keyword.'%');
                });
            }
        }

        // Other filter
        if (!empty($request->customer_id)) {
            $query = $query->where('blacklists.customer_id', '=', $request->customer_id);
        }

        if (!empty($request->admin_id)) {
            $query = $query->whereNull('customer_id');
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
     * Items per page.
     *
     * @var array
     */
    public static $itemsPerPage = 25;

    /**
     * Import from file.
     *
     * @return collect
     */
    public static function import($file, $system_job, $customer = null, $admin = null)
    {
        try {
            $content = \File::get($file);
            $lines = preg_split('/\r\n|\r|\n/', $content);

            $total = count($lines);

            // init the status
            $system_job->updateStatus([
                'status' => self::IMPORT_STATUS_RUNNING,
            ]);

            // update status, line count
            $system_job->updateStatus(['total' => $total]);

            // demo process
            $success = 0;
            foreach ($lines as $number => $line) {
                $email = trim(strtolower($line));

                // update status, finish one batch
                $system_job->updateStatus(['processed' => $number + 1]);

                // Add to blacklist
                if (\Acelle\Library\Tool::isValidEmail($email)) {
                    ++$success;
                    $system_job->updateStatus(['success' => $success]);

                    // Add to blacklist
                    if (isset($customer)) {
                        $customer->addEmaillToBlacklist($email);
                    }
                    if (isset($admin)) {
                        $admin->addEmaillToBlacklist($email);
                    }
                }
            }

            self::doBlacklist($customer);

            // Update status, finish all batches
            $system_job->updateStatus(['status' => self::IMPORT_STATUS_DONE]);
        } catch (\Exception $e) {
            // update job status
            $system_job->updateStatus([
                'status' => self::IMPORT_STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
