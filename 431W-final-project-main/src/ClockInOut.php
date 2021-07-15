<!DOCTYPE html>
<html>
    <head>
        <title>Clock In/Out</title>

        <?php require "head.html" ?>

        <script>

        function clockInOut(employeeId, isClockOut) {
            return $.ajax({
                url: "api/clock.php",
                method: "POST",
                data: {
                    employee: employeeId,
                    is_clock_out: isClockOut
                },
                complete: function() {
                    window.datatable.ajax.reload();
                }
            });
        }

        $(document).ready(function() {
            setUpSelect(
                $("#clockInForm select[name='employee_name']"),
                "api/employee.php",
                "id",
                "name"
            );
            
    
            $("#clockInForm").on("submit", function(event){
                event.preventDefault();
                $("input[type='submit']").attr("disabled", true);
                clockInOut($("#clockInForm select[name='employee_name']").val(), false).then(function() {
                    $("input[type='submit']").removeAttr("disabled");
                }).catch(function() {
                    swal("Error", "This employee is already clocked in.", "error");
                    $("input[type='submit']").removeAttr("disabled");
                });
                return false;
            });
    
            window["datatable"] = $('#clocked-in-table').DataTable({
                "ajax": {
                   url: "/api/clock.php",
                   dataSrc: '',
                },
                columns: [
                    { 
                        data: 'id',
                        render: function(data, type, row, meta) {
                            if (type === "display") {
                                data = '<a href="EmployeeManagementDetail.php?id=' + data + '">' + data + '</a>';
                            }
                            return data;
                        }
                    },
                    { data: 'name' },
                    { data: 'warehouse_name' },
                    { data: 'dollars_per_hour' },
                    { data: 'overtime_per_hour' },
                    { 
                        data: 'id',
                        render: function(data, type, row, meta) {
                            if (type === "display") {
                                data = '<button type="submit" onclick="clockInOut(' + data + ', true);">Clock Out</button>';
                            }
                            return data;
                        }
                    },
                ],
                language: {
                    emptyTable: "No employees clocked in"
                }
            });

        });
        </script>

    </head>
<body>

<?php 

require "header.html"; 
require "utils/database.php"; 

$result = query("
    SELECT * FROM employees LIMIT 50
");
$employees = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        array_push($employees, $row);
    }
}

?>


<div class="container form-container"  style="margin-top: 85px;">
    <div class="container-header">
        <h4>Clock Employee In</h4>
    </div>
    <form action="#" method="POST" id="clockInForm">
        <div class="form-group">
            <label for="time_in">Employee</label>
            <select class="form-control" name="employee_name" required>
                <?php 
                    foreach ($employees as $key => $employee) {
                        echo "<option value='" . $employee["id"] . "'>" . $employee["name"] . "</option>";
                    }
                ?>
            </select>
        </div>
        <input type="submit" name="submit" value="Clock In">
    </form>
</div>


<div class="container form-container">

    <div class="container-header">
        <h4>Employees On the Clock</h4>
    </div>
<table id="clocked-in-table" class="table table-bordered table-striped" style="background-color: white;">
    <thead>
        <tr>
            <th>Employee ID</th>
            <th>Employee Name</th>
            <th>Warehouse</th>
            <th>Wage</th>
            <th>OT Wage</th>
            <th>Clock Out</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>

</div>

</body>
</html>
