<?php
/**
 *
 * Copyright © 2015 Customsellcommerce. All rights reserved.
 */
namespace Customsell\Sync\Controller\Adminhtml\CustomsellBackend;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
//use \Customsell\Sync\Helper\Data;
use \Customsell\Sync\ApiInterface\CustomsellApi;
use \Magento\Store\Model\StoreManagerInterface;

class Checkout extends \Magento\Backend\App\Action
{

    //protected $helper;
    protected $repository;
    protected $_storeManager;
    protected $_objectManager;    

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    protected $_resource;

    protected $_connection;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(        
        Context $context,
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager      
    ) {       
        
        $this->_storeManager = $storeManager;
        
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;

        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->_resource = $this->_objectManager->create('\Magento\Framework\App\ResourceConnection');
        
        $this->_connection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
  
    }
    
    public function apiKey($storeid){
          
        $values = $this->_connection->fetchAll("select * from `core_config_data` where `path` = 'settings/settings/apikey' and scope_id='$storeid'");

        return $values[0]['value'];
    }

    public function ping($store_id){
        
        $apiKey     = $this->apiKey($store_id);

        /* validate API Url */
        $url        = 'https://api.customsellsystems.com/api/validate/';
        $parameters = array( 'api_key' => $apiKey );        
        
        $customsellApi = new CustomsellApi;
        $result = $customsellApi->exec( $apiKey, $url, $parameters );

        return $result->customsell_url;
    }

    public function updateCheckout( $baseUrl, $parameters, $store_id ) {

        $apiKey = $this->apiKey($store_id);
        $url    = $baseUrl.'/api/orderabandoned/';

        $parameters['client_reference'] = 'customsell_magento_extension';
        $parameters['api_version']      = '0.04';

        $customsellApi = new CustomsellApi;
        
        $result = $customsellApi->exec( $apiKey, $url, $parameters );

        return $result;
    }

    public function execute()
     {   

        $pushed_cart_ids = array();

        $om = \Magento\Framework\App\ObjectManager::getInstance();

        $collection = $om->get('Magento\Reports\Model\ResourceModel\Quote\Collection');

        $store_id = $this->_storeManager->getStore()->getId();

        $collection->prepareForAbandonedReport($store_id);

        $rows = $collection->load();

        foreach ($rows as $cart){

            $pushed_cart_ids[] = $cart->getData();
        }

       // file_put_contents(dirname(__FILE__).'/checkout_ab.log', print_r($pushed_cart_ids, true)."\n" );
    }    
}
