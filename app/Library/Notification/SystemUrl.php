<?php

/**
 * CronJobNotification class.
 *
 * Notification for cronjob issue
 *
 * LICENSE: This product includes software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   Acelle Library
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

namespace Acelle\Library\Notification;

use Acelle\Model\Setting;
use Acelle\Model\Notification;

class SystemUrl extends Notification
{
    /**
     * Check if CronJob is recently executed and log a notification if not
     *
     * @return void
     */
    public static function check()
    {
        self::cleanupSimilarNotifications();
        
        $current = url('/');
        $cached = Setting::get('url_root');
        if ($current != $cached) {
            $warning = [
                'title' => trans('messages.admin.notification.system_url_title'),
                'message' => trans('messages.admin.notification.system_url_not_match', ['cached' => $cached, 'current' => $current]),
            ];

            self::warning($warning);
        }
    }
}