<?php

session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

require_once "dbase.php";

if (!isset($_SESSION["store_id"])) {
    die("Store account is not configured.");
}

$storeId = $_SESSION["store_id"];

$currentPage = basename($_SERVER['PHP_SELF']);


$selectedSaleId = intval($_GET['sale_id'] ?? 0);

$selectedSale = null;
$selectedItems = [];

if ($selectedSaleId > 0) {

    // Get transaction information
    $stmt = mysqli_prepare($conn, "
        SELECT
            sale_id,
            sale_date,
            total_amount,
            payment_method,
            amount_paid,
            change_amount
        FROM sales
        WHERE sale_id = ?
        AND store_id = ?
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $selectedSaleId,
        $storeId
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $selectedSale = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);


    // Get products included in the transaction
    $stmt = mysqli_prepare($conn, "
        SELECT
            p.product_name,
            si.quantity,
            si.price,
            (si.quantity * si.price) AS item_total
        FROM sale_item si
        INNER JOIN products p
            ON si.product_id = p.product_id
        INNER JOIN sales s
            ON si.sale_id = s.sale_id
        WHERE si.sale_id = ?
        AND s.store_id = ?
        ORDER BY si.sale_item_id ASC
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $selectedSaleId,
        $storeId
    );
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($item = mysqli_fetch_assoc($result)) {
        $selectedItems[] = $item;
    }

    mysqli_stmt_close($stmt);
}


// ==========================================
// GET SALES HISTORY
// ==========================================

$sql = "
    SELECT
        s.sale_id,
        s.sale_date,
        s.total_amount,
        s.payment_method,
        s.amount_paid,
        s.change_amount,
        COUNT(si.sale_item_id) AS item_count
    FROM sales s
    LEFT JOIN sale_item si
        ON s.sale_id = si.sale_id
    WHERE s.store_id = ?
    GROUP BY
        s.sale_id,
        s.sale_date,
        s.total_amount,
        s.payment_method,
        s.amount_paid,
        s.change_amount
    ORDER BY s.sale_id DESC
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $storeId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>StockIT — Sales History</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Custom CSS -->
    <link
        rel="stylesheet"
        href="style.css">

</head>


