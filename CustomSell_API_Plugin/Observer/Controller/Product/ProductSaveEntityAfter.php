<?php


namespace Customsell\Sync\Observer\Controller\Product;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Customsell\Sync\ApiInterface\CustomsellApi;

class ProductSaveEntityAfter implements \Magento\Framework\Event\ObserverInterface
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
        $postData  = $this->_request->getPost()->toArray();         
        $eventName =  $observer->getEvent()->getName(); 

        $customsellWebApi = new \Customsell\Sync\ApiInterface\CustomsellApi;
        $customsellWebApi->saveProduct($postData);
                
        file_put_contents(dirname(__FILE__)."/logs/afterSave.log", print_r($postData, true),FILE_APPEND );
       
        
    } 
}
