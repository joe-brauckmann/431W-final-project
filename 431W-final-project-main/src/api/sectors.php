<?php

require '../utils/api.php';

echo json_encode(api_results(
    "sectors",
    array(), // orderable fields
    array(
        "name" => "s"
    ), // like fields
    array(
        "warehouse" => "i"
    ), // other fields
    array(
        "name" => "s"
    ) // search fields
));

?>