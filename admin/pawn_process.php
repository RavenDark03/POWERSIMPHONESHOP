<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

// DEBUG: Enable Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];

    if ($action == 'new_pawn') {
        $customer_id = $_POST['customer_id'];
        $item_description = $_POST['item_description'];
        $category = $_POST['category'];
        
        $item_type = $_POST['item_type'];
        if ($item_type === 'Others' && !empty($_POST['other_item_type'])) {
            $item_type = $_POST['other_item_type'];
        }
        
        // Gadget Fields
        $brand = isset($_POST['brand']) ? $_POST['brand'] : null;
        if ($brand === 'Others' && !empty($_POST['other_brand'])) {
            $brand = $_POST['other_brand'];
        }

        $model = isset($_POST['model']) ? $_POST['model'] : null;
        if ($model === 'Others' && !empty($_POST['other_model'])) {
            $model = $_POST['other_model'];
        }

        $serial_number = isset($_POST['serial_number']) ? $_POST['serial_number'] : null;
        
        // Handle Accessories specific logic
        if (isset($_POST['accessories']) && is_array($_POST['accessories'])) {
            $accessories = implode(", ", $_POST['accessories']);
        } else {
            $accessories = isset($_POST['accessories']) ? $_POST['accessories'] : null;
        }

        // Jewelry Fields
        $weight_grams = isset($_POST['weight_grams']) && $_POST['weight_grams'] !== '' ? $_POST['weight_grams'] : null;
        $purity = isset($_POST['purity']) ? $_POST['purity'] : null;
        if ($purity === 'Others' && !empty($_POST['other_purity'])) {
            $purity = $_POST['other_purity'];
        }
        $gemstones = isset($_POST['gemstones']) ? $_POST['gemstones'] : null;
        
        $item_condition = $_POST['item_condition'];
        $loan_amount = $_POST['loan_amount'];
        $interest_rate = $_POST['interest_rate'];
        $due_date = $_POST['due_date'];

        $sql = "INSERT INTO items (customer_id, item_description, category, item_type, brand, model, serial_number, accessories, weight_grams, purity, gemstones, item_condition, loan_amount, interest_rate, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssssdsssdds", $customer_id, $item_description, $category, $item_type, $brand, $model, $serial_number, $accessories, $weight_grams, $purity, $gemstones, $item_condition, $loan_amount, $interest_rate, $due_date);

        if ($stmt->execute()) {
            $item_id = $stmt->insert_id;
            $sql = "INSERT INTO transactions (item_id, transaction_type) VALUES (?, 'pawn')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $item_id);
            $stmt->execute();

            header("Location: pawning.php");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        $stmt->close();
    } elseif ($action == 'renew_pawn') {
        $id = $_POST['id'];
        $new_due_date = $_POST['new_due_date'];

        $sql = "UPDATE items SET due_date = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_due_date, $id);

        if ($stmt->execute()) {
            $sql = "INSERT INTO transactions (item_id, transaction_type) VALUES (?, 'renewal')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();

            header("Location: pawning.php");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        $stmt->close();
    } elseif ($action == 'redeem_pawn') {
        $id = $_POST['id'];

        $sql = "UPDATE items SET status = 'redeemed' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $sql = "INSERT INTO transactions (item_id, transaction_type) VALUES (?, 'redemption')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();

            header("Location: pawning.php");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>