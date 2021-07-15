<?php

    require '../utils/database.php';

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $employee = $_POST["employee"];
        $is_clock_out = $_POST["is_clock_out"];

        if (!isset($employee) || !isset($is_clock_out)) {
            header('HTTP/1.0 400 Bad Request');
            exit("Missing parameter: Employee or is clock out");
        }

        if ($is_clock_out == "true") {
            $result = query("
                SELECT id FROM `employeeshifttransactions` WHERE
                    employee = ? AND time_out IS NULL
            ", "i", $employee);
            $data = array();
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    array_push($data, $row);
                }
            } else {
                header('HTTP/1.0 400 Bad Request');
                exit("Employee is not clocked in");
            }
            // 20 minute grace period before/after shift
            $result = execute("
                UPDATE `employeeshifttransactions` SET time_out = now() WHERE id = ?; 
            ", "i", $data[0]["id"]);
            echo "Success";
        } else {
            $result = query("
                SELECT COALESCE(COUNT(*), 0) as count FROM `employeeshifttransactions` WHERE
                    employee = ? AND time_out IS NULL
            ", "i", $employee);
            $data = array();
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    array_push($data, $row);
                }
            }
            if ($data[0]["count"] > 0) {
                header('HTTP/1.0 400 Bad Request');
                exit("Employee is already clocked in");
            }
            // 20 minute grace period before/after shift
            $result = execute("
                INSERT INTO `employeeshifttransactions` (employee, shift, time_in) VALUES
                (?, 
                (SELECT id from employeeshifts WHERE 
                    DATE_SUB(start_time, INTERVAL 20 MINUTE) < DATE_ADD(now(), INTERVAL length_minutes MINUTE)
                    AND DATE_ADD(start_time, INTERVAL (length_minutes + 20) MINUTE) >= now()),
                now()); 
            ", "i", $employee);

            echo "Success";
        }

    } else if ($_SERVER['REQUEST_METHOD'] == "GET") {
        // Return all employees on shift
        $result = query("
            SELECT employee.id, employee.name, pay.dollars_per_hour, pay.overtime_per_hour, warehouse.name AS warehouse_name
            FROM `employeeshifttransactions`, `employees` employee, `employeepay` pay, warehouses warehouse
                WHERE employee.pay = pay.id AND employeeshifttransactions.employee = employee.id
                 AND warehouse.id = employee.warehouse
                 AND employeeshifttransactions.time_out IS NULL
        ");
        $data = array();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                array_push($data, $row);
            }
        }
        echo json_encode($data);
    } else {
        header('HTTP/1.0 403 Forbidden');
        echo "Invalid operation. Allowed operations: GET, POST";
    }
?>
