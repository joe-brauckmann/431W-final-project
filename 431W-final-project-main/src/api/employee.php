<?php

require '../utils/api.php';

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    // Add pay and hour calculation
    $extra_select = "
        (SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, shift.time_in, shift.time_out) / 60), 0)
        FROM employeeshifttransactions shift WHERE shift.employee = employees.id)
            as hours_including_ot,
    (SELECT 
        COALESCE((SUM(TIMESTAMPDIFF(MINUTE, shift.time_in, shift.time_out) - 
            LEAST(TIMESTAMPDIFF(MINUTE, shift.time_in, shift.time_out),
                COALESCE(trueshift.length_minutes, 9999999999999999)))) / 60, 0
            )
        FROM employeeshifttransactions shift
        LEFT JOIN employeeshifts trueshift ON shift.shift = trueshift.id
        WHERE shift.employee = employees.id) AS overtime,
    (SELECT 
        COALESCE(SUM(LEAST(TIMESTAMPDIFF(MINUTE, shift.time_in, shift.time_out),
                COALESCE(trueshift.length_minutes, 9999999999999999)) / 60), 0)
        FROM employeeshifttransactions shift
        LEFT JOIN employeeshifts trueshift ON shift.shift = trueshift.id
        WHERE shift.employee = employees.id) AS hours_excluding_ot,
    (SELECT 
        COALESCE((SUM(TIMESTAMPDIFF(MINUTE, shift.time_in, shift.time_out) - 
            LEAST(TIMESTAMPDIFF(MINUTE, shift.time_in, shift.time_out),
                COALESCE(trueshift.length_minutes, 9999999999999999)))) / 60, 0
            ) * pay.overtime_per_hour
        FROM employeeshifttransactions shift
        LEFT JOIN employeeshifts trueshift ON shift.shift = trueshift.id
        JOIN employeepay pay
        WHERE shift.employee = employees.id AND pay.id = employees.pay) AS overtime_pay,
    COALESCE((SELECT 
        SUM(LEAST(TIMESTAMPDIFF(MINUTE, shift.time_in, shift.time_out),
        COALESCE(trueshift.length_minutes, 9999999999999999)) / 60)
        * pay.dollars_per_hour
        FROM employeeshifttransactions shift
        LEFT JOIN employeeshifts trueshift ON shift.shift = trueshift.id
        JOIN employeepay pay
        WHERE shift.employee = employees.id AND pay.id = employees.pay), 0) AS pay_excluding_ot,
    COALESCE((SELECT 
        COALESCE((SUM(TIMESTAMPDIFF(MINUTE, shift.time_in, shift.time_out) - 
            LEAST(TIMESTAMPDIFF(MINUTE, shift.time_in, shift.time_out),
                COALESCE(trueshift.length_minutes, 9999999999999999)))) / 60, 0
            ) * pay.overtime_per_hour +
        COALESCE(SUM(LEAST(TIMESTAMPDIFF(MINUTE, shift.time_in, shift.time_out),
        COALESCE(trueshift.length_minutes, 9999999999999999)) / 60), 0)
        * pay.dollars_per_hour
        FROM employeeshifttransactions shift
        LEFT JOIN employeeshifts trueshift ON shift.shift = trueshift.id
        JOIN employeepay pay
        WHERE shift.employee = employees.id AND pay.id = employees.pay), 0) AS pay_including_ot,
    (SELECT warehouses.name FROM warehouses WHERE warehouses.id = employees.warehouse) AS warehouse_name,
    (SELECT pay.dollars_per_hour FROM employeepay pay WHERE pay.id = employees.pay) AS dollars_per_hour,
    (SELECT pay.overtime_per_hour FROM employeepay pay WHERE pay.id = employees.pay) AS overtime_per_hour

";
    echo json_encode(api_results(
        "employees",
        array(
            "pay" => "i",
            "warehouse" => "i"
        ), // orderable fields
        array(
            "name" => "s"
        ), // like fields
        array(), // other fields
        array(
            "name" => "s"
        ), // search fields
        $extra_select, "", "", 10000
    ));
} else if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $name = $_POST['name'];
    $warehouse = $_POST['warehouse'];
    $dollars_per_hour = $_POST['dollars_per_hour'];
    $overtime_per_hour = $_POST['overtime_per_hour'];
    
    $result = query("
        SELECT id FROM employeepay WHERE dollars_per_hour = ? AND overtime_per_hour = ?
    ", "ii", $dollars_per_hour, $overtime_per_hour);

    $data = array();
    $pay_id = NULL;
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            array_push($data, $row);
        }
        $pay_id = $data[0]["id"];
    } else {
        $pay_id = insert("
            INSERT INTO employeepay (dollars_per_hour, overtime_per_hour) VALUES (?, ?)
        ", "ii", $dollars_per_hour, $overtime_per_hour);
    }

    if ($pay_id == NULL) {
        header('HTTP/1.0 500 Server Error');
        die("Problem setting pay");
    }

    $query = "INSERT INTO inventorymgmt.employees (name, warehouse, pay) VALUES(?,?,?);";

    $newId = insert($query, "sii", $name, $warehouse, $pay_id);

    if ($newId > 0) {
        echo "Success";
    } else {
        header('HTTP/1.0 500 Server Error');
        echo "Could not create employee";
    }
} else if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
    $id = $_GET['id'];

    if (!isset($id)) {
        die("Missing parameter: id");
    }

    $query = "DELETE FROM inventorymgmt.employees WHERE id = ?;";

    execute($query, "i", $id);
}
?>