<body>

    <div class="app">

        <!-- SIDEBAR -->
        <aside class="sidebar">

            <div class="brand">
                <div class="brand-mark">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 7L12 3L20 7V17L12 21L4 17V7Z" stroke="white" stroke-width="1.6" stroke-linejoin="round"/><path d="M4 7L12 11L20 7" stroke="white" stroke-width="1.6" stroke-linejoin="round"/><path d="M12 11V21" stroke="white" stroke-width="1.6"/></svg>
                </div>
                <div class="brand-word">Stock<span>IT</span></div>
            </div>

            <div class="nav-section-label">MENU</div>

            <nav>
                <a class="nav-item <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="3" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="12" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="3" y="16" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="1.6"/></svg>
                    Dashboard
                </a>
                <a class="nav-item <?php echo $currentPage == 'pos.php' ? 'active' : ''; ?>" href="pos.php">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="2.5" y="6" width="19" height="13" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M2.5 10.5H21.5" stroke="currentColor" stroke-width="1.6"/><path d="M7 14H11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    Point of Sale
                </a>
                <a class="nav-item <?php echo $currentPage == 'products.php' ? 'active' : ''; ?>" href="products.php">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M3 7.5L12 3L21 7.5V16.5L12 21L3 16.5V7.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M3 7.5L12 12M12 12L21 7.5M12 12V21" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    Products
                </a>
                <a class="nav-item <?php echo $currentPage == 'sales_history.php' ? 'active' : ''; ?>" href="sales_history.php">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 4V15C4 17.2091 5.79086 19 8 19H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M8 15L12 10L15 13L20 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Sales History
                </a>
                <a class="nav-item <?php echo $currentPage == 'salesReport.php' ? 'active' : ''; ?>" href="salesReport.php">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 3H14L19 8V19C19 20.1046 18.1046 21 17 21H6C4.89543 21 4 20.1046 4 19V5C4 3.89543 4.89543 3 6 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 12H15M8 16H15M8 8.5H10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    Sales Report
                </a>
            </nav>
        </aside>


        <!-- MAIN CONTENT -->
        <main class="main">


            <!-- PAGE HEADER -->
            <div class="topbar">

                <div>

                    <h1 class="page-title">
                        Sales History
                    </h1>

                    <p class="page-sub">
                        View your completed sales transactions.
                    </p>

                </div>

            </div>

            <div class="sales-history-toolbar">

                <div class="sales-history-search">
                    <input
                        type="text"
                        id="transactionSearch"
                        class="form-control"
                        placeholder="Search transaction...">
                </div>

                <div class="sales-history-filter">
                    <select id="paymentFilter" class="form-select">

                        <option value="all">All Payments</option>
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash</option>

                    </select>
                </div>

            </div>



            <!-- SALES TABLE -->
            <div class="product-table-card">

                <div class="table-header">

                    <h2>
                        Transactions
                    </h2>

                    <span>
                        <?php echo mysqli_num_rows($result); ?>
                        transactions
                    </span>

                </div>
                


                <div class="table-responsive sales-table-wrapper">

                    <table class="table product-table">

                        <thead>

                            <tr>

                                <th>
                                    TRANSACTION
                                </th>

                                <th>
                                    DATE & TIME
                                </th>

                                <th>
                                    ITEMS
                                </th>

                                <th>
                                    PAYMENT
                                </th>

                                <th>
                                    TOTAL
                                </th>

                                <th>
                                    ACTION
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (mysqli_num_rows($result) > 0) { ?>

                                <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                                    <tr class="sale-row" data-sale-id="
                                        <?php echo $row['sale_id']; ?>" data-payment="<?php echo htmlspecialchars($row['payment_method']); ?>">

                                        <td>

                                            <strong>
                                                #<?php
                                                echo $row["sale_id"];
                                                ?>
                                            </strong>

                                        </td>


                                        <td>

                                            <?php
                                            echo date(
                                                "M d, Y h:i A",
                                                strtotime($row["sale_date"])
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <?php
                                            echo $row["item_count"];
                                            ?>

                                            <?php
                                            echo ($row["item_count"] == 1)
                                                ? " item"
                                                : " items";
                                            ?>

                                        </td>


                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                $row["payment_method"]
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <strong>

                                                ₱<?php
                                                echo number_format(
                                                    $row["total_amount"],
                                                    2
                                                );
                                                ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <a href="sales_history.php?sale_id=<?php echo $row['sale_id']; ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>

                                        </td>

                                    </tr>

                                <?php } ?>

                            <?php }?>

                                <tr id="noTransactionsRow" style="display: none;">
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No transactions found.
                                    </td>
                                </tr>

                        </tbody>

                    </table>

                </div>

            </div>


        </main>

    </div>

    <?php if ($selectedSale): ?>

    <div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-lg">

            <div class="modal-content transaction-modal">

                <!-- HEADER -->
                <div class="transaction-modal-header">

                    <div>
                        <h4>Transaction #<?php echo $selectedSale['sale_id']; ?></h4>

                        <p>
                            <?php echo date("M d, Y • h:i A", strtotime($selectedSale['sale_date'])); ?>
                        </p>
                    </div>

                    <a href="sales_history.php"
                    class="transaction-close-btn"
                    aria-label="Close">
                        &times;
                    </a>

                </div>


                <!-- PRODUCTS -->
                <div class="transaction-modal-body">

                    <div class="transaction-section-title">
                        Items Purchased
                    </div>

                    <div class="transaction-items">

                        <?php foreach ($selectedItems as $item): ?>

                            <div class="transaction-item">

                                <div class="transaction-item-info">

                                    <strong>
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </strong>

                                    <span>
                                        <?php echo $item['quantity']; ?>
                                        × ₱<?php echo number_format($item['price'], 2); ?>
                                    </span>

                                </div>

                                <div class="transaction-item-total">

                                    ₱<?php echo number_format($item['item_total'], 2); ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>


                    <!-- SUMMARY -->
                    <div class="transaction-summary">

                        <div class="transaction-summary-row">
                            <span>Total Due</span>

                            <strong>
                                ₱<?php echo number_format($selectedSale['total_amount'], 2); ?>
                            </strong>
                        </div>


                        <div class="transaction-summary-row">
                            <span>Payment Method</span>

                            <span class="payment-badge">
                                <?php echo htmlspecialchars($selectedSale['payment_method']); ?>
                            </span>
                        </div>


                        <div class="transaction-summary-row">
                            <span>Amount Paid</span>

                            <strong>
                                ₱<?php echo number_format($selectedSale['amount_paid'], 2); ?>
                            </strong>
                        </div>


                        <div class="transaction-summary-row">
                            <span>Change</span>

                            <strong>
                                ₱<?php echo number_format($selectedSale['change_amount'], 2); ?>
                            </strong>
                        </div>

                    </div>

                </div>


                <!-- FOOTER -->
                <div class="transaction-modal-footer">

                    <a href="sales_history.php"
                    class="btn btn-secondary">
                        Close
                    </a>

                </div>

            </div>

        </div>

    </div>

    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script>

    document.addEventListener("DOMContentLoaded", function () {

        const transactionModalElement =
            document.getElementById("transactionModal");

        if (transactionModalElement) {

            const transactionModal =
                new bootstrap.Modal(transactionModalElement);

            transactionModal.show();

        }

    });

    </script>

    <script>

    document.addEventListener("DOMContentLoaded", function () {

        const searchInput =
            document.getElementById("transactionSearch");

        const paymentFilter =
            document.getElementById("paymentFilter");

        const saleRows =
            document.querySelectorAll(".sale-row");


        function filterSales() {

            const searchText =
                searchInput.value.toLowerCase().trim();

            const selectedPayment =
                paymentFilter.value;

            let visibleRows = 0;


            saleRows.forEach(function (row) {

                const transactionId =
                    row.dataset.saleId.toLowerCase();

                const paymentMethod =
                    row.dataset.payment;


                const matchesSearch =
                    transactionId.includes(searchText);


                const matchesPayment =
                    selectedPayment === "all" ||
                    paymentMethod === selectedPayment;


                if (matchesSearch && matchesPayment) {

                    row.style.display = "";
                    visibleRows++;

                } else {

                    row.style.display = "none";

                }

            });


            const noTransactionsRow =
                document.getElementById("noTransactionsRow");

            if (visibleRows === 0) {

                noTransactionsRow.style.display = "";

            } else {

                noTransactionsRow.style.display = "none";

            }

        }


        // Search transaction number
        searchInput.addEventListener("input", function () {

            filterSales();

        });


        // Filter payment method
        paymentFilter.addEventListener("change", function () {

            filterSales();

        });

    });

    </script>


</body>
</html>