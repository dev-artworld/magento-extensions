<?php


namespace Customsell\Sync\Observer\Controller\Product;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class ProductAdd implements \Magento\Framework\Event\ObserverInterface
{
	public function execute( \Magento\Framework\Event\Observer $observer )
    {
        $eventName 	=  $observer->getEvent()->getName();
        $time 		= date('d-m-Y H:s:i');

        $message 	= $time." | ".$eventName;
        
        file_put_contents(dirname(__FILE__)."/logs/eventAddName.log", print_r($message, true));        
    } 	
}