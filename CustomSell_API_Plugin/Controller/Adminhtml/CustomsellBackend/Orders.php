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

class Orders extends \Magento\Backend\App\Action
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

    public function updateOrders( $baseUrl, $parameters, $store_id ) {

        $apiKey = $this->apiKey($store_id);
        $url    = $baseUrl.'/api/order/';

        $parameters['client_reference'] = 'customsell_magento_extension';
        $parameters['api_version']      = '0.04';

        $customsellApi = new CustomsellApi;
        
        $result = $customsellApi->exec( $apiKey, $url, $parameters );

        return $result;
    }

    public function execute()
    {

        $store_ids = array();

        $_websites = $this->_storeManager->getWebsites();

        $pushed_order_ids = array();

        $orderItems = array();

        // get the last order id from text file..
        $last_order_id = file_get_contents(dirname(__FILE__)."/logs/last_order_id.log");

        foreach ( $_websites as $website ) {
            
            foreach ($website->getGroups() as $group) {

                $stores = $group->getStores();
                
                foreach ($stores as $store) {

                    $store_id = $store->getId();

                    $values = $this->_connection->fetchAll("select * from `core_config_data` where `path` = 'settings/settings/enable_frontend' and scope_id='$store_id'");

                    if($values[0]['value'] == 1){

                        $resultPage = $this->resultPageFactory->create();
                        
                        $resultPage->getConfig()->getTitle()->prepend(__('Customsell Sync'));

                        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();

                        $orders = $objectManager->get('Magento\Sales\Model\Order')
                                                ->getCollection()
                                                ->addFieldToFilter('store_id', $store_id)
                                                ->addAttributeToFilter('entity_id', array('gt' => $last_order_id) )
                                                ->setPageSize(2)
                                                ->setCurPage(1);

                        foreach($orders as $order){     

                            $pushed_order_ids[] = $order->getData("entity_id");

                            $items = $order->getAllVisibleItems();

                            $order_items = array();

                            foreach ($items as $key => $_item) {
                                
                                $order_items[] = array('product_id' => $_item->getProductId(), 'product_quantity'  => intval($_item->getQtyOrdered()), "line_total"=> $_item->getPrice()*$_item->getQtyOrdered() );
                                
                            }

                            $temp                               = array();
                            $temp['order_reference']            = $order->getData("entity_id");
                            $temp['order_time']                 = $order->getData("created_at");
                            $temp['order_status']               = $order->getData("status");
                            $temp['order_total']                = $order->getData("grand_total");
                            $temp['order_email']                = $order->getData("customer_email");
                            $temp['items']                      = $order_items;
                            //$temp['order_view_url']             = "test";
                            $temp['guest_order']                = $order->getData("customer_is_guest");
                          
                            $orderItems[] = $temp; 

                            $last_order_id_push = $order->getData("entity_id");
                        }
                    }
                }
            }
        }

        if( count($orderItems) > 0 ){

            // Set last pushed order id in text file...

            $orders_inserted = "Following orders have been pushed to customsell on ".date('d-m-Y h:i');

            file_put_contents(dirname(__FILE__).'/logs/orders_info.log', print_r( $orders_inserted, true)."\n\n" );
                        
            file_put_contents(dirname(__FILE__).'/logs/orders_info.log', print_r( $pushed_order_ids, true), FILE_APPEND );

            $orders_pushed_count = $objectManager->get('Magento\Sales\Model\Order')
                                                ->getCollection()
                                                ->setOrder('entity_id')
                                                ->setPageSize(1)
                                                ->setCurPage(1);

            $ordersItems_count_ids = array();

            foreach ($orders_pushed_count as $orders_count_ids){

                $ordersItems_count_ids[] = $orders_count_ids->getData("entity_id");

            }

            $pushed_orders__max_id = max($pushed_order_ids);

            if($ordersItems_count_ids[0] == $pushed_orders__max_id){
                $last_orders_id_pushed = '0';
            }else{
                $last_orders_id_pushed = $pushed_orders__max_id;
            }

            file_put_contents(dirname(__FILE__).'/logs/last_order_id.log', print_r( $last_orders_id_pushed, true));

            $postData['orders'] = $orderItems;
            $customsellRun  = $this->ping($store_id);        
            $result = $this->updateOrders($customsellRun,$postData,$store_id);

            $position = array();

            foreach ($result->messages as $value) {
                $pos = strpos($value, 'created');
                if ($pos) {   
                    $position[] = 1;
                }
            }

            $orderItems_count = array();

            $ordersObj_count = $this->_objectManager->get('Magento\Sales\Model\Order')
                                           ->getCollection();

            foreach ($ordersObj_count as $order_count){

                $orderItems_count[] = $order_count;

            }

            $order_item_count = count($position);

            $last_order_count = file_get_contents(dirname(__FILE__)."/logs/ordercount.log");

            $items_synced = explode("/", $last_order_count);

            $total_item_synced = $order_item_count + $items_synced[0];

            $order_count_array = $total_item_synced."/".count($orderItems_count);

            file_put_contents(dirname(__FILE__).'/logs/ordercount.log', print_r( $order_count_array, true));
        }
    }    
}
