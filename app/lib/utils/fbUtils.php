<?php

    namespace MuPHP\Utils;

    class FBUtils
    {
        public static function getSignedRequest()
        {
            $signedRequest = null;
            $requestData   = null;
            if ($_REQUEST)
            {
                $signedRequest = $_REQUEST['signed_request'];
                $requestData   = FBUtils::parseSignedRequest($signedRequest, FB_APP_SECRET);
            }

            return $requestData;
        }

        public static function parseSignedRequest($signedRequest, $appSecretKey)
        {
            list($encoded_sig, $payload) = explode('.', $signedRequest, 2);
            $sig  = Utils::base64UrlDecode($encoded_sig);
            $data = json_decode(Utils::base64UrlDecode($payload), true);

            if (strtoupper($data['algorithm']) !== 'HMAC-SHA256')
            {
                error_log('Unknown algorithm. Expected HMAC-SHA256');

                return null;
            }

            $expected_sig = hash_hmac('sha256', $payload, $appSecretKey, $raw = true);
            if ($sig !== $expected_sig)
            {
                error_log('Bad Signed JSON signature!');

                return null;
            }

            return $data;
        }
    }
