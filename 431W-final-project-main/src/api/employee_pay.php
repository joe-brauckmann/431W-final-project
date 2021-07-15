<?php

require '../utils/api.php';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $dollars_per_hour = $_POST['dollars_per_hour'];
    $overtime_per_hour = $_POST['overtime_per_hour'];

    $query = "INSERT INTO inventorymgmt.employeepay (dollars_per_hour, overtime_per_hour) VALUES(?,?);";

    insert($query, "ii", $dollars_per_hour, $overtime_per_hour);
}else{
    echo "REQUESTS AVAILABLE: POST";
}
?>