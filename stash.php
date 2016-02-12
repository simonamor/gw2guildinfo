<!DOCTYPE html>
<html lang="en">
<head>
<style type="text/css">
.item {
    width: 64px;
    height: 64px;
}
table {
    cell-padding: 12px;
}
table td {
    border: 1px dashed grey;
    padding: 4px;
    min-width: 64px;
    min-height: 64px;
}
body {
    background: #1a1c21;
    color: #cecaba;
}
h1 {
    font-size: 22px;
}
</style>
</head>
<body>
<h1>Guild Bank</h1>
<?php

require 'Config.php';
require 'GW2API.php';
require 'GW2DB.php';

$api = new GW2API;

$db = new GW2DB;
$conn = $db->connect();

$stmt = $conn->prepare("SELECT * FROM guildhall_upgrades WHERE upgrade_id=:upgrade_id");
$stmt->bindParam('upgrade_id', $upgrade_id);

$stmt_item = $conn->prepare("SELECT * FROM items WHERE item_id=:item_id");
$stmt_item->bindParam('item_id', $item_id);

$api->set_api_key(API_KEY);

$stash = $api->api_call("/v2/guild/" . GUILD_ID . "/stash");

foreach ($stash as $section) {
    print "<table>\n";

    $upgrade_id = $section->upgrade_id;
    $stmt->execute();
    $upgrade = $stmt->fetch();

    print "<tr><th colspan=\"10\">";
    print $upgrade['name'] . "<br>";
    print $section->note . "<br>";
    print "Coins: " . coins_to_gold($section->coins) . "</th></tr>";

    print "<tr>\n";
    $count = 0;
    foreach ($section->inventory as $item) {
        print "<td style=\"text-align:center\">";

        if ($item) {
            $item_id = $item->id;
            $stmt_item->execute();
            $item_info = $stmt_item->fetch();

            print "<img title=\"" . $item_info['name'] . "\" src=\"" . $item_info['icon'] . "\" class=\"item\"><br>" . $item->count;
        }
        print "</td>\n";
        if (++$count % 10 == 0) {
            print "</tr>\n";
            if ($count < $section->size) {
                print "<tr>";
            }
        }
    }
    

    print "</table>";
}


function coins_to_gold($coins = 0) {
    $gold = intval($coins / 10000);
    $silver = intval($coins / 100) % 100;
    $copper = $coins % 100;

    return sprintf("%dg %ds %dc", $gold, $silver, $copper);
}

?>
</body>
</html>
