<?php


namespace Customsell\Sync\Observer\Controller\Product;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class ProductEdit implements \Magento\Framework\Event\ObserverInterface
{	
	protected $_request;
    protected $_resource;
    protected $_scopeConfig;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_request     = $request;
        $this->_resource    = $resource;
        $this->_scopeConfig = $scopeConfig;
    }


	public function execute( \Magento\Framework\Event\Observer $observer )
    {
        $eventName 	=  $observer->getEvent()->getName();
        $time 		= date('d-m-Y H:s:i');

        $message 	= $time." | ".$eventName;

        $postData  = $this->_request->getPost()->toArray();

        file_put_contents(dirname(__FILE__)."/logs/postDataEdit.log", print_r($postData, true));

        file_put_contents(dirname(__FILE__)."/logs/eventEditName.log", print_r($message, true));
        
    } 	
}