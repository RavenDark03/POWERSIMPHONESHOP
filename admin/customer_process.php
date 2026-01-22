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
    $action = $_POST['action'] ?? 'add'; // Default to add if not set

    // Ensure customers table has `username` column (best-effort)
    try {
        $colCheck = $conn->query("SHOW COLUMNS FROM customers LIKE 'username'");
        if ($colCheck && $colCheck->num_rows == 0) {
            $conn->query("ALTER TABLE customers ADD COLUMN username VARCHAR(191) DEFAULT '' AFTER email");
        }
    } catch (Exception $e) {
        // ignore: if ALTER fails due to permissions, we'll surface uniqueness checks only
    }

    if ($action == 'add') {
        // Generate Customer Code
        $year = date('Y');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $customer_code = "CUST-$year-$random";

        // Personal Info
        $first_name = $_POST['first_name'];
        $middle_name = $_POST['middle_name'];
        $last_name = $_POST['last_name'];
        $birthdate = $_POST['birthdate'] ?? '';
        $contact_number = $_POST['contact_number'];

        // Age validation: must be at least 18
        $birth_ts = strtotime($birthdate);
        $cutoff_ts = strtotime('-18 years');
        if (!$birth_ts || $birth_ts > $cutoff_ts) {
            echo "Error: Customer must be at least 18 years old.";
            exit();
        }

        // Present Address
        $present_house_num = $_POST['present_house_num'];
        $present_street = $_POST['present_street'];
        $present_subdivision = $_POST['present_subdivision'];
        $present_barangay = $_POST['present_barangay_text'];
        $present_city = $_POST['present_city_text'];
        $present_province = $_POST['present_province_text'];
        $present_zip = $_POST['present_zip'];

        // Permanent Address
        $permanent_house_num = $_POST['permanent_house_num'];
        $permanent_street = $_POST['permanent_street'];
        $permanent_subdivision = $_POST['permanent_subdivision'];
        $permanent_barangay = $_POST['permanent_barangay_text'];
        $permanent_city = $_POST['permanent_city_text'];
        $permanent_province = $_POST['permanent_province_text'];
        $permanent_zip = $_POST['permanent_zip'];

        // ID Info
        $id_type = $_POST['id_type'];
        $other_id_type = ($id_type == 'Others') ? $_POST['other_id_type'] : NULL;

        // Login fields for hybrid flow
        $email = trim($_POST['email'] ?? '');
        // allow explicit username; default to email for walk-ins
        $username = trim($_POST['username'] ?? '');
        if ($username === '') $username = $email;
        $username = strtolower($username);
        // generate temporary password for walk-in
        function generateTempPassword($len = 8) {
            try {
                return substr(bin2hex(random_bytes(4)), 0, $len);
            } catch (Exception $e) {
                return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $len);
            }
        }
        $temp_password = generateTempPassword(8);
        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
        $registration_source = 'walk_in';
        
        // Handle File Upload
        $id_image_front_path = "";
        $id_image_back_path = "";
        $target_dir = "../uploads/ids/";

        // Function to handle upload
        function uploadID($fileInputName, $customerCode, $suffix, $targetDir) {
            if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] == 0) {
                if (!file_exists($targetDir)) {
                    @mkdir($targetDir, 0777, true);
                }
                $ext = pathinfo($_FILES[$fileInputName]["name"], PATHINFO_EXTENSION);
                $newFilename = $customerCode . "_" . $suffix . "." . $ext;
                $targetFile = $targetDir . $newFilename;
                if (move_uploaded_file($_FILES[$fileInputName]["tmp_name"], $targetFile)) {
                    return "uploads/ids/" . $newFilename;
                }
            }
            return "";
        }

        $id_image_front_path = uploadID('id_image_front', $customer_code, 'FRONT', $target_dir);
        $id_image_back_path = uploadID('id_image_back', $customer_code, 'BACK', $target_dir);

        // determine verification: if IDs uploaded mark verified
        $is_verified = (!empty($id_image_front_path) || !empty($id_image_back_path)) ? 1 : 0;



        // Check username uniqueness for add
        if ($username !== '') {
            $checkSql = "SELECT id FROM customers WHERE username = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("s", $username);
            $checkStmt->execute();
            $checkRes = $checkStmt->get_result();
            if ($checkRes && $checkRes->num_rows > 0) {
                echo "Error: Username already in use. Choose another username.";
                exit();
            }
            $checkStmt->close();
        }

        $sql = "INSERT INTO customers (
            customer_code, first_name, middle_name, last_name, birthdate, contact_number,
            present_house_num, present_street, present_subdivision, present_barangay, present_city, present_province, present_zip,
            permanent_house_num, permanent_street, permanent_subdivision, permanent_barangay, permanent_city, permanent_province, permanent_zip,
            id_type, other_id_type, id_image_front_path, id_image_back_path,
            email, username, password, registration_source, is_verified, account_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $account_status = 'approved'; // Admin-created customers are auto-approved
        $bind_params = [
            $customer_code, $first_name, $middle_name, $last_name, $birthdate, $contact_number,
            $present_house_num, $present_street, $present_subdivision, $present_barangay, $present_city, $present_province, $present_zip,
            $permanent_house_num, $permanent_street, $permanent_subdivision, $permanent_barangay, $permanent_city, $permanent_province, $permanent_zip,
            $id_type, $other_id_type, $id_image_front_path, $id_image_back_path,
            $email, $username, $hashed_password, $registration_source, $is_verified, $account_status
        ];
        // 28 strings + integer (is_verified) + string (account_status)
        $bind_types = str_repeat('s', count($bind_params) - 2) . 'is';
        $stmt->bind_param($bind_types, ...$bind_params);

        if ($stmt->execute()) {
            // send credentials email to customer for walk-in
            if (!empty($email) && file_exists('../includes/send_email.php')) {
                require_once '../includes/send_email.php';
                $subject = "Your account credentials - Powersim";
                $message = "Hello $first_name $last_name,\n\nA customer account has been created for you.\nUsername: $username\nTemporary Password: $temp_password\n\nPlease login and change your password.\n\nRegards,\nPowersim";
                @sendEmail($email, $subject, $message);
            }

            header("Location: customers.php");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        $stmt->close();
    } elseif ($action == 'edit') {
        $id = $_POST['id'];
        
        // Personal Info
        $first_name = $_POST['first_name'];
        $middle_name = $_POST['middle_name'];
        $last_name = $_POST['last_name'];
        $birthdate = $_POST['birthdate'] ?? '';
        $contact_number = $_POST['contact_number'];

        // username (editable)
        $username = trim($_POST['username'] ?? '');
        $username = strtolower($username);

        // Present Address
        $present_house_num = $_POST['present_house_num'];
        $present_street = $_POST['present_street'];
        $present_subdivision = $_POST['present_subdivision'];
        $present_barangay = $_POST['present_barangay_text'];
        $present_city = $_POST['present_city_text'];
        $present_province = $_POST['present_province_text'];
        $present_zip = $_POST['present_zip'];

        // Permanent Address
        $permanent_house_num = $_POST['permanent_house_num'];
        $permanent_street = $_POST['permanent_street'];
        $permanent_subdivision = $_POST['permanent_subdivision'];
        $permanent_barangay = $_POST['permanent_barangay_text'];
        $permanent_city = $_POST['permanent_city_text'];
        $permanent_province = $_POST['permanent_province_text'];
        $permanent_zip = $_POST['permanent_zip'];

        // ID Info
        $id_type = $_POST['id_type'];
        $other_id_type = ($id_type == 'Others') ? $_POST['other_id_type'] : NULL;
        
        // Handle File Upload
        $sql_updates = "";
        // Validate uniqueness of username for edit (if provided)
        if ($username !== '') {
            $checkSql = "SELECT id FROM customers WHERE username = ? AND id != ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("si", $username, $id);
            $checkStmt->execute();
            $checkRes = $checkStmt->get_result();
            if ($checkRes && $checkRes->num_rows > 0) {
                echo "Error: Username already in use by another customer.";
                exit();
            }
            $checkStmt->close();
        }

        $params = [
            $first_name, $middle_name, $last_name, $contact_number, $username,
            $present_house_num, $present_street, $present_subdivision, $present_barangay, $present_city, $present_province, $present_zip,
            $permanent_house_num, $permanent_street, $permanent_subdivision, $permanent_barangay, $permanent_city, $permanent_province, $permanent_zip,
            $id_type, $other_id_type
        ];
        $types = "sssssssssssssssssssss";

        if (!empty($birthdate)) {
            $birth_ts = strtotime($birthdate);
            $cutoff_ts = strtotime('-18 years');
            if (!$birth_ts || $birth_ts > $cutoff_ts) {
                echo "Error: Customer must be at least 18 years old.";
                exit();
            }
            $sql_updates .= ", birthdate = ?";
            $params[] = $birthdate;
            $types .= "s";
        }

        $code_sql = "SELECT customer_code FROM customers WHERE id = ?";
        $code_stmt = $conn->prepare($code_sql);
        $code_stmt->bind_param("i", $id);
        $code_stmt->execute();
        $code_res = $code_stmt->get_result();
        $code_row = $code_res->fetch_assoc();
        $customer_code = $code_row['customer_code'];
        $target_dir = "../uploads/ids/";

        // Function to handle upload for Edit (duplication logic simplified for inline) 
        function processEditUpload($fileInput, $customerCode, $suffix, $targetDir, $columnName, &$sql_updates, &$params, &$types) {
             if (isset($_FILES[$fileInput]) && $_FILES[$fileInput]['error'] == 0) {
                if (!file_exists($targetDir)) { @mkdir($targetDir, 0777, true); }
                $ext = pathinfo($_FILES[$fileInput]["name"], PATHINFO_EXTENSION);
                $newFilename = $customerCode . "_" . $suffix . "." . $ext;
                $targetFile = $targetDir . $newFilename;
                
                if (move_uploaded_file($_FILES[$fileInput]["tmp_name"], $targetFile)) {
                    $path = "uploads/ids/" . $newFilename;
                    $sql_updates .= ", $columnName = ?";
                    $params[] = $path;
                    $types .= "s";
                    // mark as verified when an ID image is uploaded (only add once)
                    if (strpos($sql_updates, 'is_verified') === false) {
                        $sql_updates .= ", is_verified = 1";
                    }
                }
             }
        }

        processEditUpload('id_image_front', $customer_code, 'FRONT', $target_dir, 'id_image_front_path', $sql_updates, $params, $types);
        processEditUpload('id_image_back', $customer_code, 'BACK', $target_dir, 'id_image_back_path', $sql_updates, $params, $types);

        
        $params[] = $id;
        $types .= "i";

        $sql = "UPDATE customers SET 
            first_name=?, middle_name=?, last_name=?, contact_number=?, username=?,
            present_house_num=?, present_street=?, present_subdivision=?, present_barangay=?, present_city=?, present_province=?, present_zip=?,
            permanent_house_num=?, permanent_street=?, permanent_subdivision=?, permanent_barangay=?, permanent_city=?, permanent_province=?, permanent_zip=?,
            id_type=?, other_id_type=?" . $sql_updates . " WHERE id=?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            header("Location: customers.php");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>