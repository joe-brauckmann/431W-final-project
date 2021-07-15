<?php

    require '../utils/database.php';

    if ($_SERVER['REQUEST_METHOD'] == "POST") {

        // PARAMETERS in POST body
        $product_name = $_POST["product_name"];
        $quantity = $_POST["quantity"];
        $employee = $_POST["employee"];

        // At least one of these is required
        $to_sector = $_POST["to_sector"];
        $from_sector = $_POST["from_sector"];

        if (!isset($product_name) || !isset($quantity) || !isset($employee)) {
            header('HTTP/1.0 400 Bad Request');
            exit("Missing parameter: Product name or quantity or employee");
        }
        // Must set ONE OF (to_warehouse and to_sector) or (from_warehouse and from_sector)
        if (!isset($to_sector) && !isset($from_sector)) {
                // Case if "to" is unset and at least one "from" is unset
            header('HTTP/1.0 400 Bad Request');
            exit("Missing parameter: Must set ONE OF (to_warehouse and to_sector) or (from_warehouse and from_sector)");
        }

        if ($to_sector == $from_sector) {
            header('HTTP/1.0 400 Bad Request');
            exit("Cannot move product to same sector!");
        }


        $mysqli = get_mysqli();
        $mysqli->begin_transaction();
        try {
            // If FROM values are set, check the quantity is valid
            if (isset($from_sector)) {
                $stmt = $mysqli->prepare("
                    SELECT COALESCE(SUM(quantity_added), 0) AS sum
                        FROM `inventorymgmt`.`inventorytransactions` t, `inventorymgmt`.`products` p, `inventorymgmt`.`sectors` s
                        WHERE t.product = p.id AND p.sector = s.id AND p.name = ? AND s.id = ?;            
                ");
                $stmt->bind_param('si', $product_name, $from_sector);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();
                $data = array();
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        array_push($data, $row);
                    }
                }
                $count = $data[0]["sum"];
                if ($count - $quantity < 0) {
                    throw new Exception('Negative resulting quantity');
                }
            }

            // We now know that there is enough product to move.
            // If TO exists, insert the new product into the DB if it doesn't exist
            $new_product_id = NULL;
            if (isset($to_sector)) {
                $stmt = $mysqli->prepare("
                    INSERT INTO `inventorymgmt`.`products` (name, sector) 
                    SELECT ?, ? FROM DUAL
                    WHERE NOT EXISTS (SELECT * FROM `inventorymgmt`.`products`
                        WHERE name = ? AND sector = ?);            
                ");
                $stmt->bind_param('sisi', $product_name, $to_sector, $product_name, $to_sector);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();
                if ($result === TRUE) {
                    $new_product_id = $mysqli->insert_id;
                } else {
                    $stmt = $mysqli->prepare("
                        SELECT `id` FROM `inventorymgmt`.`products`
                            WHERE name = ? AND sector = ?;            
                    ");
                    $stmt->bind_param('si', $product_name, $to_sector);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();
                    $data = array();
                    assert($result->num_rows > 0);
                    while($row = $result->fetch_assoc()) {
                        array_push($data, $row);
                    }
                    $new_product_id = $data[0]["id"];
                }
            }

            // Create inventory transaction for FROM if need be
            if (isset($from_sector)) {
                $stmt = $mysqli->prepare("
                    INSERT INTO `inventorymgmt`.`inventorytransactions`
                        (`product`, `employee`, `quantity_added`, `timestamp`) VALUES (
                            (SELECT `id` FROM `products` WHERE `name` = ? AND `sector` = ?), 
                            ?, ?, now());        
                ");
                $neg_quantity = -1 * $quantity;
                $stmt->bind_param('siii', $product_name, $from_sector, $employee, $neg_quantity);
                $stmt->execute();
                $stmt->close();
            }
            // Create inventory transaction for TO if need be
            if (isset($to_sector)) {
                $stmt = $mysqli->prepare("
                    INSERT INTO `inventorymgmt`.`inventorytransactions`
                        (`product`, `employee`, `quantity_added`, `timestamp`) VALUES (
                            ?, ?, ?, now());        
                ");
                $stmt->bind_param('iii', $new_product_id, $employee, $quantity);
                $stmt->execute();
                $stmt->close();
            }

            /* If code reaches this point without errors then commit the data in the database */
            $mysqli->commit();
            echo "Success";
            $mysqli->close();
        } catch (Exception $exception) {
            $mysqli->rollback();
            $mysqli->close();
            header('HTTP/1.0 500 Server Error');
            echo "Fatal error: " . $exception->getMessage();
        }
    } else {
        header('HTTP/1.0 403 Forbidden');
        echo "Invalid operation. Allowed operations: POST";
    }
?>
