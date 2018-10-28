<?php

namespace Acelle\Jobs;

use Acelle\Jobs\Job;

class ExportSubscribersJob extends ImportExportJob
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($mailList, $customer)
    {
        // call parent's constructor
        parent::__construct($mailList, $customer);
        
        
        $systemJob = $this->getSystemJob();
        // set failed
        $systemJob->data = json_encode([
            "mail_list_uid" => $mailList->uid,
            "status" => "new",
            "message" => trans('messages.starting'),
            "total" => 0,
            "success" => 0,
            "error" => 0,
            "percent" => 0
        ]);
        $systemJob->save();
    }
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $job = $this;
        
        // Set language
        try {
            if (is_object($job->customer) && is_object($job->customer->language)) {
                \App::setLocale($job->customer->language->code);
                \Carbon\Carbon::setLocale($job->customer->language->code);
            }
        } catch (\Exception $e) {
        }
        
        try {            
            \Acelle\Model\MailList::export($job->mailList, $job->customer, $job);
        } catch (\Exception $e) {
            $systemJob = $this->getSystemJob();
        
            // set failed
            $systemJob->data = json_encode([
                "mail_list_uid" => $job->mailList->uid,
                "status" => "failed",
                "message" => $e->getMessage(),
                "total" => 0,
                "success" => 0,
                "error" => 0,
                "percent" => 0
            ]);
            $systemJob->save();
        }
    }
}
