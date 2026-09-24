<?php

session_start();

if (!isset($_SESSION["user"])) {
    exit("Unauthorized");
}

require_once "dbase.php";

if (!isset($_SESSION["store_id"])) {
    exit("Store account is not configured.");
}

$storeId = $_SESSION["store_id"];

$period = $_GET["period"] ?? "today";
$customDate = $_GET["custom_date"] ?? "";

$allowedPeriods = [
    "today",
    "this_week",
    "last_week",
    "this_month",
    "last_month",
    "custom"
];

if (!in_array($period, $allowedPeriods, true)) {
    $period = "today";
}


/*
|--------------------------------------------------------------------------
| Date condition
|--------------------------------------------------------------------------
*/

if ($period === "today") {

    $dateCondition = "
        s.sale_date >= CURDATE()
        AND s.sale_date < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ";

} elseif ($period === "this_week") {

    $dateCondition = "
        s.sale_date >= DATE_SUB(
            CURDATE(),
            INTERVAL WEEKDAY(CURDATE()) DAY
        )
        AND s.sale_date < DATE_ADD(
            DATE_SUB(
                CURDATE(),
                INTERVAL WEEKDAY(CURDATE()) DAY
            ),
            INTERVAL 7 DAY
        )
    ";

} elseif ($period === "last_week") {

    $dateCondition = "
        s.sale_date >= DATE_SUB(
            CURDATE(),
            INTERVAL (WEEKDAY(CURDATE()) + 7) DAY
        )
        AND s.sale_date < DATE_SUB(
            CURDATE(),
            INTERVAL WEEKDAY(CURDATE()) DAY
        )
    ";

} elseif ($period === "this_month") {

    $dateCondition = "
        s.sale_date >= DATE_FORMAT(
            CURDATE(),
            '%Y-%m-01'
        )
        AND s.sale_date < DATE_ADD(
            DATE_FORMAT(
                CURDATE(),
                '%Y-%m-01'
            ),
            INTERVAL 1 MONTH
        )
    ";

} elseif ($period === "last_month") {

    $dateCondition = "
        s.sale_date >= DATE_FORMAT(
            CURDATE() - INTERVAL 1 MONTH,
            '%Y-%m-01'
        )
        AND s.sale_date < DATE_FORMAT(
            CURDATE(),
            '%Y-%m-01'
        )
    ";

} elseif ($period === "custom") {

    $dateObject = DateTime::createFromFormat(
        '!Y-m-d',
        $customDate
    );

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !== $customDate
    ) {
        exit("Invalid custom date.");
    }

    $safeCustomDate = mysqli_real_escape_string(
        $conn,
        $customDate
    );

    $dateCondition = "
        s.sale_date >= '$safeCustomDate 00:00:00'
        AND s.sale_date < DATE_ADD(
            '$safeCustomDate 00:00:00',
            INTERVAL 1 DAY
        )
    ";
}


/*
|--------------------------------------------------------------------------
| Get sales
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        s.sale_id,
        s.sale_date,
        s.payment_method,
        si.product_id,
        p.product_name,
        si.quantity,
        si.price,
        si.cost,
        (si.price * si.quantity) AS sales_amount,
        (si.cost * si.quantity) AS cost_amount,
        ((si.price - si.cost) * si.quantity) AS gross_profit
    FROM sales s
    INNER JOIN sale_item si
        ON s.sale_id = si.sale_id
    INNER JOIN products p
        ON si.product_id = p.product_id
    WHERE $dateCondition
    AND s.store_id = ?
    ORDER BY s.sale_date ASC, s.sale_id ASC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    exit("Failed to prepare sales query.");
}

mysqli_stmt_bind_param($stmt, "i", $storeId);

if (!mysqli_stmt_execute($stmt)) {
    exit("Failed to retrieve sales data.");
}

$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    exit("Failed to retrieve sales data.");
}


/*
|--------------------------------------------------------------------------
| Download CSV
|--------------------------------------------------------------------------
*/

header("Content-Type: text/csv; charset=UTF-8");
header(
    "Content-Disposition: attachment; filename=stockit_sales_report.csv"
);
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");


/*
|--------------------------------------------------------------------------
| CSV Header
|--------------------------------------------------------------------------
*/

fputcsv($output, [
    "Transaction ID",
    "Date",
    "Payment Method",
    "Product",
    "Quantity",
    "Selling Price",
    "Cost",
    "Sales Amount",
    "Cost Amount",
    "Gross Profit"
]);


/*
|--------------------------------------------------------------------------
| CSV Data
|--------------------------------------------------------------------------
*/

while ($row = mysqli_fetch_assoc($result)) {

    fputcsv($output, [
        $row["sale_id"],
        $row["sale_date"],
        $row["payment_method"],
        $row["product_name"],
        $row["quantity"],
        number_format($row["price"], 2, ".", ""),
        number_format($row["cost"], 2, ".", ""),
        number_format($row["sales_amount"], 2, ".", ""),
        number_format($row["cost_amount"], 2, ".", ""),
        number_format($row["gross_profit"], 2, ".", "")
    ]);

}

fclose($output);
exit();

?>