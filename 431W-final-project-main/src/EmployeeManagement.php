<!DOCTYPE html>
<html>
    <head>
        <title>Employee Management</title>

        <?php require "head.html" ?>

        <script>

        $(document).ready(function () {
            $("#createForm").on("submit", function(event){
                event.preventDefault();

                var formValues = $(this).serializeArray().reduce(
                    (obj, v) => {
                        obj[v.name] = v.value;
                        return obj;
                    }, {}
                );
                $.ajax({
                    url: "api/employee.php",
                    method: "POST",
                    data: formValues,
                    success: function() {
                        swal("Success", "Employee hired!", "success");
                        window.employeeTable.ajax.reload();
                    },
                    error: function(xhr, e) {
                        swal("Could not hire employee", xhr.responseText, "error");
                    }
                });
            });

            $("#searchForm").on("submit", function(event){
                event.preventDefault();
                var formValues = $(this).serialize();
                $("#results").html(formValues);
                //
                // $.post("testForm.php", formValues, function(data){
                //     // Display the returned data in browser
                //     $("#result").html(data);
                // });
            });
            setUpSelect(
                $("#createForm select[name='warehouse']"),
                "api/warehouse.php",
                "id",
                "name"
            );
        });

        function roundTableNumber(data, type, row, meta) {
            if (type === "display") {
                data = Math.round((parseFloat(data) + Number.EPSILON) * 100) / 100;
            }
            return data;
        }

        $(document).ready(function() {
            window["employeeTable"] = $('#EmployeeMgmtTable').DataTable({
                "ajax": {
                   url: "/api/employee.php",
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
                    { 
                        data: 'hours_excluding_ot',
                        render: roundTableNumber
                    },
                    { 
                        data: 'overtime',
                        render: roundTableNumber
                    },
                    { 
                        data: 'hours_including_ot',
                        render: roundTableNumber
                    },
                    { 
                        data: 'dollars_per_hour',
                        render: roundTableNumber
                    },
                    { 
                        data: 'overtime_per_hour',
                        render: roundTableNumber
                    },
                    { 
                        data: 'pay_including_ot',
                        render: roundTableNumber
                    },
                ]
            });
        } );

        </script>

    </head>
<body>

<?php 

require "header.html"; 
require "utils/database.php"; 

$result = query("
    SELECT * FROM warehouses LIMIT 50
");
$warehouses = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        array_push($warehouses, $row);
    }
}

?>

<div class="container form-container" style="margin-top: 85px;">
    <div class="container-header">
        <h4>Hire Employee</h4>
    </div>
    <form action="#" method="POST" id="createForm">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" class="form-control" name="name" required>
        </div>
        <div class="form-group">
            <label for="warehouse">Warehouse</label>
            <select name="warehouse" class="form-control" required>
                <?php 
                    foreach ($warehouses as $key => $warehouses) {
                        echo "<option value='" . $warehouses["id"] . "'>" . $warehouses["name"] . "</option>";
                    }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="dollars_per_hour">Wage ($ per hour)</label>
            <input type="number"
                name="dollars_per_hour" class="form-control"
                step="0.1" required />
        </div>
        <div class="form-group">
            <label for="overtime_per_hour">Overtime Wage ($ per hour)</label>
            <input type="number"
                name="overtime_per_hour" class="form-control"
                step="0.1" required />
        </div>
        <input type="submit" name="submit" value="Add">
    </form>
</div>

<div class="container form-container" style="display:none;"> <!-- temporarily hidden -->
    <div class="container-header">
        <h4>Search By</h4>
    </div>
    <form action="#" method="POST" id="searchForm">
        <div class="row">
            <div class="col-sm-6">
                <label><input type="checkbox" name="searchVal[]" value="warehouse" id="chk1"> Product Name <input type="text" name="product_name" disabled id="disabled1"></label><br>
                <label><input type="checkbox" name="searchVal[]" value="employees_name" id="chk2"> Warehouse <input type="text" name="warehouse" disabled id="disabled2"></label><br>
            </div>
            <div class="col-sm-6">
                <h5>Order By:</h5>
                <label><input type="radio" name="order_by" value="employees_id" checked>Employee ID</label><br>
                <label><input type="radio" name="order_by" value="employees_name">Employee Name</label><br>
            </div>
        </div>
        <input type="submit" name="submit" value="Search">
    </form>
</div>

<div class="container form-container">
    <div class="container-header">
        <h4>Employees</h4>
    </div>

<table id="EmployeeMgmtTable" class="table table-striped">
    <thead>
        <tr>
            <th class="sorting">Employee ID (Click for more details)</th>
            <th class="sorting">Employee Name</th>
            <th class="sorting">Warehouse</th>
            <th class="sorting">Hours (Excluding Overtime)</th>
            <th class="sorting">Overtime Hours</th>
            <th class="sorting">Total Hours</th>
            <th class="sorting">Wage ($ per hour)</th>
            <th class="sorting">OT Wage ($ per hour)</th>
            <th class="sorting">Total Pay</th>
        </tr>
    </thead>
</table>

</div>

<div id="results">
</div>

<script>

// Form Stuff
$("#chk1").click(function(){
    $("#disabled1").attr('disabled', !this.checked)
});

$("#chk2").click(function(){
    $("#disabled2").attr('disabled', !this.checked)
});

$("#chk3").click(function(){
    $("#disabled3").attr('disabled', !this.checked)
});

</script>

</body>
</html>
