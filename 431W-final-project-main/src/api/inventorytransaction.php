<?php

require '../utils/database.php';

$warehouse = $_GET["warehouse"];

try {
    $result = query("
        SELECT employee.name, product.name, inv.quantity
        FROM inventorytransactions inv, employees employee, products product,
            sectors sector, warehouses warehouse
        WHERE
            inv.sector = sector.id AND
            sector.warehouse = warehouse.id AND
            inv.employee = employee.id AND
            warehouse.id = ?
    ", "i", $warehouse);
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