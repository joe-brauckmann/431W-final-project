<?php

require '../utils/api.php';

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    echo json_encode(api_results(
        "employeeshifts",
        array(
            "start_time" => "s",
            "length_minutes" => "i"
        ), // orderable fields
        array(), // like fields
        array(), // other fields
        array(), // search fields
        "", "", "", 10000
    ));
} else if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $start_time = $_POST['start_time'];
    $length_minutes = $_POST['length_minutes'];

    if (!isset($start_time) || !isset($length_minutes)) {
        header('HTTP/1.0 400 Bad Request');
        die("Missing Parameters");
    }

    try {
        $start_time = date("Y-m-d H:i:s", strtotime($start_time));
    
        $query = "INSERT INTO inventorymgmt.employeeshifts (start_time, length_minutes) VALUES (?,?);";
    
        $newId = insert($query, "si", $start_time, $length_minutes);
    
        if ($newId > 0) {
            echo "Success";
        } else {
            header('HTTP/1.0 500 Server Error');
            echo "Could not create employee";
        }
    } catch (Exception $exception) {
        header('HTTP/1.0 500 Server Error');
        echo $exception;
    }
}
?>