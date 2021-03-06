<?php


namespace Customsell\Sync\ApiInterface;

class CustomsellApi
{
        public function saveProduct($productData)
        {
                file_put_contents(dirname(__FILE__)."/productData.log", print_r($productData, true));
        }

        
        public function exec($apiKey, $url = '', $parameters = array(), $method = 'post' ){
                
                //$apiKey     = 'SW6O7BAZJZI87Y3LGPGQ9IQ3ECEQ22AV';
                // Sign the request
                $request_time   = time(); // Used to sign request
                $api_signature  = md5($apiKey . $request_time);


                // echo "<pre>";
                
                // echo("signature<br>");
                // print_r($request_time);
                // echo("<br>");

                // echo("signature<br>");
                // print_r($api_signature);
                // echo "</pre>";

                // echo "<pre>";
                // print_r(json_encode($parameters) );
                // echo "</pre>";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);            
                if( $method == 'post') {
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));            
                }        
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('API_SIGNATURE: ' . $api_signature, 'API_REQUEST_TIME: ' . $request_time));
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                
                $response       = curl_exec($ch);
                $httpCode       = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                curl_close($ch);                
                return json_decode($response);
        }
}
