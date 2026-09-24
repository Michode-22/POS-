<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

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


// ==========================================
// TODAY'S SALES
// ==========================================

$sqlTodaySales = "
    SELECT COALESCE(SUM(total_amount), 0) AS today_sales
    FROM sales
    WHERE DATE(sale_date) = CURDATE()
    AND store_id = ?
";

$stmtTodaySales = mysqli_prepare($conn, $sqlTodaySales);
mysqli_stmt_bind_param($stmtTodaySales, "i", $storeId);
mysqli_stmt_execute($stmtTodaySales);

$resultTodaySales = mysqli_stmt_get_result($stmtTodaySales);

$todaySalesData = mysqli_fetch_assoc($resultTodaySales);

$todaySales = $todaySalesData["today_sales"];

mysqli_stmt_close($stmtTodaySales);


// ==========================================
// TODAY'S TRANSACTIONS
// ==========================================

$sqlTodayTransactions = "
    SELECT COUNT(*) AS today_transactions
    FROM sales
    WHERE DATE(sale_date) = CURDATE()
    AND store_id = ?
";

$stmtTodayTransactions = mysqli_prepare($conn, $sqlTodayTransactions);
mysqli_stmt_bind_param($stmtTodayTransactions, "i", $storeId);
mysqli_stmt_execute($stmtTodayTransactions);

$resultTodayTransactions = mysqli_stmt_get_result($stmtTodayTransactions);

$todayTransactionsData = mysqli_fetch_assoc($resultTodayTransactions);

$todayTransactions = $todayTransactionsData["today_transactions"];

mysqli_stmt_close($stmtTodayTransactions);


// ==========================================
// TOTAL PRODUCTS
// ==========================================

$sqlTotalProducts = "
    SELECT COUNT(*) AS total_products
    FROM products
    WHERE store_id = ?
";

$stmtTotalProducts = mysqli_prepare($conn, $sqlTotalProducts);
mysqli_stmt_bind_param($stmtTotalProducts, "i", $storeId);
mysqli_stmt_execute($stmtTotalProducts);

$resultTotalProducts = mysqli_stmt_get_result($stmtTotalProducts);

$totalProductsData = mysqli_fetch_assoc($resultTotalProducts);

$totalProducts = $totalProductsData["total_products"];

mysqli_stmt_close($stmtTotalProducts);


// ==========================================
// LOW STOCK PRODUCTS
// ==========================================

$sqlLowStock = "
    SELECT COUNT(*) AS low_stock
    FROM products
    WHERE stock_quantity <= reorder_level
    AND store_id = ?
";

$stmtLowStock = mysqli_prepare($conn, $sqlLowStock);
mysqli_stmt_bind_param($stmtLowStock, "i", $storeId);
mysqli_stmt_execute($stmtLowStock);

$resultLowStock = mysqli_stmt_get_result($stmtLowStock);

$lowStockData = mysqli_fetch_assoc($resultLowStock);

$lowStock = $lowStockData["low_stock"];

mysqli_stmt_close($stmtLowStock);


// ==========================================
// SALES OVERVIEW - THIS WEEK
// ==========================================

$sqlSalesOverview = "
    SELECT
        DATE(sale_date) AS sale_day,
        COALESCE(SUM(total_amount), 0) AS daily_sales
    FROM sales
    WHERE sale_date >= DATE_SUB(
        CURDATE(),
        INTERVAL WEEKDAY(CURDATE()) DAY
    )
    AND sale_date < DATE_ADD(
        DATE_SUB(
            CURDATE(),
            INTERVAL WEEKDAY(CURDATE()) DAY
        ),
        INTERVAL 7 DAY
    )
    AND store_id = ?
    GROUP BY DATE(sale_date)
    ORDER BY sale_day ASC
";

$stmtSalesOverview = mysqli_prepare($conn, $sqlSalesOverview);
mysqli_stmt_bind_param($stmtSalesOverview, "i", $storeId);
mysqli_stmt_execute($stmtSalesOverview);

$resultSalesOverview = mysqli_stmt_get_result($stmtSalesOverview);


// Create Monday through Sunday with zero sales by default
$salesByDay = [];

$weekStart = new DateTime("monday this week");

for ($i = 0; $i < 7; $i++) {

    $date = clone $weekStart;
    $date->modify("+$i day");

    $dateKey = $date->format("Y-m-d");

    $salesByDay[$dateKey] = 0;
}


