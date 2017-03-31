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

class Promo extends \Magento\Backend\App\Action
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

    public function updatePromos( $baseUrl, $parameters, $store_id ) {

        $apiKey = $this->apiKey($store_id);
        $url    = $baseUrl.'/api/promo/';

        $customsellApi = new CustomsellApi;
        
        $result = $customsellApi->exec( $apiKey, $url, $parameters );

        return $result;
    }

    public function execute()
    {
        
        $store_ids = array();

        $_websites = $this->_storeManager->getWebsites();

        $pushed_coupon_ids = array();

        $couponItems = array();

        // get the last coupon id from text file..
        $last_coupon_id = file_get_contents(dirname(__FILE__)."/logs/last_coupon_id.log");

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

                        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();

                        $coupon = $objectManager->create('Magento\SalesRule\Model\Coupon')
                                                ->getCollection()
                                                //->addFieldToFilter('store_id', $store_id)
                                                ->addFieldToFilter('coupon_id', array('gt' => $last_coupon_id) )
                                                ->setPageSize(2)
                                                ->setCurPage(1);

                        foreach($coupon as $couponDatamodel1){

                                $pushed_coupon_ids[] = $couponDatamodel1->getData("coupon_id");

                                $temp                  = array();
                                $temp['promo_code']    = $couponDatamodel1->getData("code");
                              
                                $couponItems[] = $temp;

                                $last_coupon_id_push = $couponDatamodel1->getData("entity_id");
                        }
                    }
                }
            }
        }

        if( count($couponItems) > 0 ){

            // Set last pushed order id in text file...

            $coupon_inserted = "Following coupons have been pushed to customsell on ".date('d-m-Y h:i');

            file_put_contents(dirname(__FILE__).'/logs/coupons_info.log', print_r( $coupon_inserted, true)."\n\n" );
                        
            file_put_contents(dirname(__FILE__).'/logs/coupons_info.log', print_r( $pushed_coupon_ids, true), FILE_APPEND );

            $coupon_pushed_count = $objectManager->create('Magento\SalesRule\Model\Coupon')->getCollection()
                                                ->setOrder('coupon_id','desc')
                                                ->setPageSize(1)
                                                ->setCurPage(1);

            $couponItems_count_ids = array();

            foreach ($coupon_pushed_count as $coupon_count_ids){

                $couponItems_count_ids[] = $coupon_count_ids->getId();

            }

            $pushed_coupon__max_id = max($pushed_coupon_ids);

            if($couponItems_count_ids[0] == $pushed_coupon__max_id){
                $last_coupon_id_pushed = '0';
            }else{
                $last_coupon_id_pushed = $pushed_coupon__max_id;
            }

            file_put_contents(dirname(__FILE__).'/logs/last_coupon_id.log', print_r( $last_coupon_id_pushed, true));

            $postData['promos'] = $couponItems;
            $customsellRun  = $this->ping($store_id);
            $result = $this->updatePromos($customsellRun,$postData,$store_id);

            $position = array();

            foreach ($result->messages as $value) {
                $pos = strpos($value, 'created');
                if ($pos) {   
                    $position[] = 1;
                }
            }

            $couponItems_count = array();

            $couponObj_count = $this->_objectManager->create('Magento\SalesRule\Model\Coupon')->getCollection();

            foreach ($couponObj_count as $coupon_count){

                $couponItems_count[] = $coupon_count;

            }

            $coupon_item_count = count($position);

            $last_coupon_count = file_get_contents(dirname(__FILE__)."/logs/couponcount.log");

            $items_synced = explode("/", $last_coupon_count);

            $total_item_synced = $coupon_item_count + $items_synced[0];

            $coupon_count_array = $total_item_synced."/".count($couponItems_count);

           
            file_put_contents(dirname(__FILE__).'/logs/couponcount.log', print_r( $coupon_count_array, true));
        }

    }
    
}
