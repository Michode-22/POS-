<?php

session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

require_once "dbase.php";


// ==========================================
// GET SALE ID
// ==========================================

$saleId = intval($_GET["sale_id"] ?? 0);


// ==========================================
// CHECK SALE ID
// ==========================================

if ($saleId <= 0) {
    die("Invalid transaction.");
}


// ==========================================
// GET SALE INFORMATION
// ==========================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT sale_id, sale_date, total_amount
     FROM sales
     WHERE sale_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $saleId
);

mysqli_stmt_execute($stmt);

$saleResult = mysqli_stmt_get_result($stmt);

$sale = mysqli_fetch_assoc($saleResult);

mysqli_stmt_close($stmt);


// ==========================================
// CHECK IF SALE EXISTS
// ==========================================

if (!$sale) {
    die("Transaction not found.");
}


// ==========================================
// GET SALE ITEMS
// ==========================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        sale_item.product_id,
        sale_item.quantity,
        sale_item.price,
        products.product_name
     FROM sale_item
     INNER JOIN products
        ON sale_item.product_id = products.product_id
     WHERE sale_item.sale_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $saleId
);

mysqli_stmt_execute($stmt);

$itemsResult = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        StockIT — Transaction #<?php echo $sale["sale_id"]; ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="style.css">

</head>


<body>

    <div class="main">

        <div class="topbar">

            <div>

                <h1 class="page-title">
                    Transaction #<?php echo $sale["sale_id"]; ?>
                </h1>

                <p class="page-sub">
                    Transaction details
                </p>

            </div>

        </div>


        <div class="pos-card">

            <div class="pos-card-header">

                <h2>
                    Transaction Information
                </h2>

                <p>
                    Completed sale details.
                </p>

            </div>


            <div class="p-3">

                <div class="mb-3">

                    <strong>
                        Transaction No.
                    </strong>

                    <div>
                        #<?php echo $sale["sale_id"]; ?>
                    </div>

                </div>


                <div class="mb-4">

                    <strong>
                        Date and Time
                    </strong>

                    <div>
                        <?php echo $sale["sale_date"]; ?>
                    </div>

                </div>


                <div class="table-responsive">

                    <table class="table align-middle">

                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Quantity
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Subtotal
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php while ($item = mysqli_fetch_assoc($itemsResult)) { ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $item["product_name"]
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo $item["quantity"];
                                        ?>
                                    </td>

                                    <td>
                                        ₱<?php
                                        echo number_format(
                                            $item["price"],
                                            2
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        ₱<?php
                                        echo number_format(
                                            $item["price"] *
                                            $item["quantity"],
                                            2
                                        );
                                        ?>

                                    </td>

                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                </div>


                <div class="d-flex justify-content-end mt-3">

                    <div>

                        <span>
                            Total
                        </span>

                        <strong class="ms-4">

                            ₱<?php
                            echo number_format(
                                $sale["total_amount"],
                                2
                            );
                            ?>

                        </strong>

                    </div>

                </div>


                <div class="mt-4">

                    <a
                        href="pos.php"
                        class="btn btn-primary">

                        Back to Point of Sale

                    </a>

                </div>

            </div>

        </div>

    </div>

</body>

</html>

<?php

mysqli_stmt_close($stmt);

?>