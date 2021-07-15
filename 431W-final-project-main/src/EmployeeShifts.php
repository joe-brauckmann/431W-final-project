<!DOCTYPE html>
<html>
    <head>
        <title>Product Management</title>

        <?php require "head.html" ?>

        <script>

        Date.prototype.addHours = function(h) {
            this.setTime(this.getTime() + (h*60*60*1000));
            return this;
        }

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
                    url: "api/employeeshift.php",
                    method: "POST",
                    data: {
                        start_time: new Date(formValues["start_date"] + " " + formValues["start_time"]).toLocaleString(),
                        length_minutes: formValues["length"]
                    },
                    success: function() {
                        swal("Success", "Shift created!", "success");
                        window.shiftTable.ajax.reload();
                    },
                    error: function(xhr, e) {
                        swal("Could not create shift", xhr.responseText, "error");
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
                $("#createForm select[name='warehouse_name']"),
                "api/warehouse.php",
                "id",
                "name"
            );
            $('#datepicker').pickadate();
            $('#timepicker').pickatime();
        });

        $(document).ready(function() {
            window["shiftTable"] = $('#EmployeeShiftTable').DataTable({
                "ajax": {
                   url: "/api/employeeshift.php",
                   dataSrc: '',
                },
                "order": [[ 1, "desc" ]], // Default order by start time
                "columns": [
                    { "data": "id" },
                    {
                        "data": "start_time",
                        "render": function(data, type, row, meta) {
                            if (type === "display") {
                                data = new Date(data).toLocaleString("en-US");
                            }
                            return data;
                        }
                    },
                    {
                        "data": "length_minutes",
                        "render": function(data, type, row, meta) {
                            if (type === "display") {
                                data /= 60;
                            }
                            return data;
                        }
                    },
                    {
                        "data": "length_minutes",
                        "render": function(data, type, row, meta) {
                            if (type === "display") {
                                let date = new Date(row.start_time);
                                date.addHours(data / 60);
                                data = date.toLocaleString("en-US");
                            }
                            return data;
                        }
                    },
                ]
            } );
        } );
        </script>

    </head>
<body>

<?php require "header.html"; ?>

<div class="container form-container" style="margin-top: 85px;">
    <div class="container-header">
        <h4>Create New Shift</h4>
    </div>
    <form action="#" method="POST" id="createForm">
        <div class="form-group">
            <label for="datepicker">Start Date</label>
            <input type='text' class="form-control" id="datepicker" name="start_date" required />
        </div>
        <div class="form-group">
            <label for="timepicker">Start Time</label>
            <input type='text' class="form-control" id="timepicker" name="start_time" required />
        </div>
        <div class="form-group">
            <label for="length">Length (minutes)</label>
            <input type="number" class="form-control" name="length" required>
        </div>
        <input type="submit" name="submit" value="Add">
    </form>
</div>

<div class="container form-container" style="display: none;"> <!-- Temporarily hidden -->
    <div class="container-header">
        <h4>Search By</h4>
    </div>
    <form action="#" method="POST" id="searchForm">
        <div class="row">
            <div class="col-sm-6">
                <label> Period <input type="text" name="employeeshifts_start_time" disabled id="disabled1"></label>
                <label> To <input type="text" name="employeeshifts_start_time" disabled id="disabled1"></label><br>
                
            </div>
            <div class="col-sm-6">
                <h5>Order By:</h5>
                <label><input type="radio" name="order_by" value="employeeshifts_id" checked>Shift ID</label><br>
                <label><input type="radio" name="order_by" value="employeeshifts_start_time">Date Newest</label><br>
                <label><input type="radio" name="order_by" value="employeeshifts_start_time">Date Oldest</label><br>
            </div>
        </div>
        <input type="submit" name="submit" value="Search">
    </form>
</div>

<div class="container form-container">
    <div class="container-header">
        <h4>Saved Shifts</h4>
    </div>
    <table id="EmployeeShiftTable" class="table table-striped">
        <thead>
            <tr>
                <th>Shift ID</th>
                <th>Start Time</th>
                <th>Hours</th>
                <th>End Time</th>
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
