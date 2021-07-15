<?php
    $hostname = "db";
    $username = "root";
    $password = "431w";
    $db = "inventorymgmt";

    // $paramTypes is like "sii"
    function query($sql, $paramTypes = NULL, ...$paramValues) {
        global $hostname, $username, $password, $db;
        $conn = new mysqli($hostname, $username, $password, $db);
        if ($conn->connect_error) {
            die("Failed to connect to database: " . $conn->connect_error);
        }

        $statement = $conn->prepare($sql);
        if ($paramTypes) {
            $statement->bind_param($paramTypes, ...$paramValues);
        }

        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $conn->close();
        return $result;
        // Use like:
        // if ($result->num_rows > 0) {
        //   while($row = $result->fetch_assoc()) {
        //     echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        //   }
        // } else {
        //   echo "0 results";
        // }
    }

    // Param Array is defined like
    // array(
    //     replaceOne  => type,
    //     replaceTwo => type,
    //     ...
    // )
    // Example:
    // array(
    //     "Canned Tuna"  => "s", // for string
    // )
    function execute($sql, $paramTypes = NULL, ...$paramValues) {
        global $hostname, $username, $password, $db;
        $conn = new mysqli($hostname, $username, $password, $db);
        if ($conn->connect_error) {
            die("Failed to connect to database: " . $conn->connect_error);
        }

        $statement = $conn->prepare($sql);
        if ($paramTypes) {
            $statement->bind_param($paramTypes, ...$paramValues);
        }

        $result = $statement->execute();
        $statement->close();
        $conn->close();
        return $result;
    }


    // Returns the last inserted id
    function insert($sql, $paramTypes = NULL, ...$paramValues) {
        global $hostname, $username, $password, $db;
        $conn = new mysqli($hostname, $username, $password, $db);
        if ($conn->connect_error) {
            die("Failed to connect to database: " . $conn->connect_error);
        }

        $statement = $conn->prepare($sql);
        if ($paramTypes) {
            $statement->bind_param($paramTypes, ...$paramValues);
        }

        $lastInsertId = NULL;
        $statement->execute();
        $result = $statement->get_result();
        $lastInsertId = $statement->insert_id;
        $statement->close();
        $conn->close();
        return $lastInsertId;
    }

    function get_mysqli() {
        global $hostname, $username, $password, $db;
        $conn = new mysqli($hostname, $username, $password, $db);
        if ($conn->connect_error) {
            die("Failed to connect to database: " . $conn->connect_error);
        }
        return $conn;
    }

?>
