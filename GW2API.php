<?php

class GW2API {
    var $api_key;

    function set_api_key($key) {
        $this->api_key = $key;
    }

    function api_call($type, $params = array()) {
        $headers = array();

        if (array_key_exists('api_key', $params)) {
            $this->set_api_key($params['api_key']);
            unset($params['api_key']);
        }
        if ($this->api_key) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }
        $url = 'https://api.guildwars2.com/' . $type;

        if (! empty($params)) {
            $url_args = http_build_query( $params );
            $url = $url . '?' . $url_args;
        }

        $header_str = array();
        $header_keys = array_keys($headers);
        foreach ($header_keys as $header) {
            $header_str[] = "$header: " . $headers[$header];
        }

        $new_curl = curl_init();
        curl_setopt($new_curl, CURLOPT_URL, $url);
        curl_setopt($new_curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($new_curl, CURLOPT_HTTPHEADER, $header_str);

        $curl_response = curl_exec($new_curl);
        curl_close($new_curl);

        $response = json_decode($curl_response);
        return $response;
    }

    // Get details of the account that the API key is for
    // Must call ->set_api_key() before using this
    function account() {
        // Check for API key
        // if (! $this->has_api_key()) {

        return $this->api_call(
            'v2/account'
        );
    }

    function guild_members($guild_id = '') {
        // Check for API key
        // if (! $this->has_api_key()) {

        // Check for guild id present
        // if (empty($guild_id)) {

        return $this->api_call(
            "v2/guild/$guild_id/members"
        );
    }

    function guild_details($guild_id = '') {
        return $this->api_call(
            'v1/guild_details', array( 'guild_id' => $guild_id )
        );
    }

    function guild_stash($guild_id = '') {
        // Check for API key
        // if (! $this->has_api_key()) {

        // Check for guild id present
        // if (empty($guild_id)) {

        return $this->api_call(
            "v2/guild/$guild_id/stash"
        );
    }

}
