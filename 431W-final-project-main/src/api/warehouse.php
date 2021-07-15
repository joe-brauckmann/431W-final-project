<?php

require '../utils/api.php';

echo json_encode(api_results(
    "warehouses",
    array(), // orderable fields
    array(
        "name" => "s"
    ), // like fields
    array(), // other fields
    array(
        "name" => "s"
    ) // search fields
));

?>