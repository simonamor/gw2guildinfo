<?php

if (array_key_exists('HTTP_HOST', $_SERVER)) {
    die("<pre>Not a CGI</pre>");
}

require 'Config.php';
require 'GW2API.php';
require 'GW2DB.php';

$api = new GW2API;

$db = new GW2DB;
$conn = $db->connect();

$upgrades = $api->api_call('v2/guild/upgrades');

$all_upgrades = array();

while (! empty($upgrades)) {
    $get_upgrades = array();
    while ((count($upgrades) > 0) && (count($get_upgrades) < 190)) {
        $ex_upgrade_id = array_shift($upgrades);
        array_push($get_upgrades, $ex_upgrade_id);
    }
    $get_upgrades_result = fetch_upgrades($get_upgrades);
    $all_upgrades = array_merge($all_upgrades, $get_upgrades_result);
}

print "Found " . count($all_upgrades) . " upgrades\n";

$conn->query("DELETE FROM guildhall_upgrades");
$conn->query("DELETE FROM guildhall_upgrade_cost");
$conn->query("DELETE FROM guildhall_upgrade_prereq");

$stmt_gu = $conn->prepare("INSERT INTO guildhall_upgrades SET upgrade_id=:gu_upgrade_id,name=:gu_name,type=:gu_type,icon=:gu_icon,required_level=:gu_req_level,experience=:gu_xp");
$stmt_gu->bindParam('gu_upgrade_id', $gu_upgrade_id);
$stmt_gu->bindParam('gu_name', $gu_name);
$stmt_gu->bindParam('gu_type', $gu_type);
$stmt_gu->bindParam('gu_icon', $gu_icon);
$stmt_gu->bindParam('gu_req_level', $gu_req_level);
$stmt_gu->bindParam('gu_xp', $gu_xp);

// cost (items needed) for upgrade
$stmt_guc = $conn->prepare("INSERT INTO guildhall_upgrade_cost SET upgrade_id=:guc_upgrade_id,type=:guc_type,name=:guc_name,qty=:guc_qty,item_id=:guc_item_id");
$stmt_guc->bindParam('guc_upgrade_id', $guc_upgrade_id);
$stmt_guc->bindParam('guc_type', $guc_type);
$stmt_guc->bindParam('guc_name', $guc_name);
$stmt_guc->bindParam('guc_qty', $guc_qty);
$stmt_guc->bindParam('guc_item_id', $guc_item_id);

// prerequisites for upgrade
$stmt_gup = $conn->prepare("INSERT INTO guildhall_upgrade_prereq SET upgrade_id=:gup_upgrade_id,prereq_id=:gup_prereq_id");
$stmt_gup->bindParam('gup_upgrade_id', $gup_upgrade_id);
$stmt_gup->bindParam('gup_prereq_id', $gup_prereq_id);


foreach ($all_upgrades as $upgrade) {
    printf("Id %d: %s\n", $upgrade->id, $upgrade->name);

    $gu_upgrade_id = $upgrade->id;
    $gup_upgrade_id = $gu_upgrade_id;
    $guc_upgrade_id = $gu_upgrade_id;

    $gu_name = $upgrade->name;
    $gu_type = $upgrade->type;
    $gu_icon = $upgrade->icon;
    $gu_req_level = $upgrade->required_level;
    $gu_xp = $upgrade->experience;
    $stmt_gu->execute();

    // add costs
    foreach ($upgrade->costs as $guc) {
        $guc_type = $guc->type;
        $guc_name = $guc->name;
        $guc_qty = $guc->count;
        if ($guc->type == 'Item') {
            $guc_item_id = $guc->item_id;
        } else {
            $guc_item_id = NULL;
        }
        $stmt_guc->execute();
    }

    // prerequisites
    foreach ($upgrade->prerequisites as $gup_prereq_id) {
        $stmt_gup->execute();
    }

}


exit();

function fetch_upgrades($upgrade_array) {
    global $api; // Use the global api object

    // Got nothing to fetch
    if (count($upgrade_array) == 0) {
        return;
    }

    $upgrade_ids = implode(",", $upgrade_array);
    $upgrade_results = $api->api_call("v2/guild/upgrades?ids=".$upgrade_ids);
    return $upgrade_results;
}

