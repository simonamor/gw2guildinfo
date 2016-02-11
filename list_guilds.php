<?php

if (array_key_exists('HTTP_HOST', $_SERVER)) {
    die("<pre>Not a CGI</pre>");
}

require 'Config.php';
require 'GW2API.php';

$api = new GW2API;

$api->set_api_key(API_KEY);

$account = $api->api_call("/v2/account");
foreach ($account->guilds as $guild) {
    $guild_info = $api->api_call("/v1/guild_details.json?guild_id=" . $guild);
    print "Name: " . $guild_info->guild_name . "\n";
    print "Id: " . $guild . "\n\n";
}

