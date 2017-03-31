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

class Product extends \Magento\Backend\App\Action
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

    public function updateProducts( $baseUrl, $parameters, $store_id ) {

        $apiKey = $this->apiKey($store_id);
        $url    = $baseUrl.'/api/product/';

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

        $pushed_product_ids = array();

        $productItems = array();

        // get the last product id from text file..
        $last_product_id = file_get_contents(dirname(__FILE__)."/logs/last_product_id.log");       


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

                        $productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');

                        $collection = $productCollection->create()
                                    ->addAttributeToSelect('*')
                                    ->addStoreFilter($store_id)
                                    ->addAttributeToFilter('entity_id', array('gt' => $last_product_id) )
                                    ->setPageSize(2)
                                    ->setCurPage(1)
                                    ->load();

                        foreach ($collection as $product){

                            $pushed_product_ids[] = $product->getId();

                            $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface'); 
                            $currentStore = $storeManager->getStore();

                            $mediaUrl = $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

                            $image_url = $mediaUrl."catalog/product".$product->getImage();

                            $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
                            $stock =  $StockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());

                            $category_id = implode(",", $product->getCategoryIds());

                            $temp                               = array();
                            $temp['product_id']                 = $product->getId();
                            $temp['product_sku']                = $product->getSku();
                            $temp['product_name']               = $product->getName();
                            $temp['product_url']                = $product->getProductUrl();
                            $temp['product_image_url']          = $image_url;
                            $temp['product_description']        = $product->getDescription();
                            $temp['product_stock_level']        = $stock;
                            $temp['product_category_id']        = $category_id;
                            $temp['product_price']              = $product->getPrice();
                          
                            $productItems[] = $temp;

                            $last_product_id_push = $product->getId();             
                        }                        
                    }
                }
            }
        }

        $productCollection_count = $this->_objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');     

        if( count($productItems) > 0 ){

            // Set last pushed order id in text file...

            $products_inserted = "Following products have been pushed to customsell on ".date('d-m-Y h:i');

            file_put_contents(dirname(__FILE__).'/logs/products_info.log', print_r( $products_inserted, true)."\n\n" );
                        
            file_put_contents(dirname(__FILE__).'/logs/products_info.log', print_r( $pushed_product_ids, true), FILE_APPEND );

            $product_pushed_count = $productCollection_count->create()
                                    ->addAttributeToSelect('*')
                                    ->setOrder('entity_id')
                                    ->setPageSize(1)
                                    ->setCurPage(1)
                                    ->load();

            $productItems_count_ids = array();

            foreach ($product_pushed_count as $product_count_ids){

                $productItems_count_ids[] = $product_count_ids->getId();

            }

            $pushed_product__max_id = max($pushed_product_ids);

            if($productItems_count_ids[0] == $pushed_product__max_id){
                $last_product_id_pushed = '0';
            }else{
                $last_product_id_pushed = $pushed_product__max_id;
            }

            file_put_contents(dirname(__FILE__).'/logs/last_product_id.log', print_r( $last_product_id_pushed, true));

            $postData['products'] = $productItems;        
            $customsellRun  = $this->ping($store_id);        
            $result = $this->updateProducts($customsellRun,$postData,$store_id);

            $position = array();

            foreach ($result->messages as $value) {
                $pos = strpos($value, 'created');
                if ($pos) {   
                    $position[] = 1;
                }
            }

            $productItems_count = array();

            $collection_count = $productCollection_count->create()
                                ->addAttributeToSelect('*')
                                ->load();

            foreach ($collection_count as $product_count){

                $productItems_count[] = $product_count;

            }

            $product_item_count = count($position);

            $last_product_count = file_get_contents(dirname(__FILE__)."/logs/productcount.log");

            $items_synced = explode("/", $last_product_count);

            $total_item_synced = $product_item_count + $items_synced[0];

            $product_count_array = $total_item_synced."/".count($productItems_count);

            file_put_contents(dirname(__FILE__).'/logs/productcount.log', print_r( $product_count_array, true));

        }
   }    
}
