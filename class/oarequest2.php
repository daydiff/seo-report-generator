<?php

class oarequest2
{
    public function request(
          $url
        , $token
    )
    {

        $header = "Authorization: OAuth {$token}";

        // initialising CURL
        $ch = curl_init();

        // Setting the URL and all required values
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));

        // Execute query and get result

        $output = curl_exec($ch);

        if ($output !== false) {
            return $output;
        } else {
            return "Connection Error: " . curl_error($ch);
        }
    }
}
