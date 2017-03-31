<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Customsell\Sync\Controller\Index;

use \Customsell\Sync\Helper\Data;

/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory  */
    protected $resultPageFactory;
    protected $helper;
    protected $customSellSyncApi;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Data $helper            
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->helper            = $helper;

        parent::__construct($context);

    }
    /**
     * Load the page defined in view/frontend/layout/samplenewpage_index_index.xml
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // return $this->resultPageFactory->create();      


        $baseUrl = $this->helper->ping();
        echo $baseUrl."def";
        echo "<hr>";

        //echo $this->helper->updateCategories($baseUrl);

        echo "<br>doneabc";
    }
}
