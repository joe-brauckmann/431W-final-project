<?php 

    require "utils/database.php"; 

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $id = $_POST['id'];
    
        if (!isset($id)) {
            die("Missing parameter: id");
        }
    
        $employee_name = $_POST['name'];
        $warehouse = $_POST['warehouse'];
        $dollars_per_hour = $_POST['wage'];
        $overtime_per_hour = $_POST['ot_wage'];

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


        $params = array();
        $paramTypes = array();
        $inserts = array();
    
        if (isset($employee_name)) {
            array_push($inserts, "name = ?");
            array_push($params, $employee_name);
            array_push($paramTypes, "s");
        }
        if (isset($warehouse)) {
            array_push($inserts, "warehouse = ?");
            array_push($params, $warehouse);
            array_push($paramTypes, "i");
        }
    
        array_push($inserts, "pay = ?");
        array_push($params, $pay_id);
        array_push($paramTypes, "i");
        array_push($params, $id);
        array_push($paramTypes, "i");
    
        query("
            UPDATE employees SET " . join($inserts, ", ") . "
            WHERE id = ?
        ", join($paramTypes), ...$params);
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Product Management</title>

        <?php 
            require "head.html";
            $id = $_GET["id"] ?? $_POST["id"];
            if (!isset($id)) {
                die("Missing parameter: id");
            }
            

            $result = query("
                SELECT employees.*,
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
    
                FROM employees WHERE employees.id = ?
            ", "i", $id);
            $data = array();
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    array_push($data, $row);
                }
            }
            
            $employee = $data[0];
        ?>

        <script>

            $(document).ready(function() {
                $(".datepicker").pickadate();
                $("#employeeHoursForm").submit(function(e) {
                    e.preventDefault();
                    let data = [];
                    if (document.getElementById("chk5").checked) {
                        data.push("&start_time=" + $("#disabled5").val());
                        data.push("&end_time=" + $("#disabled5_1").val());
                    }
                    if (document.getElementById("MRF").checked) {
                        window["employeeTable"].order([[ 0, "desc" ]]);
                    } else {
                        window["employeeTable"].order([[ 0, "asc" ]]);
                    }
                    window["employeeTable"].ajax.url(
                        "/api/employeereport.php?id=" + encodeURIComponent(
                            <?php echo $employee["id"] ?>
                        ) + data.join("")
                    ).load();
                });
                window["employeeTable"] = $('#shifts').DataTable({
                    "ajax": {
                       url: "/api/employeereport.php?id=" + encodeURIComponent(
                            <?php echo $employee["id"] ?>
                       ),
                       dataSrc: '',
                    },
                    order: [[ 0, "desc" ]], // Default order by start time
                    columns: [
                        { data: 'time_in' },
                        { data: 'time_out' },
                        { data: 'hours' },
                    ]
                });
            });

        </script>

    </head>
<body>

<?php require "header.html"; ?>

<div class="container">

<table class="table table-bordered table-striped" style="margin-top: 85px">
    <thead>
        <tr>
            <th>Employee ID</th>
            <th>Employee Name</th>
            <th>Warehouse</th>
            <th>Hours</th>
            <th>Wage</th>
            <th>OT Wage</th>
            <th>Pay for Period</th>
        </tr>
    </thead>
    <tbody id="tableContent">
        <tr>
            <td><?php echo $employee["id"] ?></td>
            <td><?php echo $employee["name"] ?></td>
            <td><?php echo $employee["warehouse_name"] ?></td>
            <td><?php echo $employee["hours_including_ot"] ?></td>
            <td><?php echo $employee["dollars_per_hour"] ?></td>
            <td><?php echo $employee["overtime_per_hour"] ?></td>
            <td><?php echo $employee["pay_including_ot"] ?></td>
        </tr>
    </tbody>
</table>

</div>


<div class="container form-container">
    <div class="container-header">
        <h4>Edit Employee</h4>
    </div>
    <form method="POST" action="EmployeeManagementDetail.php">
        <div class="row">
            <div class="col-6">
                <input type="hidden" name="id" value="<?php echo $employee["id"] ?>" />
                <label for="name">Employee Name:</label>
                <input type="text" name="name" value="<?php echo $employee["name"] ?>">
                <br>
                <label for="name">Warehouse:</label>
                <input type="number" name="warehouse" value="<?php echo $employee["warehouse"] ?>">
            </div>
            <div class="col-6">
                <label for="name">Wage:</label>
                <input type="text" name="wage" value="<?php echo $employee["dollars_per_hour"] ?>">
                <br>
                <label for="name">OT Wage:</label>
                <input type="text" name="ot_wage" value="<?php echo $employee["overtime_per_hour"] ?>">
            </div>    
        </div>
        <input type="submit" value="Update Employee">
    </form>
</div>

<div class="container form-container">
    <div class="container-header">
        <h4>Employee Hours</h4>
    </div>
    <form action="#" method="POST" id="employeeHoursForm">
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="select_period" value="selected" id="chk5">Period:
                    </label>
                    <input type="text" class="datepicker" name="start_period" disabled id="disabled5" required> To
                    <input type="text" class="datepicker" name="end_period" disabled id="disabled5_1" required>
                </div>
            </div>
            <div class="col-sm-6">
                Order By<br>
                <label><input type="radio" name="order_by" value="MRF" id="MRF" checked>Most Recent First</label><br>
                <label><input type="radio" name="order_by" value="MRF">Oldest First</label>
            </div>
        </div>
        <input type="submit" name="submit" value="Update">
    </form>
    <br>
    <table class="table table-bordered table-striped" id="shifts">
        <thead>
            <tr>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Length</th>
            </tr>
        </thead>
        <tbody id="tableContent">
        </tbody>
    </table>
</div>

<script>

// Form Stuff
$("#chk1").click(function(){
    $("#disabled1").attr('disabled', !this.checked)
});

$("#chk2").click(function(){
    $("#disabled2").attr('disabled', !this.checked)
});

$("#chk3").click(function() {
    $("#disabled3").attr('disabled', !this.checked)
});

$("#chk4").click(function() {
    $("#disabled4").attr('disabled', !this.checked)
});

$("#chk5").click(function() {
    $("#disabled5").attr('disabled', !this.checked)
    $("#disabled5_1").attr('disabled', !this.checked)
});

</script>

</body>
</html>
