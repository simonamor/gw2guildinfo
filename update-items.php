<?php

// Update the locally stored cache of items with the
// item data from the API.

// If there is a new build available, this wipes out the whole item
// cache and rebuilds it with the latest information.

if (array_key_exists('HTTP_HOST', $_SERVER)) {
    die("<pre>Not a CGI</pre>");
}

require 'Config.php';
require 'GW2API.php';
require 'GW2DB.php';

// GW2API object from gw2api.php
$api = new GW2API;

$db = new GW2DB;
$conn = $db->connect();

// Should check v2/build to see if there is a new build, if so,
// delete everything in the items table
// If there isn't a new build since last time, simply check if we
// already have the item id in the table and skip it if we have.

$stmt_build = $conn->query("SELECT opt_val FROM options WHERE opt_key='build'");
if ($stmt_build->rowCount() == 0) {
    $conn->query("INSERT INTO options SET opt_key='build',opt_val='0'");
    $build_ver = 0;
} else {
    $stmt_build_ver = $stmt_build->fetch();
    $build_ver = $stmt_build_ver[0];
}
$stmt_build->closeCursor();

$api_build_ver_result = $api->api_call('v2/build');
$api_build_ver = $api_build_ver_result->id;

$overwrite = 0;

if ($api_build_ver != $build_ver) {
    print "Build version changed. Purging item data as we go\n";
    $overwrite = 1;
    $conn->query("UPDATE options SET opt_val='$api_build_ver' WHERE opt_key='build'");
} else {
    print "Build version the same as previous run. $build_ver / $api_build_ver\n";
}

// Used to check if an item exists in the db already
$stmt_exists = $conn->prepare("SELECT * FROM items WHERE item_id=:item_id");
$stmt_exists->bindParam('item_id', $ex_item_id);

// Store the entire response for the item in the json field since we don't
// need to query by type etc just yet. This may change later.
$stmt = $conn->prepare("INSERT INTO items SET item_id=:item_id,name=:name,icon=:icon,json=:json ON DUPLICATE KEY UPDATE name=:name,icon=:icon,json=:json");
$stmt->bindParam('item_id', $add_item_id);
$stmt->bindParam('name', $add_name);
$stmt->bindParam('icon', $add_icon);
$stmt->bindParam('json', $add_json);

// *** Update items cache ***

// Fetch the full list of ids
$items = $api->api_call('v2/items');

// and work out which ones need updating
while (! empty($items)) {

    $get_items = array();
    // Process the items 200 at a time. The API is limited to 200 items
    // per request.
    while ((count($items) > 0) && (count($get_items) < 200)) {
        $ex_item_id = array_shift($items);
        // If we're rebuilding everything, add the id to the list to query
        if ($overwrite == 1) {
            array_push($get_items, $ex_item_id);
        } else {
            $stmt_exists->execute();
            // otherwise, only add the id to the list if we don't already
            // have it in the database.
            if ($stmt_exists->rowCount() == 0) {
                array_push($get_items, $ex_item_id);
            }
        }
    }

    // $get_items might be empty so best to check before using it
    if (count($get_items) > 0) {

        $item_ids = implode(",", $get_items);
        $item_results = $api->api_call("v2/items?ids=".$item_ids);

        foreach ($item_results as $item_result) {
            $add_item_id = $item_result->id;
            $add_name = $item_result->name;
            $add_icon = $item_result->icon;
            $add_json = json_encode($item_result);
            $stmt->execute();
        }
        print "Stored $item_ids\n";
        sleep(1);
    }
}

