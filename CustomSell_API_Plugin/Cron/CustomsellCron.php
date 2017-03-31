<?php

namespace Customsell\Sync\Cron;

use \Customsell\Sync\Helper\Data;

class CustomsellCron
{

    protected $logger;

    // protected $helper;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(\Psr\Log\LoggerInterface $logger )
    {
        // $this->helper = $helper;    
        $this->logger = $logger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $time = Date('d-m-Y H:i:s');    
        $this->logger->addInfo("Cronjob CustomsellCron is executed. $time");
        // $customUrl = $this->helper->ping();

        file_put_contents(dirname(__FILE__)."/cron_runner.log", print_r('Executed at: '.$time, true),FILE_APPEND );
        // file_put_contents(dirname(__FILE__)."/cron_runner.log", print_r($customUrl, true),FILE_APPEND );
        file_put_contents(dirname(__FILE__)."/cron_runner.log", print_r("\n", true),FILE_APPEND );
    }
}
