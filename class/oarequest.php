<?php

class oarequest
{
    public function request(
          $url_
        , $siteID
        , $date1
        , $date2
        , $token
        , $mode = 'plain'
        , $goalID = ''
        , $perPage = 20
        , $group = 'month'
    )
    {

        $url = "{$url_}?id={$siteID}&goal_id={$goalID}&per_page={$perPage}&date1={$date1}&date2={$date2}&group={$group}&table_mode={$mode}";
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
