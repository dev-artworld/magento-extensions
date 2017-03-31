<?php
namespace Technologymindz\ProductFilter\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $storeManager;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context, 
        \Magento\Store\Model\StoreManagerInterface $storeManager 
    )
    {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function getConfigValue($value = '')
    {
        return $this->scopeConfig
                ->getValue(
                        $value,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        );            
    }

    public function getStoreLocation()
    {
        $store_lat = $this->scopeConfig->getValue( 'TechnologymindzProductFilter/store_lat_grp/store_lat',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $store_lon = $this->scopeConfig->getValue( 'TechnologymindzProductFilter/store_lon_grp/store_lon',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $data[] = $store_lat;
        $data[] = $store_lon;

        return $data;
    }

    public function getStoreRadius()
    {
        $radius    = $this->scopeConfig->getValue('TechnologymindzProductFilter/store_radius_grp/store_radius',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $radius;
    }

    public function getUserLocation()
    {   
        $ip = $_SERVER['REMOTE_ADDR'];

        $data = $this->url_get_contents("http://ipinfo.io/{$ip}/json");

        if ($data != false)
        {
            $detail_client = json_decode($data);
 
            return $pieces = explode(",", $detail_client->loc);
        }
    }

    public function url_get_contents($Url)
    {        
        if (!function_exists('curl_init')){ 
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $Url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function calculateDistance($store_lat, $store_lon, $user_lat, $user_lon)
    {
        $store_lat = deg2rad( $store_lat);
        $store_lon = deg2rad( $store_lon);

        $user_lat = deg2rad( $user_lat);
        $user_lon = deg2rad( $user_lon);

        $radius_of_earth = 6371;

        $dist = (acos(sin($store_lat) * sin($user_lat) + cos($store_lat) * cos($user_lat) * cos($store_lon - $user_lon)) * $radius_of_earth);
        $distance = (int($dist) * 1000);

        return $distance;
    }

    public function isAvailable()
    {
        $storeRadius = $this->getStoreRadius();
        $storeData = $this->getStoreLocation();
        $store_lat = $storeData[0];
        $store_lon = $storeData[1];

        $userData  = $this->getUserLocation();
        $user_lat  = $userData[0];
        $user_lon  = $userData[1];

        $distance = $this->calculateDistance($store_lat, $store_lon, $user_lat, $user_lon );

        if ($distance <= $storeRadius) {
            return true;
        } else {
            return false;    
        }
    }
    public function getErrorMesasge()
    {
        $errorMesasge    = $this->scopeConfig->getValue('TechnologymindzProductFilter/store_err_grp/store_err', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($errorMesasge == '') {
            return "Not Available";    
        } else {
            return $errorMesasge;    
        }
    }
}
