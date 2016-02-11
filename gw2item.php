<?php

if (array_key_exists('id', $_GET)) {
    $id = $_GET['id'];
    if (! is_numeric($id)) {
        print "Item <b>$id</b> invalid. Use a number.\n";
        exit();
    }
} else {
    print "Item requires an id. Use a number.\n";
    exit();
}

require 'Config.php';
require 'GW2DB.php';

$db = new GW2DB;
$conn = $db->connect();

$stmt = $conn->prepare("SELECT * FROM items WHERE item_id=?");
$stmt->bindValue(1, $id);
$stmt->execute();

if ($stmt->rowCount() == 1) {
    $item = $stmt->fetch();

    print "<img src='" . $item['icon'] . "'><br>\n";
    print "Item: " . $item['item_id'] . "<br>\n";
    print "Name: " . $item['name'] . "<br>\n";

    $json = json_decode($item['json']);

    print "<pre>";
    print_r($json);
    print "</pre>";
} else {
    print "Item id $id not found\n";
}