// Put database sales into the correct day
while ($row = mysqli_fetch_assoc($resultSalesOverview)) {

    $salesByDay[$row["sale_day"]] =
        (float) $row["daily_sales"];

}


// Find the highest daily sale for chart scaling
$maxDailySales = max($salesByDay);

if ($maxDailySales <= 0) {
    $maxDailySales = 1;
}


// ==========================================
// LOW STOCK ITEMS
// ==========================================

$sqlLowStockItems = "
    SELECT
        product_name,
        stock_quantity,
        reorder_level
    FROM products
    WHERE stock_quantity <= reorder_level
    AND store_id = ?
    ORDER BY stock_quantity ASC, product_name ASC
";

$stmtLowStockItems = mysqli_prepare($conn, $sqlLowStockItems);
mysqli_stmt_bind_param($stmtLowStockItems, "i", $storeId);
mysqli_stmt_execute($stmtLowStockItems);

$resultLowStockItems = mysqli_stmt_get_result($stmtLowStockItems);


// recent transaction
$sqlRecentTransactions = "
    SELECT
        s.sale_id,
        s.sale_date,
        s.payment_method,
        s.total_amount,
        COALESCE(SUM(si.quantity), 0) AS total_items
    FROM sales s
    LEFT JOIN sale_item si
        ON s.sale_id = si.sale_id
    WHERE s.store_id = ?
    GROUP BY
        s.sale_id,
        s.sale_date,
        s.payment_method,
        s.total_amount
    ORDER BY s.sale_date DESC
    LIMIT 5
";

$stmtRecentTransactions = mysqli_prepare($conn, $sqlRecentTransactions);
mysqli_stmt_bind_param($stmtRecentTransactions, "i", $storeId);
mysqli_stmt_execute($stmtRecentTransactions);

