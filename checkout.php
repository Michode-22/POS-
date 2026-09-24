<?php

session_start();

header("Content-Type: application/json");

if (!isset($_SESSION["user"])) {

    echo json_encode([
        "success" => false,
        "message" => "You are not logged in."
    ]);

    exit();
}

require_once "dbase.php";

if (!isset($_SESSION["store_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Store account is not configured."
    ]);
    exit();
}

$storeId = $_SESSION["store_id"];


// ==========================================
// CHECK REQUEST METHOD
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);

    exit();
}


// ==========================================
// GET DATA FROM POS
// ==========================================

$cart = json_decode($_POST["cart"] ?? "", true);

$payment = floatval($_POST["payment"] ?? 0);
$paymentMethod = $_POST["payment_method"] ?? "Cash";


// ==========================================
// CHECK CART
// ==========================================

if (!is_array($cart) || empty($cart)) {

    echo json_encode([
        "success" => false,
        "message" => "Cart is empty."
    ]);

    exit();
}


// ==========================================
// START MYSQL TRANSACTION
// ==========================================

mysqli_begin_transaction($conn);

try {

    $total = 0;

    $products = [];


    // ==========================================
    // CHECK EVERY PRODUCT
    // ==========================================

    foreach ($cart as $item) {

        $productId = intval($item["id"]);
        $quantity = intval($item["quantity"]);


        if ($quantity < 1) {

            throw new Exception("Invalid quantity.");
        }


        // Lock this product row while checkout is happening
        $stmt = mysqli_prepare(
            $conn,
            "SELECT product_name, price, cost, stock_quantity
            FROM products
            WHERE product_id = ?
            AND store_id = ?
            FOR UPDATE"
        );

        if (!$stmt) {
            throw new Exception(
                "Failed to prepare product lookup: " .
                mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $productId,
            $storeId
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(
                "Failed to check product: " .
                mysqli_stmt_error($stmt)
            );
        }

        $result = mysqli_stmt_get_result($stmt);

        if (!$result) {
            throw new Exception(
                "Failed to get product result: " .
                mysqli_error($conn)
            );
        }

        $product = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);


        // Product doesn't exist
        if (!$product) {

            throw new Exception(
                "Product ID " . $productId . " was not found."
            );
        }


        // Not enough stock
        if ($quantity > $product["stock_quantity"]) {

            throw new Exception(
                $product["product_name"] .
                " does not have enough stock."
            );
        }


        // Calculate total using database price
        $itemTotal =
            floatval($product["price"]) * $quantity;

        $total += $itemTotal;


        // Store product information for later
        $products[] = [
            "id"=>$productId,
            "name"=>$product["product_name"],
            "price"=>floatval($product["price"]),
            "cost"=>floatval($product["cost"]),
            "quantity"=>$quantity
        ];

    }


    // ==========================================
    // CHECK PAYMENT
    // ==========================================

    if ($payment < $total) {

        throw new Exception(
            "Payment is not enough."
        );
    }


    // ==========================================
    // INSERT INTO SALES
    // ==========================================

    $change = $payment - $total;

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO sales
        (total_amount, store_id, payment_method, amount_paid, change_amount)
        VALUES (?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "disdd",
        $total,
        $storeId,
        $paymentMethod,
        $payment,
        $change
    );

    mysqli_stmt_execute($stmt);

    $saleId = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);


    // ==========================================
    // INSERT SALE ITEMS + UPDATE STOCK
    // ==========================================

    foreach ($products as $product) {


        // INSERT SALE ITEM
        $stmt = mysqli_prepare($conn,
            "INSERT INTO sale_item
            (sale_id, product_id, quantity, price, cost)
            VALUES (?, ?, ?, ?, ?)");

        mysqli_stmt_bind_param($stmt, "iiidd",
            $saleId,
            $product["id"],
            $product["quantity"],
            $product["price"],
            $product["cost"]
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);


        // DECREASE STOCK
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE products
             SET stock_quantity = stock_quantity - ?
             WHERE product_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $product["quantity"],
            $product["id"]
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);

    }


    // ==========================================
    // EVERYTHING SUCCESSFUL
    // ==========================================

    mysqli_commit($conn);


    $change = $payment - $total;


    echo json_encode([
    "success" => true,
    "sale_id" => $saleId,
    "total" => number_format($total, 2, ".", ""),
    "payment" => number_format($payment, 2, ".", ""),
    "change" => number_format($change, 2, ".", ""),
    ]);

}
catch (Exception $e) {

    // Undo every database change
    mysqli_rollback($conn);


    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

}

?>