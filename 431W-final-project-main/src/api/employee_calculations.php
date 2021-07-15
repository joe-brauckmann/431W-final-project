<?php

require '../utils/api.php';

if ($_SERVER['REQUEST_METHOD'] == "GET") {
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
        ) // search fields
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