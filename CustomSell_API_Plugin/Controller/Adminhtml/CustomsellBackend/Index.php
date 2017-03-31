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
use \Magento\Framework\Registry;
//use \Customsell\Sync\Helper\Data;

class Index extends \Magento\Backend\App\Action
{

    //protected $helper;
    protected $repository;
    protected $_coreRegistry;
    protected $_storeManager;
    protected $_storeCategories = [];
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $_categoryFactory;
	/**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        CategoryFactory $categoryFactory
        //\Customsell\Sync\Helper\Data $helper
    ) {

        $this->_categoryFactory = $categoryFactory;
        $this->_coreRegistry = $coreRegistry;
        //$this->helper = $helper;

        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
  
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
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */

        if(!file_exists(dirname(__FILE__)."/logs/productcount.log")){

            $last_product_count = '0';
            
        }else{
            $last_product_count = file_get_contents(dirname(__FILE__)."/logs/productcount.log");
        }

        if(!file_exists(dirname(__FILE__)."/logs/categorycount.log")){

            $last_category_count = '0';
            
        }else{
            $last_category_count = file_get_contents(dirname(__FILE__)."/logs/categorycount.log");
        }

        if(!file_exists(dirname(__FILE__)."/logs/customercount.log")){
            
            $last_customer_count = '0';
            
        }else{
            $last_customer_count = file_get_contents(dirname(__FILE__)."/logs/customercount.log");
        }

        if(!file_exists(dirname(__FILE__)."/logs/ordercount.log")){
            
            $last_order_count = '0';
            
        }else{
            $last_order_count = file_get_contents(dirname(__FILE__)."/logs/ordercount.log");
        }

        if(!file_exists(dirname(__FILE__)."/logs/couponcount.log")){

            $last_promo_count = '0';
            
        }else{
            $last_promo_count = file_get_contents(dirname(__FILE__)."/logs/couponcount.log");
        }

        if(!file_exists(dirname(__FILE__)."/logs/newslettercount.log")){
            
            $last_newsletter_count = '0';
            
        }else{
            $last_newsletter_count = file_get_contents(dirname(__FILE__)."/logs/newslettercount.log");
        }

        $sync_count = "<p>Product - ".$last_product_count."</p>";
        $sync_count .= "<p>Category - ".$last_category_count."</p>";
        $sync_count .= "<p>Customer - ".$last_customer_count."</p>";
        $sync_count .= "<p>Order - ".$last_order_count."</p>";
        $sync_count .= "<p>Discount Coupon - ".$last_promo_count."</p>";
        $sync_count .= "<p>Newsletter Subscriber  - ".$last_newsletter_count."</p>";

        $this->_coreRegistry->register('sync_count', $sync_count);

        $resultPage = $this->resultPageFactory->create();

        $resultPage->getConfig()->getTitle()->prepend(__('Customsell Sync'));
        return $resultPage;

    }

    
    
}