$resultRecentTransactions = mysqli_stmt_get_result($stmtRecentTransactions);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockIT — Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">

  <aside class="sidebar">
    <div class="brand">
      <div class="brand-mark">
        <svg viewBox="0 0 24 24" fill="none"><path d="M4 7L12 3L20 7V17L12 21L4 17V7Z" stroke="white" stroke-width="1.6" stroke-linejoin="round"/><path d="M4 7L12 11L20 7" stroke="white" stroke-width="1.6" stroke-linejoin="round"/><path d="M12 11V21" stroke="white" stroke-width="1.6"/></svg>
      </div>
      <div class="brand-word">Stock<span>IT</span></div>
    </div>

    <div class="nav-section-label">MENU</div>
    <nav>
      <a class="nav-item active" href="index.php">
        <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="3" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="12" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="3" y="16" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="1.6"/></svg>
        Dashboard
      </a>
      <a class="nav-item" href="pos.php">
        <svg viewBox="0 0 24 24" fill="none"><rect x="2.5" y="6" width="19" height="13" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M2.5 10.5H21.5" stroke="currentColor" stroke-width="1.6"/><path d="M7 14H11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Point of Sale
      </a>
      <a class="nav-item" href="products.php">
        <svg viewBox="0 0 24 24" fill="none"><path d="M3 7.5L12 3L21 7.5V16.5L12 21L3 16.5V7.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M3 7.5L12 12M12 12L21 7.5M12 12V21" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        Products
      </a>
      <a class="nav-item" href="sales_history.php">
        <svg viewBox="0 0 24 24" fill="none"><path d="M4 4V15C4 17.2091 5.79086 19 8 19H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M8 15L12 10L15 13L20 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Sales History
      </a>
      <a class="nav-item <?php echo $currentPage == 'salesReport.php' ? 'active' : ''; ?>" href="salesReport.php">
        <svg viewBox="0 0 24 24" fill="none"><path d="M6 3H14L19 8V19C19 20.1046 18.1046 21 17 21H6C4.89543 21 4 20.1046 4 19V5C4 3.89543 4.89543 3 6 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 12H15M8 16H15M8 8.5H10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Sales Report
      </a>
    </nav>
  </aside>

  <main class="main">
    <div class="topbar">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-sub">Here's how your store is doing today.</p>
      </div>
      <div class="topbar-actions">
        
        <div class="user-menu" id="userMenu">
          <button type="button" class="user-chip" id="userMenuButton" aria-label="Open admin account menu" aria-expanded="false">
            <div class="avatar">AD</div>
            <span class="uname">admin</span>
            <svg viewBox="0 0 24 24" fill="none"><path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>

          <div class="user-dropdown" aria-label="Admin actions">
            <a href="logout.php">Logout</a>
          </div>
        </div>
      </div>
    </div>

    <section class="stat-grid">

        <!-- TODAY'S SALES -->
        <div class="stat-card">

            <div class="stat-top">

                <div>
                    <span class="stat-label">TODAY'S SALES</span>
                    <div class="stat-value">
                        ₱<?php echo number_format($todaySales, 2); ?>
                    </div>
                </div>

                <div class="stat-icon icon-blue">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 2V22"
                              stroke="currentColor"
                              stroke-width="1.7"
                              stroke-linecap="round"/>

                        <path d="M17 6.5C17 4.6 14.8 3 12 3
                                C9.2 3 7 4.6 7 6.5
                                C7 8.4 9.2 9.4 12 10
                                C14.8 10.6 17 11.6 17 13.5
                                C17 15.4 14.8 17 12 17
                                C9.2 17 7 15.4 7 13.5"
                              stroke="currentColor"
                              stroke-width="1.7"
                              stroke-linecap="round"/>
                    </svg>
                </div>

            </div>

            <div class="stat-foot">
                Sales recorded today
            </div>

        </div>


        <!-- TODAY'S TRANSACTIONS -->
        <div class="stat-card">

            <div class="stat-top">

                <div>
                    <span class="stat-label">TRANSACTIONS</span>

                    <div class="stat-value">
                        <?php echo number_format($todayTransactions); ?>
                    </div>
                </div>

                <div class="stat-icon icon-blue">
                    <svg viewBox="0 0 24 24" fill="none">

                        <rect x="3"
                              y="4"
                              width="18"
                              height="16"
                              rx="2"
                              stroke="currentColor"
                              stroke-width="1.6"/>

                        <path d="M7 9H17"
                              stroke="currentColor"
                              stroke-width="1.6"
                              stroke-linecap="round"/>

                        <path d="M7 13H14"
                              stroke="currentColor"
                              stroke-width="1.6"
                              stroke-linecap="round"/>

                    </svg>
                </div>

            </div>

            <div class="stat-foot">
                Completed today
            </div>

        </div>


        <!-- TOTAL PRODUCTS -->
        <div class="stat-card">

            <div class="stat-top">

                <div>
                    <span class="stat-label">TOTAL PRODUCTS</span>

                    <div class="stat-value">
                        <?php echo number_format($totalProducts); ?>
                    </div>
                </div>

                <div class="stat-icon icon-blue">
                    <svg viewBox="0 0 24 24" fill="none">

                        <path d="M3 7.5L12 3L21 7.5V16.5L12 21L3 16.5V7.5Z"
                              stroke="currentColor"
                              stroke-width="1.6"
                              stroke-linejoin="round"/>

                        <path d="M3 7.5L12 12L21 7.5"
                              stroke="currentColor"
                              stroke-width="1.6"
                              stroke-linejoin="round"/>

                        <path d="M12 12V21"
                              stroke="currentColor"
                              stroke-width="1.6"/>

                    </svg>
                </div>

            </div>

            <div class="stat-foot">
                Registered in inventory
            </div>

        </div>


        <!-- LOW STOCK -->
        <div class="stat-card">

            <div class="stat-top">

                <div>
                    <span class="stat-label">LOW STOCK</span>

                    <div class="stat-value">
                        <?php echo number_format($lowStock); ?>
                    </div>
                </div>

                <div class="stat-icon icon-amber">
                    <svg viewBox="0 0 24 24" fill="none">

                        <path d="M12 3L22 20H2L12 3Z"
                              stroke="currentColor"
                              stroke-width="1.6"
                              stroke-linejoin="round"/>

                        <path d="M12 9.5V13.5"
                              stroke="currentColor"
                              stroke-width="1.7"
                              stroke-linecap="round"/>

                        <circle cx="12"
                                cy="16.3"
                                r="0.9"
                                fill="currentColor"/>

                    </svg>
                </div>

            </div>

            <div class="stat-foot">
                Items need restocking
            </div>

        </div>

    </section>

    <section class="dashboard-middle">

        <!-- SALES OVERVIEW -->
        <div class="dashboard-chart-card">

            <div class="dashboard-section-header">

                <div>
                    <h2>Sales Overview</h2>
                    <p>Revenue for this week</p>
                </div>

            </div>


            <div class="sales-chart">
              <!-- Y-AXIS VALUES -->
              <div class="sales-y-axis">

                  <span>
                      ₱<?php echo number_format($maxDailySales, 0); ?>
                  </span>

                  <span>
                      ₱<?php echo number_format($maxDailySales * 0.75, 0); ?>
                  </span>

                  <span>
                      ₱<?php echo number_format($maxDailySales * 0.50, 0); ?>
                  </span>

                  <span>
                      ₱<?php echo number_format($maxDailySales * 0.25, 0); ?>
                  </span>

                  <span>
                      ₱0
                  </span>

              </div>



              <div class="sales-grid-lines">
                  <span></span>
                  <span></span>
                  <span></span>
                  <span></span>
                  <span></span>
              </div>

                <?php foreach ($salesByDay as $date => $sales): ?>

                    <?php

                    $barHeight =
                        ($sales / $maxDailySales) * 100;

                    $dayLabel =
                        date("D", strtotime($date));

                    ?>

                    <div class="sales-chart-column">

                        <div class="sales-chart-bar-wrapper">

                            <strong
                                class="sales-chart-value"
                                style="bottom: calc(<?php echo $barHeight; ?>% + 6px);">
                                ₱<?php echo number_format($sales, 0); ?>
                            </strong>

                            <span
                                class="sales-chart-bar"
                                style="height: <?php echo $barHeight; ?>%;">
                            </span>

                        </div>

                        <small>
                            <?php echo $dayLabel; ?>
                        </small>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>


        <!-- LOW STOCK -->
        <div class="dashboard-stock-card">

            <div class="dashboard-section-header">

                <div>
                    <h2>Low Stock Items</h2>
                    <p>Products that need attention</p>
                </div>

                <span class="low-stock-count">
                    <?php echo $lowStock; ?>
                </span>

            </div>


            <div class="low-stock-list">

                <?php if (mysqli_num_rows($resultLowStockItems) > 0): ?>

                    <?php while ($item = mysqli_fetch_assoc($resultLowStockItems)): ?>

                        <div class="low-stock-item">

                            <div class="low-stock-product">

                                <strong>
                                    <?php echo htmlspecialchars($item["product_name"]); ?>
                                </strong>

                                <span>
                                    Reorder level:
                                    <?php echo $item["reorder_level"]; ?>
                                </span>

                            </div>

                            <div class="low-stock-quantity">

                                <?php echo $item["stock_quantity"]; ?>

                                <small>left</small>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="no-low-stock">
                        All products are sufficiently stocked.
                    </div>

                <?php endif; ?>

            </div>


            <a href="products.php" class="view-inventory-link">
                View Inventory →
            </a>

        </div>
    </section>

    
    <!-- recent transaction section -->
    <section class="recent-transactions-card">
        <div class="dashboard-section-header">
            <div>
                <h2>Recent Transactions</h2>
                <p>Your latest sales transactions</p>
            </div>

            <a href="sales_history.php" class="view-all-link">
                View All →
            </a>
        </div>

        <div class="recent-transactions-table-wrapper">

            <table class="recent-transactions-table">

                <thead>
                    <tr>
                        <th>TRANSACTION</th>
                        <th>DATE & TIME</th>
                        <th>ITEMS</th>
                        <th>PAYMENT</th>
                        <th>TOTAL</th>
                        <th>ACTION</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (mysqli_num_rows($resultRecentTransactions) > 0): ?>

                        <?php while ($transaction = mysqli_fetch_assoc($resultRecentTransactions)): ?>

                            <tr>

                                <td>
                                    <strong>
                                        #<?php echo $transaction["sale_id"]; ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php echo date(
                                        "M d, Y • h:i A",
                                        strtotime($transaction["sale_date"])
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo number_format($transaction["total_items"]); ?>
                                </td>

                                <td>
                                    <span class="payment-badge <?php echo strtolower($transaction["payment_method"]); ?>">
                                        <?php echo htmlspecialchars($transaction["payment_method"]); ?>
                                    </span>
                                </td>

                                <td>
                                    <strong>
                                        ₱<?php echo number_format($transaction["total_amount"], 2); ?>
                                    </strong>
                                </td>

                                <td>
                                    <a
                                        href="sales_history.php?sale_id=<?php echo $transaction["sale_id"]; ?>"
                                        class="transaction-view-btn">
                                        View
                                    </a>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="no-transactions">
                                No transactions found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

  </main>
</div>

<script src="script.js"></script>

<script>
window.addEventListener("pageshow", function (event) {

    if (event.persisted) {
        window.location.reload();
    }

});
</script>
</body>
</html>