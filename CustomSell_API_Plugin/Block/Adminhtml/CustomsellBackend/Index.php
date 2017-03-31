<?php
/**
 * Copyright © 2015 Customsell . All rights reserved.
 */
namespace Customsell\Sync\Block\Adminhtml\CustomsellBackend;

use Magento\Framework\View\Element\Template;

class Index extends \Magento\Backend\Block\Template
{	
	protected $_coreRegistry;

	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Framework\Registry $coreRegistry,
        array $data = []
		)	
	{
		$this->_coreRegistry = $coreRegistry;
		parent::__construct($context);
	}

	public function sayHello()
	{
		return $this->_coreRegistry->registry('sync_count');
		//$last_product_count = file_get_contents(dirname(__FILE__)."/productcount.log");
		//return __('Hello World');
	}

	
}
