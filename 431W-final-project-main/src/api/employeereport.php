<?php

require '../utils/database.php';

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    $id = $_GET["id"];
    
    $start_time = $_GET["start_time"];
    $end_time = $_GET["end_time"];
    $order_asc = $_GET["order_asc"] ?? "false";
    
    if (isset($start_time)) {
        $start_time = date("Y-m-d H:i:s", strtotime($start_time));
    } 
    if (isset($end_time) ){
        $end_time = date("Y-m-d H:i:s", strtotime($end_time));
    }
    
    $order_asc = $order_asc == "true" ? " ORDER BY shift.time_in ASC" : " ORDER BY shift.time_in DESC";
    
    try {
        $result = NULL;
        if (isset($start_time) && isset($end_time)) {
            $result = query("
                SELECT shift.time_in, shift.time_out, TIMESTAMPDIFF(MINUTE, shift.time_in, shift.time_out) / 60 AS hours
                FROM employeeshifttransactions shift, employees employee
                WHERE
                    shift.employee = employee.id AND
                    employee.id = ? AND
                    shift.time_in >= ? AND
                    COALESCE(shift.time_out, 0) <= ?
            " . $order_asc, "iss", $id, $start_time, $end_time);
        } else {
            $result = query("
            SELECT shift.time_in, shift.time_out, TIMESTAMPDIFF(MINUTE, shift.time_in, shift.time_out) / 60 AS hours
            FROM employeeshifttransactions shift, employees employee
            WHERE
                shift.employee = employee.id AND
                employee.id = ?
            " . $order_asc, "i", $id);
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
}

?>