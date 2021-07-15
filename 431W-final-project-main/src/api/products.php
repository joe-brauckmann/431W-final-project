<?php

require '../utils/api.php';

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    $extra_selects = "
        (SELECT COALESCE(SUM(quantity_added), 0) AS sum
        FROM `inventorymgmt`.`inventorytransactions` t
        WHERE t.product = products.id) AS quantity,
        (SELECT sector.name FROM sectors sector WHERE sector.id = products.sector) AS sector_name,
        (SELECT warehouse.name FROM sectors sector, warehouses warehouse 
            WHERE sector.id = products.sector AND warehouse.id = sector.warehouse
        ) AS warehouse_name
    ";
    echo json_encode(api_results(
        "products",
        array(
            "sector" => "i"
        ), // orderable fields
        array(
            "name" => "s"
        ), // like fields
        array(), // other fields
        array(
            "name" => "s"
        ), // search fields
        $extra_selects, "", "", 1000
    ));
} else if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $name = $_POST['name'];
    $sector = $_POST['sector'];
    
    echo $name;
    echo $sector;

    $query = "INSERT INTO inventorymgmt.products (name, sector) VALUES(?,?);";

    insert($query, "si", $name, $sector);
} else if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
    $id = $_GET['id'];

    $query = "DELETE FROM inventorymgmt.products WHERE id = ?;";

    execute($query, "i", $id);
}
?>