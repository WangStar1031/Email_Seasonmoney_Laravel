<?php

namespace Acelle\Jobs;

use Acelle\Library\Log as MailLog;

class SendConfirmationEmailJob extends SystemJob
{
    protected $subscribers;
    protected $mailList;

    /**
     * Create a new job instance.
     * @note: Parent constructors are not called implicitly if the child class defines a constructor.
     *        In order to run a parent constructor, a call to parent::__construct() within the child constructor is required.
     * 
     * @return void
     */
    public function __construct($subscribers, $mailList)
    {
        $this->subscribers = $subscribers;
        $this->mailList = $mailList;
        parent::__construct();

        // This line must go after the constructor
        $this->linkJobToMailList();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function linkJobToMailList()
    {
        $systemJob = $this->getSystemJob();
        $systemJob->data = $this->mailList->id;
        $systemJob->save();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        MailLog::info(sprintf('Start re-sending confirmation email to %s contacts', sizeof($this->subscribers)));
        foreach($this->subscribers as $subscriber) {
            try {
                MailLog::info(sprintf('Re-sending confirmation email to %s (%s)', $subscriber->email, $subscriber->id));
                $this->mailList->sendSubscriptionConfirmationEmail($subscriber);    
            } catch (\Exception $e) {
                MailLog::error(sprintf('Something went wrong when re-sending confirmation email for mail list %s. Error: %s', $this->mailList, $e->getMessage()));
                break;
            }
        }
        MailLog::info('Finish re-sending confirmation email');
    }
}
