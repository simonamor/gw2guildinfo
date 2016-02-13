<?php

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

# Guild related stuff
$api->set_api_key(API_KEY);

// *** Update Treasury stuff ***

$treasury = $api->api_call('v2/guild/' . GUILD_ID . '/treasury', array());

$stmt = $conn->prepare("SELECT * FROM guildhall_treasury WHERE item_id=:item_id");
$stmt->bindParam('item_id', $find_item_id);

$stmt_add_t = $conn->prepare("INSERT INTO guildhall_treasury SET item_id=:item_id,qty=:qty");
$stmt_add_t->bindParam('item_id', $add_item_id);
$stmt_add_t->bindParam('qty', $add_qty);

$stmt_upd_t = $conn->prepare("UPDATE guildhall_treasury SET qty=:qty WHERE item_id=:item_id");
$stmt_upd_t->bindParam('item_id', $upd_item_id);
$stmt_upd_t->bindParam('qty', $upd_qty);

$stmt_add_tr = $conn->prepare("INSERT INTO guildhall_treasury_req SET item_id=:item_id,upgrade_id=:upgrade_id,qty=:qty");
$stmt_add_tr->bindParam('item_id', $add_item_id);
$stmt_add_tr->bindParam('upgrade_id', $add_upgrade_id);
$stmt_add_tr->bindParam('qty', $add_qty);

$stmt_del_tr = $conn->prepare("DELETE FROM guildhall_treasury_req WHERE item_id=:item_id");
$stmt_del_tr->bindParam('item_id', $del_item_id);

foreach ($treasury as $item) {
    $find_item_id = $item->item_id;
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        $add_item_id = $item->item_id;
        $add_qty = $item->count;
        $stmt_add_t->execute();
        print "Insert new guildhall_treasury record\n";
    } else {
        $upd_item_id = $item->item_id;
        $upd_qty = $item->count;
        $stmt_upd_t->execute();
        print "Update count/qty in treasury to " . $item->count . "\n";
    }

    $del_item_id = $item->item_id;
    $stmt_del_tr->execute();
    print "Delete existing guildhall_treasury_req records for id $del_item_id\n";

    foreach ($item->needed_by as $upg) {
        $add_item_id = $item->item_id;
        $add_upgrade_id = $upg->upgrade_id;
        $add_qty = $upg->count;
        $stmt_add_tr->execute();
        print "Insert new guildhall_treasury_req record\n";
    }

    print $item->item_id . ' with ' . $item->count . " in treasury\n";
    print_r($item->needed_by);
}

?>
