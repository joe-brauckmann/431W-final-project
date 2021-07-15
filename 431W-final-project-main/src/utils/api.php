<?php 

require 'database.php';

//* s is for strings
// * d is for decimals
// * i is for integers
// * b is for blob
function api_results($table,
            $orderable_fields, $like_fields, $other_fields,
            $search_fields, $extra_select = "", $extra_tables = "", $extra_ands = "", $limit = 100) {
    $whereClauses = array();
    $paramTypes = array();
    $params = array();

    // Filters

    // Orderable fields (=, <, <=, >, >=)
    foreach ($orderable_fields as $key => $value) {
        // Equals clause
        if (isset($_GET[$key])) {
            array_push($whereClauses, $key . " = ?");
            array_push($paramTypes, $value);
            array_push($params, $_GET[$key]);
        }
        // Greater than clause
        if (isset($_GET[$key . "__gt"])) {
            array_push($whereClauses, $key . " > ?");
            array_push($paramTypes, $value);
            array_push($params, $_GET[$key . "__gt"]);
        }
        // Greater than or equal to clause
        if (isset($_GET[$key . "__gte"])) {
            array_push($whereClauses, $key . " >= ?");
            array_push($paramTypes, $value);
            array_push($params, $_GET[$key . "__gte"]);
        }
        // Less than clause
        if (isset($_GET[$key . "__lt"])) {
            array_push($whereClauses, $key . " < ?");
            array_push($paramTypes, $value);
            array_push($params, $_GET[$key . "__lt"]);
        }
        // Greater than or equal to clause
        if (isset($_GET[$key . "__lte"])) {
            array_push($whereClauses, $key . " <= ?");
            array_push($paramTypes, $value);
            array_push($params, $_GET[$key . "__lte"]);
        }
    }

    // Like fields (LIKE '%?%')
    foreach ($like_fields as $key => $value) {
        if (isset($_GET[$key])) {
            array_push($whereClauses, $key . " LIKE ?");
            array_push($paramTypes, $value);
            array_push($params, "%" . $_GET[$key] . "%");
        }
    }

    // Other fields (only equals)
    foreach ($other_fields as $key => $value) {
        if (isset($_GET[$key])) {
            array_push($whereClauses, $key . " = ?");
            array_push($paramTypes, $value);
            array_push($params, $_GET[$key]);
        }
    }

    $searchClauses = array();
    // Search fields (search all together)
    if (isset($_GET["q"])) {
        foreach ($search_fields as $key => $value) {
            array_push($searchClauses, $key . " LIKE ?");
            array_push($paramTypes, $value);
            array_push($params, "%" . $_GET["q"] . "%");
        }
    }

    if (count($searchClauses) > 0) {
        array_push($whereClauses, join(" OR ", $searchClauses));
    }

    // print_r($whereClauses);
    // print_r($params);
    //die($params);

    $result = NULL;

    $selectPart = strlen($extra_select) > 0 ? "*, " . $extra_select : "*";
    $tables = strlen($extra_tables) > 0 ? $table . ", " . $extra_tables : $table;
    if (strlen($extra_ands) > 0) {
        array_push($whereClauses, $extra_ands);
    }
    if (count($whereClauses) > 0) {
        // $qstring = "SELECT " . $selectPart . " FROM " . $tables . " WHERE " . join(" AND ", $whereClauses) . " LIMIT 100";
        // echo $qstring;
        $result = query(
            "SELECT " . $selectPart . " FROM " . $tables . " WHERE " . join(" AND ", $whereClauses) . " LIMIT " . $limit,
            join($paramTypes),
            ...$params
        );
    } else {
        $result = query("SELECT " . $selectPart . " FROM " . $tables . " LIMIT " . $limit);
    }
    $data = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            array_push($data, $row);
        }
    }
    return $data;
}

?>
