<?php
namespace Customsell\Sync\Model\Config\Source;

use Magento\Customer\Model;

class Customers implements \Magento\Framework\Option\ArrayInterface
{

    protected $objectManager;

    public function __construct(
        /*\Magento\Customer\Model\Customer $customerFactory,*/
        \Magento\Framework\ObjectManagerInterface $interface
    ) {
       // $this->customerFactory = $customerFactory;
       $this->objectManager = $interface;
    }


    public function toOptionArray( $isMultiselect = false)
    {
        $customer_attributes = $this->objectManager->get('Magento\Customer\Model\Customer')->getAttributes();

        $attributesArrays = array();

           foreach($customer_attributes as $cal=>$val){
               $attributesArrays[] = array(
                   'label' => $cal,
                   'value' => $cal
               );
           }

        return $attributesArrays;
    }



}
