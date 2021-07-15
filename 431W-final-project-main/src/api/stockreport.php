<?php

require '../utils/database.php';

$pid = $_GET["pid"];

$start_time = $_GET["start_time"];
$end_time = $_GET["end_time"];
$order_asc = $_GET["order_asc"] ?? "false";

if (isset($start_time)) {
    $start_time = date("Y-m-d H:i:s", strtotime($start_time));
} 
if (isset($end_time) ){
    $end_time = date("Y-m-d H:i:s", strtotime($end_time));
}

$order_asc = $order_asc == "true" ? " ORDER BY inv.timestamp ASC" : " ORDER BY inv.timestamp DESC";

try {
    $result = NULL;
    if (isset($start_time) && isset($end_time)) {
        $result = query("
            SELECT employee.name AS empname, product.name, inv.quantity_added, inv.id, inv.timestamp
            FROM inventorytransactions inv, employees employee, products product
            WHERE
                inv.employee = employee.id AND
                inv.product = product.id AND
                product.id = ? AND
                inv.timestamp >= ? AND
                inv.timestamp <= ?
        " . $order_asc, "iss", $pid, $start_time, $end_time);
    } else {
        $result = query("
            SELECT employee.name AS empname, product.name, inv.quantity_added, inv.id, inv.timestamp
            FROM inventorytransactions inv, employees employee, products product
            WHERE
                inv.employee = employee.id AND
                inv.product = product.id AND
                product.id = ?
        " . $order_asc, "i", $pid);
    }
    $data = array();
    while($row = $result->fetch_assoc()) {
        array_push($data, $row);
    }
    echo json_encode($data);
} catch (Exception $exception) {
    header('HTTP/1.0 500 Server Error');
    echo "Fatal error: " . $exception->getMessage();
}

?>