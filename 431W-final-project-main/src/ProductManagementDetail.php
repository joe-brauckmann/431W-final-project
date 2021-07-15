<!DOCTYPE html>
<html>
    <head>
        <title>Product Management</title>

        <?php 
            require "head.html";
            if (!isset($_GET["id"])) {
                die("Missing parameter: id");
            }
            
            require "utils/database.php"; 

            $result = query("
                SELECT products.*, 
                    (SELECT COALESCE(SUM(quantity_added), 0) AS sum
                    FROM `inventorymgmt`.`inventorytransactions` t
                    WHERE t.product = products.id) AS quantity,
                    warehouses.name AS warehouse_name, sectors.name AS sector_name
                FROM products, warehouses, sectors
                WHERE sectors.id = products.id AND warehouses.id = sectors.warehouse AND products.id = ?
            ", "i", $_GET["id"]);
            $data = array();
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    array_push($data, $row);
                }
            }
            
            $product = $data[0];
        ?>

        <script>
            $(document).ready(function() {
                $(".datepicker").pickadate();

                $("#reportForm").submit(function(e) {
                    e.preventDefault();
                    let data = [];
                    if (document.getElementById("chk5").checked) {
                        data.push("&start_time=" + $("#disabled5").val());
                        data.push("&end_time=" + $("#disabled5_1").val());
                    }
                    if (document.getElementById("mfr").checked) {
                        window["productTable"].order([[ 2, "desc" ]]);
                    } else {
                        window["productTable"].order([[ 2, "asc" ]]);
                    }
                    window["productTable"].ajax.url(
                        "/api/stockreport.php?pid=" + encodeURIComponent(
                            <?php echo $product["id"] ?>
                       ) + data.join("")
                    ).load();
                });
                window["productTable"] = $('#transactions').DataTable({
                    "ajax": {
                       url: "/api/stockreport.php?pid=" + encodeURIComponent(
                            <?php echo $product["id"] ?>
                       ),
                       dataSrc: '',
                    },
                    order: [[ 2, "desc" ]], // Default order by start time
                    columns: [
                        { data: 'id' },
                        { data: 'quantity_added' },
                        { data: 'timestamp' },
                        { data: 'empname' },
                    ]
                });
            });

        </script>

    </head>
<body>

<?php 
require "header.html"; 
?>

<div class="container">

<table class="table table-bordered table-striped" style="margin-top: 85px">
    <thead>
        <tr>
            <th>Product ID</th>
            <th>Product Name</th>
            <th>Quantity</th>
            <th>Warehouse</th>
            <th>Sector</th>
        </tr>
    </thead>
    <tbody id="tableContent">
        <tr>
            <td><?php echo $product["id"] ?></td>
            <td><?php echo $product["name"] ?></td>
            <td><?php echo $product["quantity"] ?></td>
            <td><?php echo $product["warehouse_name"] ?></td>
            <td><?php echo $product["sector_name"] ?></td>
        </tr>
    </tbody>
</table>

</div>

<div class="container form-container" style="display: none;">
    <div class="container-header">
        <h4>Adjust Quantity</h4>
    </div>
    <form action="#" method="POST" id="adjustForm">
        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input type="text" class="form-control" name="quantity" required>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <label><input type="radio" name="adjustBy" value="add" id="chk3">Add</label><br>
                <label>
                    <input type="radio" name="adjustBy" value="warehouse" id="chk1">Move to Warehouse
                    <input type="text" name="warehouse_name" disabled id="disabled1"><br>
                    Sector<input type="text" name="sector_name" disabled id="disabled1_1">
                </label>
            </div>
            <div class="col-sm-6">
                <label><input type="radio" name="adjustBy" value="subtract" id="chk4">Subtract</label><br>
                <label>
                    <input type="radio" name="adjustBy" value="sector" id="chk2">Move to sector
                    <input type="text" name="sector_name" disabled id="disabled2">
                </label>
            </div>
        </div>
        <input type="submit" name="submit" value="Adjust">
    </form>
</div>

<div class="container form-container">
    <div class="container-header">
        <h4>Stock Report</h4>
    </div>
    <form action="#" method="POST" id="reportForm">
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <input type="checkbox" name="select_period" value="selected" id="chk5">
                    <label for="chk5">Period:</label> 
                    <input type="text" name="start_period" class="datepicker" disabled id="disabled5" required> To
                    <input type="text" name="end_period" class="datepicker" disabled id="disabled5_1" required>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    Order By<br>
                    <label><input type="radio" name="order_by" value="MRF" id="mfr" checked>Most Recent First</label><br>
                    <label><input type="radio" name="order_by" value="OF" if="of">Oldest First</label>
                </div>
            </div>
        </div>
        <input type="submit" name="submit" value="Update">
    </form>
    <br>

    <table class="table table-bordered table-striped" id="transactions">
        <thead>
            <tr>
                <th>Transaction ID</th>
                <th>Quantity</th>
                <th>Timestamp</th>
                <th>Employee</th>
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
    $("#disabled1_1").attr('disabled', !this.checked)
    $("#disabled2").attr('disabled', this.checked)
});

$("#chk2").click(function(){
    $("#disabled1").attr('disabled', this.checked)
    $("#disabled1_1").attr('disabled', this.checked)
    $("#disabled2").attr('disabled', !this.checked)
});

$("#chk3").click(function() {
    $("#disabled1").attr('disabled', this.checked)
    $("#disabled1_1").attr('disabled', this.checked)
    $("#disabled2").attr('disabled', this.checked)
});

$("#chk4").click(function() {
    $("#disabled1").attr('disabled', this.checked)
    $("#disabled1_1").attr('disabled', this.checked)
    $("#disabled2").attr('disabled', this.checked)
});

$("#chk5").click(function() {
    $("#disabled5").attr('disabled', !this.checked);
    $("#disabled5_1").attr('disabled', !this.checked);
})

</script>

</body>
</html>
