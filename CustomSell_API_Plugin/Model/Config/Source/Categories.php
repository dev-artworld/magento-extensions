<?php 

namespace Customsell\Sync\Model\Config\Source;

use \Customsell\Sync\Helper\Data;

class Categories implements \Magento\Framework\Option\ArrayInterface {

    protected $helper;
    /**
     * Store categories cache
     *
     * @var array
     */
    protected $_storeCategories = [];
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $_categoryFactory;
    /**
     * Categories constructor.
     *
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    public function __construct(
            \Magento\Catalog\Model\CategoryFactory $categoryFactory,
            \Customsell\Sync\Helper\Data $helper
        )
    {
        $this->helper = $helper;            
        $this->_categoryFactory = $categoryFactory;
    }
    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $cacheKey = sprintf('%d-%d-%d-%d', 1, false, false, true);
        if (isset($this->_storeCategories[$cacheKey])) {
            return $this->_storeCategories[$cacheKey];
        }
        /**
         * Check if parent node of the store still exists
         */
        $category = $this->_categoryFactory->create();
        $storeCategories = $category->getCategories($recursionLevel = 1, false, false, true);
        $this->_storeCategories[$cacheKey] = $storeCategories;
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
            
            $temp['category_parent_id']      = $parent_category->getId();

            $categoryItems[] = $temp;

        }
/*
        echo "<pre>";
        print_r($storeCategories->getData('description'));

        return;*/
        
        return $categoryItems;
    }

    // function runCron(){

    // }

    function sayHello(){
        

        $logdata                = "Executed at: ".date('d-m-Y H:i:s');
        $postData['categories'] = $this->toOptionArray();

        
        $customsellRun  = $this->helper->ping();
        $result         = $this->helper->updateCategories($customsellRun,$postData);

        file_put_contents(dirname(__FILE__)."/ModelCategory3.log", print_r($result, true) );
        /*file_put_contents(dirname(__FILE__)."/ModelCategory4.log", print_r($customsellRun, true),FILE_APPEND );*/
        /*file_put_contents(dirname(__FILE__)."/ModelCategory5.log", print_r($postData, true),FILE_APPEND );
        
        file_put_contents(dirname(__FILE__)."/ModelCategory2.log", print_r("\n", true),FILE_APPEND );
*/
        //return "Hello World!!!!";
    }

    
}
?>