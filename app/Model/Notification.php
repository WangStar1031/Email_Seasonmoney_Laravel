<?php

/**
 * Notification class.
 *
 * Model class for notifications
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

class Notification extends Model
{
    protected $table = 'notifications';
    
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    
    protected $fillable = [
        'type',
        'title',
        'message',
        'level',
        'uid',
    ];
    
    /**
     * Create an INFO notification
     *
     * @return notification record
     */
    public static function info($attributes)
    {
        $default = [
            'level' => static::LEVEL_INFO,
        ];
        
        $attributes = array_merge($default, $attributes);
        return static::add($attributes);
    }
    
    /**
     * Create an WARNING notification
     *
     * @return notification record
     */
    public static function warning($attributes)
    {
        $default = [
            'level' => static::LEVEL_WARNING,
        ];
        
        $attributes = array_merge($default, $attributes);
        return static::add($attributes);
    }
    
    /**
     * Create an ERROR notification
     *
     * @return notification record
     */
    public static function error($attributes)
    {
        $default = [
            'level' => static::LEVEL_ERROR,
        ];
        
        $attributes = array_merge($default, $attributes);
        return static::add($attributes);
    }
    
    /**
     * Actually insert the notification record to the notifications table
     *
     * @return notification record
     */
    public static function add($attributes)
    {
        $default = [
            'type' => get_called_class(),
            'uid' => uniqid(),
        ];
        $attributes = array_merge($default, $attributes);
        return self::create($attributes);
    }
    
    /**
     * Get top notifications
     *
     * @return notification records
     */
    public static function top($limit = 3)
    {
        return static::limit($limit)->get();
    }
    
    /**
     * Clean up notifications of the same type as the called class
     *
     * @return void
     */
    public static function cleanupSimilarNotifications()
    {
        static::where('type', get_called_class())->delete();
    }
    
}
