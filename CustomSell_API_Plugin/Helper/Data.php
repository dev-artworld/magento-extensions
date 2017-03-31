<?php

namespace Customsell\Sync\Helper;

use \Customsell\Sync\ApiInterface\CustomsellApi;

class Data extends \Magento\Framework\App\Helper\AbstractHelper{

	protected $_storeManager;
    public $_objectManager;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context, 
        \Magento\Store\Model\StoreManagerInterface $storeManager 
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($context);
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    public function getConfigValue($value = '') {
        return $this->scopeConfig
                ->getValue(
                        $value,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        );            
    }

    public function getModuleStatus() {
    	$status    = $this->scopeConfig->getValue( 'settings/settings/enable_frontend', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);        
        return $status;
    }

    public function getCustomerAttributes() {
    	$customerAttributes    = $this->scopeConfig->getValue( 'settings/settings/customer_attributes', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);    
        return $customerAttributes;
    }

    public function getApiKey() {

        $resource = $this->_objectManager->create('\Magento\Framework\App\ResourceConnection');
        
        $connection = $resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
  
        $values = $connection->fetchAll("select * from `core_config_data` where `path` = 'settings/settings/apikey'");

    	$apikey    = $values[0]['value'];

        file_put_contents(dirname(__FILE__)."/api.log", print_r($apikey, true));

        return $apikey;
    }

    // 

    public function ping(){
		
		$apiKey 	= $this->getApiKey();

        /* validate API Url */
		$url 		= 'https://api.customsellsystems.com/api/validate/';
		$parameters = array( 'api_key' => $apiKey );		
    	
    	$customsellApi = new CustomsellApi;
    	$result = $customsellApi->exec( $apiKey, $url, $parameters );

        return $result->customsell_url;
    }

    public function updateCategories( $baseUrl, $parameters ) {

    	$apiKey = $this->getApiKey();
		$url 	= $baseUrl.'/api/category/';

        $parameters['client_reference'] = 'customsell_magento_extension';
        $parameters['api_version']      = '0.04';

    	$customsellApi = new CustomsellApi;
        
        
    	$result = $customsellApi->exec( $apiKey, $url, $parameters );

        return $result;
    }


    function runCron() {

        $logdata = "Executed at: ".date('d-m-Y H:i:s');
        file_put_contents(dirname(__FILE__)."/model.log", print_r($logdata, true),FILE_APPEND );
        file_put_contents(dirname(__FILE__)."/model.log", print_r("\n", true),FILE_APPEND );

    }    
}