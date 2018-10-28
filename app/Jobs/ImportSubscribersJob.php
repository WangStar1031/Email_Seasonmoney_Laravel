<?php

namespace Acelle\Jobs;

use Acelle\Library\Log as CustomLog;

class ImportSubscribersJob extends ImportExportJob
{
    // @todo this should better be a constant
    protected $file;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($mailList, $customer, $file)
    {
        // call parent's constructor
        parent::__construct($mailList, $customer);

        // Upload csv
        $this->file = join_paths($this->path, 'data.csv');
        rename($file, $this->file);
        chmod($this->file, 0777);

        // Update system job status
        // init the status
        $this->updateStatus([
            'mail_list_uid' => $mailList->uid,
            'customer_id' => $customer->id,
            'status' => \Acelle\Model\MailList::IMPORT_STATUS_NEW,
            'error_message' => '',
            'total' => 0,
            'processed' => 0,
        ]);
    }

    /**
     * Get import file name.
     *
     * @return string file path
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Get import file name.
     *
     * @return void
     */
    public function updateStatus($data)
    {
        $systemJobModel = $this->getSystemJob();
        $json = ($systemJobModel->data) ? json_decode($systemJobModel->data, true) : [ 'log' => $this->getLog() ];
        $systemJobModel->data = json_encode(array_merge($json, $data));
        $systemJobModel->save();
    }

    /**
     * Get import log file
     *
     * @return string file path
     */
    public function getLog()
    {
        return join_paths($this->getPath(), 'import.log');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->mailList->import2($this->file, $this->customer, $this);
    }

    /**
     * Get the job's logger
     *
     * @return object job logger
     */
    public function getLogger()
    {
        $log_name = 'importer';
        $logger = CustomLog::create( $this->getLog(), $log_name );
        return $logger;
    }
}
