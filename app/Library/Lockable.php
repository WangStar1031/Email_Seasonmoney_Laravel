<?php

/**
 * Lockable class.
 *
 * Support concorrency-enabled classes
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
 * @todo separate the time-series and the quota stuffs
 */

namespace Acelle\Library;

class Lockable
{
    /**
     * Get exclusive lock
     *
     * @return void
     */
    private $file;
    
    public function __construct($file)
    {
        if (!file_exists($file)) {
            touch($file);
        }
        
        $this->file = $file;
    }
    
    /**
     * Get exclusive lock
     *
     * @return void
     */
    public function getExclusiveLock($callback)
    {
        $reader = fopen($this->file, 'w');
        while (true) {
            if (flock($reader, LOCK_EX | LOCK_NB)) {  // acquire an exclusive lock
                // execute the callback
                $callback($reader);

                fflush($reader);
                flock($reader, LOCK_UN);    // release the lock
                fclose($reader);
                break;
            }
        }
    }

    /**
     * Get shared lock
     *
     * @return void
     */
    public function getSharedLock($callback)
    {
        $reader = fopen($this->file, "r");
        while (true) {
            if (flock($reader, LOCK_SH | LOCK_NB)) {  // acquire an exclusive lock
                // execute the callback
                $callback($this);

                flock($reader, LOCK_UN);    // release the lock
                fclose($reader);
                break;
            }
        }
    }
}
