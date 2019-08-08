<?php

class Centurylink_cache_api {

    private $keyId;
    private $keySecret;

    public function __construct() {
        $this->keyId = '293711785';
        $this->keySecret = 'JSQgyf2cEcrFRa9JqwkY';
    }

    public function getAPISignature($dateStr, $uri, $contentType, $method, $md5, $keySecret) {
        $data = $dateStr . "\n" . $uri . "\n" . $contentType . "\n" . $method . "\n" . $md5;

        //Compute the hash via the hash_mac function (requires PHP 5 >= 5.1.2, PECL hash >= 1.1)
        $hmac = hash_hmac("sha1", $data, $keySecret, true);

        //The hash to be sent in the request must be encoded in base64
        return base64_encode($hmac);
    }

    public function sendAPICallPOST($accept, $contentType, $dateStr, $postBody, $url) {

        //First, if you have a post body you will be sending with the request, create an MD5 hash of this post body.
        $md5 = "";
        if ($postBody != null)
            $md5 = md5($postBody);

        //Create the api signature
        $signature = $this->getAPISignature($dateStr, parse_url($url, PHP_URL_PATH), $contentType, "POST", $md5, $this->keySecret);

        //Create the string to be set as the authorization property in the request header.
        //Format: MPA {key_id}:{signature}
        $authString = "MPA " . $this->keyId . ":" . $signature;

        //Create Header Properties Array.
        //Because this is a post, content-type is set to reflect format of sent parameters (application/json, application/xml, etc.)
        $headerProperties = array(
            'Accept: ' . $accept,
            'Authorization: ' . $authString,
            'Content-Type: ' . $contentType,
            'Date: ' . $dateStr
        );

        //Initialize curl request, set appropriate properties, and execute the request.
        $ch = curl_init();
        if ($postBody != null) {
            //If you have a post body to send with the request...
            //Set Content-Length header property to the length of your post body
            array_push($headerProperties, 'Content-Length: ' . strlen($postBody));

            //Set Content-MD5 header property to the MD5 Hash of your post body
            array_push($headerProperties, 'Content-MD5: ' . $md5);

            //Set curl request property CURLOPT_POSTFIELDS to equal your post body
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
        } else {
            //Otherwise, set Content-MD5 header property to blank as you have no post body
            array_push($headerProperties, 'Content-MD5: ');
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerProperties);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

}
