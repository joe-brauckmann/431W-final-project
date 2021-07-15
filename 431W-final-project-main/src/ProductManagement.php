<!DOCTYPE html>
<html>
    <head>
        <title>Product Management</title>

        <?php require "head.html" ?>

        <script>

        $(document).ready(function () {
            $("select[name='from_warehouse']").on("changed.bs.select", function(e, clickedIndex, newValue, oldValue) {
                $("select[name='from_sector']").empty();
                $.ajax({
                    url: "/api/sectors.php?warehouse=" + this.value,
                    method: "GET",
                    success: function(data) {
                        for (let sector of JSON.parse(data)) {
                            $("select[name='from_sector']").append(
                                "<option value='" + sector.id + "'>" + sector.name + "</option>"
                            );
                        }
                        $('.select').selectpicker('refresh');
                    }
                });
            });
            $("select[name='to_warehouse']").on("changed.bs.select", function(e, clickedIndex, newValue, oldValue) {
                $("select[name='to_sector']").empty();
                $.ajax({
                    url: "/api/sectors.php?warehouse=" + this.value,
                    method: "GET",
                    success: function(data) {
                        for (let sector of JSON.parse(data)) {
                            $("select[name='to_sector']").append(
                                "<option value='" + sector.id + "'>" + sector.name + "</option>"
                            );
                        }
                        $('.select').selectpicker('refresh');
                    }
                });
            });
            $.ajax({
                url: "/api/sectors.php?warehouse=" + $("select[name='from_warehouse']").val(),
                method: "GET",
                success: function(data) {
                    for (let sector of JSON.parse(data)) {
                        $("select[name='from_sector']").append(
                            "<option value='" + sector.id + "'>" + sector.name + "</option>"
                        );
                    }
                    $('.select').selectpicker('refresh');
                }
            });
            $.ajax({
                url: "/api/sectors.php?warehouse=" + $("select[name='to_warehouse']").val(),
                method: "GET",
                success: function(data) {
                    for (let sector of JSON.parse(data)) {
                        $("select[name='to_sector']").append(
                            "<option value='" + sector.id + "'>" + sector.name + "</option>"
                        );
                    }
                    $('.select').selectpicker('refresh');
                }
            });
            $("#createForm").on("submit", function(event){
                event.preventDefault();
        
                var formValues = $(this).serializeArray().reduce(
                    (obj, v) => {
                        obj[v.name] = v.value;
                        return obj;
                    }, {}
                );
                if (document.getElementById("move-from").checked) {
                    delete formValues["from_sector"];
                }
                if (document.getElementById("move-to").checked) {
                    delete formValues["to_sector"];
                }
                $.ajax({
                    url: "api/move_product.php",
                    method: "POST",
                    data: formValues,
                    success: function() {
                        swal("Success", "Product moved!", "success");
                        window.productTable.ajax.reload();
                    },
                    error: function(xhr, e) {
                        swal("Could not move product", xhr.responseText, "error");
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
                $("#createForm select[name='from_warehouse']"),
                "api/warehouse.php",
                "id",
                "name"
            );
            setUpSelect(
                $("#createForm select[name='to_warehouse']"),
                "api/warehouse.php",
                "id",
                "name"
            );
            setUpSelect(
                $("#createForm select[name='employee']"),
                "api/employee.php",
                "id",
                "name"
            );

            $("#move-from").click(function() {
                if (document.getElementById("move-from").checked) {
                    $(".move-from").attr("disabled", true);
                } else {
                    $(".move-from").removeAttr("disabled");
                }
                $('.select').selectpicker('refresh');
            });

            $("#move-to").click(function() {
                if (document.getElementById("move-to").checked) {
                    $(".move-to").attr("disabled", true);
                } else {
                    $(".move-to").removeAttr("disabled");
                }
                $('.select').selectpicker('refresh');
            });

            $("#createForm select[name='warehouse_name']").on('changed.bs.select', function(e, clickedIndex, isSelected, previousValue) {
                let warehouseId = $("#createForm select[name='warehouse_name']").val();
                $.ajax({
                    url: "/api/sectors.php?warehouse=" + encodeURIComponent(warehouseId),
                    success: function(data) {
                        data = JSON.parse(data);
                        $("#createForm select[name='sector_name']").empty();
                        for (let element of data) {
                            $("#createForm select[name='sector_name']").append('<option val="' + element.id + '">' + element.name + '</option>');
                        }
                        $("#createForm select[name='sector_name']").selectpicker("refresh");
                    }
                });
            });

            window["productTable"] = $('#product-table').DataTable({
                "ajax": {
                   url: "/api/products.php",
                   dataSrc: '',
                },
                columns: [
                    { 
                        data: 'id',
                        render: function(data, type, row, meta) {
                            if (type === "display") {
                                data = '<a href="ProductManagementDetail.php?id=' + data + '">' + data + '</a>';
                            }
                            return data;
                        }
                    },
                    { data: 'name' },
                    { data: 'quantity' },
                    { data: 'warehouse_name' },
                    { data: 'sector_name' },
                ]
            });
        });
        </script>

    </head>
<body>

<?php 

require "header.html"; 
require "utils/database.php"; 

$result = query("
    SELECT * FROM warehouses ORDER BY name LIMIT 1000
");
$warehouses = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        array_push($warehouses, $row);
    }
}

$result = query("
    SELECT * FROM employees ORDER BY name LIMIT 1000
");
$employees = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        array_push($employees, $row);
    }
}

?>

<div class="container form-container" style="margin-top: 85px;">
    <div class="container-header">
        <h4>Manage Inventory</h4>
    </div>
    <form action="#" method="POST" id="createForm">
        <div class="form-group">
            <label for="product_name">Product Name</label><span class="form-validate"></span>
            <input type="text" class="form-control" name="product_name" required>
        </div>
        <div class="form-group">
            <label for="quantity">Quantity</label><span class="form-validate"></span>
            <input type="number" class="form-control" name="quantity" required>
        </div>
        <div class="form-group">
            <label for="employee">Employee Performing Transaction</label><span class="form-validate"></span>
            <select name="employee" class="form-control select"  data-size="10">
                <?php 
                    foreach ($employees as $key => $employee) {
                        echo "<option value='" . $employee["id"] . "'>" . $employee["name"] . "</option>";
                    }
                ?>
            </select>
        </div>
        <div class="row">
            <div class="col-6">
                <input type="checkbox" id="move-from"/>
                <label for="move-from">Moving from the outside</label>
                <div class="form-group">
                    <label for="from_warehouse">From Warehouse</label><span class="form-validate" id="create_warehouse"></span>
                    <select name="from_warehouse" class="form-control move-from select" data-size="10">
                        <?php 
                            foreach ($warehouses as $key => $warehouse) {
                                echo "<option value='" . $warehouse["id"] . "'>" . $warehouse["name"] . "</option>";
                            }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="from_sector">From Sector</label><span class="form-validate" id="create_sector"></span>
                    <select name="from_sector" class="form-control move-from select selectpicker">
                    </select>
                </div>
            </div>
            <div class="form-group col-6">
                <input type="checkbox" id="move-to"/>
                <label for="move-to">Moving to the outside</label>
                <div class="form-group">
                    <label for="to_warehouse">To Warehouse</label><span class="form-validate" id="create_warehouse"></span>
                    <select name="to_warehouse" class="form-control move-to select" data-size="10">
                        <?php 
                        foreach ($warehouses as $key => $warehouse) {
                            echo "<option value='" . $warehouse["id"] . "'>" . $warehouse["name"] . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="to_sector">To Sector</label><span class="form-validate" id="create_sector"></span>
                    <select name="to_sector" class="form-control move-to select selectpicker">
                        <?php 
                            foreach ($sectors as $key => $sector) {
                                echo "<option value='" . $sector["id"] . "'>" . $sector["name"] . "</option>";
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <input type="submit" name="submit" value="Move">
    </form>
</div>

<div class="container form-container" style="display: none;"> <!-- Temporarily hidden-->
    <div class="container-header">
        <h4>Search By</h4>
    </div>
    <form action="#" method="POST" id="searchForm">
        <div class="row">
            <div class="col-sm-6">
                <label><input type="checkbox" name="searchVal[]" value="product_name" id="chk1"> Product Name <input type="text" name="product_name" disabled id="disabled1"></label><br>
                <label><input type="checkbox" name="searchVal[]" value="warehouse_name" id="chk2"> Warehouse <input type="text" name="warehouse_name" disabled id="disabled2"></label><br>
                <label><input type="checkbox" name="searchVal[]" value="sector_name" id="chk3"> Sector <input type="text" name="sector_name" disabled id="disabled3"></label><br>
            </div>
            <div class="col-sm-6">
                <h5>Order By:</h5>
                <label><input type="radio" name="order_by" value="product_name" checked>Product Name</label><br>
                <label><input type="radio" name="order_by" value="MRI">Most Recent Item</label><br>
                <label><input type="radio" name="order_by" value="MRT">Most Recent Transaction</label><br>
            </div>
        </div>
        <input type="submit" name="submit" value="Search">
    </form>
</div>

<div class="container">

<table id="product-table" class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Product ID (Click for more details)</th>
            <th>Product Name</th>
            <th>Quantity</th>
            <th>Warehouse</th>
            <th>Sector</th>
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
