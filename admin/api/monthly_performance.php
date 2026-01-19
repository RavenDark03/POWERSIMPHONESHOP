<?php
// admin/api/monthly_performance.php
header('Content-Type: application/json');
include '../../includes/connection.php';

// Prepare arrays for the last 12 months
$labels = [];
$volume = [];
$revenue = [];

// Loop for the last 12 months (current month inclusive, going backwards would mean reversing result, but better to go 11 months back to now)
// Actually, chart usually shows left-to-right (oldest to newest).
for ($i = 11; $i >= 0; $i--) {
    $date = new DateTime();
    $date->modify("-" . $i . " months");
    $month = $date->format('m');
    $year = $date->format('Y');
    $label = $date->format('M Y');
    
    $labels[] = $label;

    // 1. Calculate Monthly Loan Volume (Sum of loan_amount for items created in this month)
    $sql_volume = "SELECT SUM(loan_amount) as total_volume FROM items WHERE YEAR(created_at) = '$year' AND MONTH(created_at) = '$month'";
    $result_volume = $conn->query($sql_volume);
    $row_volume = $result_volume->fetch_assoc();
    $volume[] = $row_volume['total_volume'] ? floatval($row_volume['total_volume']) : 0;

    // 2. Calculate Monthly Revenue (Interest collected on Renewals + Redemptions)
    // Formula: loan_amount * (interest_rate / 100)
    // We filter by transaction_date matching the month
    $sql_revenue = "SELECT SUM(items.loan_amount * (items.interest_rate / 100)) as total_revenue 
                    FROM transactions 
                    JOIN items ON transactions.item_id = items.id 
                    WHERE transactions.transaction_type IN ('renewal', 'redemption') 
                    AND YEAR(transactions.transaction_date) = '$year' 
                    AND MONTH(transactions.transaction_date) = '$month'";
    
    $result_revenue = $conn->query($sql_revenue);
    $row_revenue = $result_revenue->fetch_assoc();
    $revenue[] = $row_revenue['total_revenue'] ? floatval($row_revenue['total_revenue']) : 0;
}

$data = [
    'labels' => $labels,
    'volume' => $volume,
    'revenue' => $revenue
];

echo json_encode($data);
$conn->close();
?>
