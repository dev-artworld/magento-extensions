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

class Newsletter extends \Magento\Backend\App\Action
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

    /**
     * Check the permission to run it
     *
     * @return bool
     */
   /*  protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Cms::page');
    } */

    /**
     * Product action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */

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

    public function updateCustomers( $baseUrl, $parameters, $store_id ) {

        $apiKey = $this->apiKey($store_id);
        $url    = $baseUrl.'/api/customer/';

        $customsellApi = new CustomsellApi;
        
        $result = $customsellApi->exec( $apiKey, $url, $parameters );

        return $result;
    }

    public function execute()
    {

        $store_ids = array();

        $_websites = $this->_storeManager->getWebsites();

        $pushed_customer_ids = array();

        $customerItems = array();

        $last_customer_id = file_get_contents(dirname(__FILE__)."/logs/last_newsletter_id.log");

        foreach ( $_websites as $website ) {
            
            foreach ($website->getGroups() as $group) {

                $stores = $group->getStores();
                
                foreach ($stores as $store) {

                    $store_id = $store->getId();

                    $values = $this->_connection->fetchAll("select * from `core_config_data` where `path` = 'settings/settings/enable_frontend' and scope_id='$store_id'");

                    if($values[0]['value'] == 1){
        
                        $resultPage = $this->resultPageFactory->create();
                        //$resultPage->setActiveMenu('Customsell_Sync::menu_1');
                        $resultPage->getConfig()->getTitle()->prepend(__('Customsell Sync'));

                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                        $newsletterObj = $objectManager->create('Magento\Newsletter\Model\Subscriber')->getCollection()
                                                       ->addFieldToFilter('store_id', $store_id)
                                                       ->addFieldToFilter('subscriber_id', array('gt' => $last_customer_id))
                                                       ->setPageSize(2)
                                                       ->setCurPage(1);

                        foreach($newsletterObj as $newsletterObjdata ){

                            $pushed_customer_ids[] = $newsletterObjdata->getId();

                            $temp                               = array();
                            $temp['customer_id']                = $newsletterObjdata->getId();
                            $temp['customer_first_name']        = "";
                            $temp['customer_last_name']         = "";
                            $temp['customer_email']             = $newsletterObjdata->getEmail();
                            $temp['customer_address_line1']     = "";
                            $temp['customer_address_city']      = "";
                            $temp['customer_address_postcode']  = "";
                            $temp['customer_address_country']   = "";
                            $temp['customer_phone']             = "";
                            $temp['customer_is_subscribed']     = $newsletterObjdata->getStatus();
                            $temp['customer_address_county']    = "";
                          
                            $customerItems[] = $temp;

                            $last_customer_id_push = $newsletterObjdata->getId();
                             
                        }
                    }
                }
            }
        }

        if( count($customerItems) > 0 ){

            // Set last pushed order id in text file...

            $customers_inserted = "Following Newsletter Subscriber have been pushed to customsell on ".date('d-m-Y h:i');

            file_put_contents(dirname(__FILE__).'/logs/newsletter_info.log', print_r( $customers_inserted, true)."\n\n" );
                        
            file_put_contents(dirname(__FILE__).'/logs/newsletter_info.log', print_r( $pushed_customer_ids, true), FILE_APPEND );

            $customers_pushed_count = $this->_objectManager->create('Magento\Newsletter\Model\Subscriber')->getCollection()                                            ->setOrder('subscriber_id', 'desc')
                                                            ->setPageSize(1)
                                                            ->setCurPage(1);

            $customersItems_count_ids = array();

            foreach ($customers_pushed_count as $customers_count_ids){

                $customersItems_count_ids[] = $customers_count_ids->getId();

            }


            $pushed_customers__max_id = max($pushed_customer_ids);

            if($customersItems_count_ids[0] == $pushed_customers__max_id){
                $last_customers_id_pushed = '0';
            }else{
                $last_customers_id_pushed = $pushed_customers__max_id;
            }

            file_put_contents(dirname(__FILE__).'/logs/last_newsletter_id.log', print_r( $last_customers_id_pushed, true));

            $postData['customers'] = $customerItems;
            $customsellRun  = $this->ping($store_id);
            $result = $this->updateCustomers($customsellRun,$postData,$store_id);

            $position = array();

            foreach ($result->messages as $value) {
                $pos = strpos($value, 'created');
                if ($pos) {   
                    $position[] = 1;
                }
            }

            $customerItems_count = array();

            $customerObj_count = $this->_objectManager->create('Magento\Newsletter\Model\Subscriber')->getCollection();

            foreach ($customerObj_count as $customer_count){

                $customerItems_count[] = $customer_count;

            }

            $customer_item_count = count($position);

            $last_customer_count = file_get_contents(dirname(__FILE__)."/logs/newslettercount.log");

            $items_synced = explode("/", $last_customer_count);

            $total_item_synced = $customer_item_count + $items_synced[0];

            $customer_count_array = $total_item_synced."/".count($customerItems_count);

           
            file_put_contents(dirname(__FILE__).'/logs/newslettercount.log', print_r( $customer_count_array, true));
        }
        
    }
    
}
