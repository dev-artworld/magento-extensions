<?php
/**
 *
 * Copyright © 2015 Customsellcommerce. All rights reserved.
 */
namespace Customsell\Sync\Controller\Adminhtml\CustomsellBackend;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Catalog\Model\CategoryFactory;
//use \Customsell\Sync\Helper\Data;
use \Customsell\Sync\ApiInterface\CustomsellApi;
use \Magento\Store\Model\StoreManagerInterface;

class Categories extends \Magento\Backend\App\Action
{

    //protected $helper;
    protected $repository;
    protected $_storeManager;
    protected $_objectManager;
    protected $_storeCategories = [];
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $_categoryFactory;

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
        StoreManagerInterface $storeManager,
        CategoryFactory $categoryFactory      
    ) {
        
        $this->_storeManager = $storeManager;

        $this->_categoryFactory = $categoryFactory;
        
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

    public function updateCategories( $baseUrl, $parameters, $store_id ) {

        $apiKey = $this->apiKey($store_id);
        $url    = $baseUrl.'/api/category/';

        $customsellApi = new CustomsellApi;
        
        $result = $customsellApi->exec( $apiKey, $url, $parameters );

        return $result;
    }

    public function execute()
    {

        $store_ids = array();

        $_websites = $this->_storeManager->getWebsites();

        $categoryItems = array();

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

                        $category = $this->_categoryFactory->create();
                        $storeCategories = $category->getCategories($recursionLevel = 1, false, false, true);

                        $resultArray = [];
                        $storeresultArray = [];

                        foreach($storeCategories as $category) {

                                $resultArray[$category->getId()] = $category->getName();
                                $temp                = array();
                                $temp['category_id']          = $category->getId();
                                $temp['category_name']        = $category->getName();
                                $temp['category_description'] = $category->getDescription();
                                $temp['category_url']         = $category->getUrl($category->getId());
                                //$temp['category_parent_id']   = $category->getParentCategory();

                                $parent_category            = $category->getParentCategory();
                                
                                $temp['category_parent_id'] = $parent_category->getId();

                                $categoryItems[] = $temp;

                        }
                    }
                }
            }
        }

        file_put_contents(dirname(__FILE__).'/categorycount.log', print_r( $categoryItems, true));
                    
        $postData['categories'] = $categoryItems;
        $customsellRun  = $this->ping($store_id);
        // $result = $this->helper->updateCustomers($customsellRun,$postData);
        $result = $this->updateCategories($customsellRun,$postData,$store_id);

        file_put_contents(dirname(__FILE__).'/result.log', print_r( $result, true));

        $position = array();

            foreach ($result->messages as $value) {
                $pos = strpos($value, 'created');
                if ($pos) {   
                    $position[] = 1;
                }
            }

        $categoryItems_count = array();

        $category_count = $this->_categoryFactory->create();
        $storeCategories_count = $category_count->getCategories($recursionLevel = 1, false, false, true);

        $resultArray_count = [];
        $storeresultArray_count = [];

        foreach ($storeCategories_count as $categories_count){

            $categoryItems_count[] = $categories_count;

        }

        $category_item_count = count($categoryItems_count);

        $last_category_count = file_get_contents(dirname(__FILE__)."/logs/categorycount.log");

        $items_synced = explode("/", $last_category_count);

        //$total_item_synced = $category_item_count + $items_synced[0];

        //$category_item_count = $total_item_synced;

        $category_count_array = $category_item_count."/".count($categoryItems_count);

        file_put_contents(dirname(__FILE__).'/logs/categorycount.log', print_r( $category_count_array, true));

    }    
}
