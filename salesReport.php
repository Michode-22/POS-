<?php
session_start();

if(!isset($_SESSION["user"])){
    header("Location: login.php");
    exit();
}
require_once "dbase.php";

if (!isset($_SESSION["store_id"])) {
    die("Store account is not configured.");
}

$storeId = $_SESSION["store_id"];

$currentPage = basename($_SERVER['PHP_SELF']);

$period = $_GET["period"] ?? "this_week";
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
    $period = "this_week";
}


// Determine the selected date range
if ($period === "today") {

    $dateCondition = "DATE(sale_date) = CURDATE()";

} elseif ($period === "this_week") {

    $dateCondition = "
        sale_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
        AND sale_date < DATE_ADD(
            DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY),
            INTERVAL 7 DAY
        )
    ";

} elseif ($period === "last_week") {

    $dateCondition = "
        sale_date >= DATE_SUB(
            CURDATE(),
            INTERVAL (WEEKDAY(CURDATE()) + 7) DAY
        )
        AND sale_date < DATE_SUB(
            CURDATE(),
            INTERVAL WEEKDAY(CURDATE()) DAY
        )
    ";

} elseif ($period === "this_month") {

    $dateCondition = "
        sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
        AND sale_date < DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 1 MONTH
        )
    ";

} elseif ($period === "last_month") {

    $dateCondition = "
        sale_date >= DATE_FORMAT(
            CURDATE() - INTERVAL 1 MONTH,
            '%Y-%m-01'
        )
        AND sale_date < DATE_FORMAT(
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

        $period = "this_week";
        $customDate = "";

        $dateCondition = "
            sale_date >= DATE_SUB(
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
        ";

    } else {

        $safeCustomDate = mysqli_real_escape_string(
            $conn,
            $customDate
        );

        $dateCondition = "
            sale_date >= '$safeCustomDate 00:00:00'
            AND sale_date < DATE_ADD(
                '$safeCustomDate 00:00:00',
                INTERVAL 1 DAY
            )
        ";
    }
}


//total sales card query
$sqlTotalSales = "
    SELECT COALESCE(SUM(total_amount), 0) AS total_sales
    FROM sales
    WHERE $dateCondition
    AND store_id = ?
";

$stmtTotalSales = mysqli_prepare($conn, $sqlTotalSales);
mysqli_stmt_bind_param($stmtTotalSales, "i", $storeId);
mysqli_stmt_execute($stmtTotalSales);

$resultTotalSales = mysqli_stmt_get_result($stmtTotalSales);

$totalSalesData = mysqli_fetch_assoc($resultTotalSales);

$totalSales = $totalSalesData["total_sales"];

mysqli_stmt_close($stmtTotalSales);


//total cost card query
$sqlTotalCost = "
    SELECT COALESCE(SUM(si.cost * si.quantity), 0) AS total_cost
    FROM sale_item si
    INNER JOIN sales s ON si.sale_id = s.sale_id
    WHERE $dateCondition
    AND s.store_id = ?
";

$stmtTotalCost = mysqli_prepare($conn, $sqlTotalCost);
mysqli_stmt_bind_param($stmtTotalCost, "i", $storeId);
mysqli_stmt_execute($stmtTotalCost);

$resultTotalCost = mysqli_stmt_get_result($stmtTotalCost);

$totalCostData = mysqli_fetch_assoc($resultTotalCost);

$totalCost = $totalCostData["total_cost"];

mysqli_stmt_close($stmtTotalCost);


// PRODUCT SOLD CARD QUERY
$sqlProductSold = "
    SELECT COALESCE(SUM(si.quantity), 0) AS product_sold
    FROM sale_item si
    INNER JOIN sales s ON si.sale_id = s.sale_id
    WHERE $dateCondition
    AND s.store_id = ?
";

$stmtProductSold = mysqli_prepare($conn, $sqlProductSold);
mysqli_stmt_bind_param($stmtProductSold, "i", $storeId);
mysqli_stmt_execute($stmtProductSold);

$resultProductSold = mysqli_stmt_get_result($stmtProductSold);

$productSoldData = mysqli_fetch_assoc($resultProductSold);

$productSold = $productSoldData["product_sold"];

mysqli_stmt_close($stmtProductSold);


// GROSS PROFIT
$grossProfit = $totalSales - $totalCost;


//sales overview chart
$chartData = [];
$maxDailySales = 0;


// Determine the date range for the chart
if ($period === "this_week") {

    $chartStart = new DateTime("monday this week");
    $chartEnd = new DateTime("sunday this week");

} elseif ($period === "last_week") {

    $chartStart = new DateTime("monday last week");
    $chartEnd = new DateTime("sunday last week");

} elseif ($period === "this_month") {

    $chartStart = new DateTime("first day of this month");
    $chartEnd = new DateTime("last day of this month");

} elseif ($period === "last_month") {

    $chartStart = new DateTime("first day of last month");
    $chartEnd = new DateTime("last day of last month");

} elseif ($period === "custom") {

    $chartStart = new DateTime($customDate);
    $chartEnd = new DateTime($customDate);

} else {

    // Keep Today available for future download/report use
    $chartStart = new DateTime("today");
    $chartEnd = new DateTime("today");
}


// Get actual sales grouped by date
$sqlSalesChart = "
    SELECT
        DATE(sale_date) AS sale_day,
        COALESCE(SUM(total_amount), 0) AS daily_sales
    FROM sales
    WHERE $dateCondition
    AND store_id = ?
    GROUP BY DATE(sale_date)
    ORDER BY sale_day ASC
";

$stmtSalesChart = mysqli_prepare($conn, $sqlSalesChart);
mysqli_stmt_bind_param($stmtSalesChart, "i", $storeId);
mysqli_stmt_execute($stmtSalesChart);

$resultSalesChart = mysqli_stmt_get_result($stmtSalesChart);

mysqli_stmt_close($stmtSalesChart);


// Store actual sales using the date as the key
$salesByDate = [];

while ($row = mysqli_fetch_assoc($resultSalesChart)) {

    $salesByDate[$row["sale_day"]] =
        (float) $row["daily_sales"];
}


// Generate every date in the selected period
$currentDate = clone $chartStart;

while ($currentDate <= $chartEnd) {

    $dateKey = $currentDate->format("Y-m-d");

    $dailySales = $salesByDate[$dateKey] ?? 0;

    $chartData[] = [
        "date" => $dateKey,
        "sales" => $dailySales
    ];

    if ($dailySales > $maxDailySales) {
        $maxDailySales = $dailySales;
    }

    $currentDate->modify("+1 day");
}


// Prevent division by zero
if ($maxDailySales <= 0) {
    $maxDailySales = 1;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>StockIT — Sales Report</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">

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
                        Sales Report
                    </h1>

                    <p class="page-sub">
                        View your store's sales performance and profit.
                    </p>

                </div>

            </div>

            <!--cards -->

            <section class="stat-grid">

                <div class="stat-card">

                    <div class="stat-top">

                        <div>
                            <span class="stat-label">TOTAL SALES</span>

                            <div class="stat-value">
                                ₱<?php echo number_format($totalSales, 2); ?>
                            </div>
                        </div>

                    </div>

                    <div class="stat-foot">
                        Total revenue from sales
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <div>
                            <span class="stat-label">TOTAL COST</span>

                            <div class="stat-value">
                                ₱<?php echo number_format($totalCost, 2); ?>
                            </div>
                        </div>

                    </div>

                    <div class="stat-foot">
                        Cost of products sold
                    </div>

                </div>


                <div class="stat-card">
                    <div class="stat-top">
                        <div>
                            <span class="stat-label">PRODUCT SOLD</span>
                            <div class="stat-value">
                                <?php echo number_format($productSold); ?>
                            </div>
                        </div>
                    </div>

                    <div class="stat-foot">
                        Total quantity sold
                    </div>
                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <div>
                            <span class="stat-label">GROSS PROFIT</span>

                            <div class="stat-value">
                                ₱<?php echo number_format($grossProfit, 2); ?>
                            </div>
                        </div>

                    </div>

                    <div class="stat-foot">
                        Sales minus product cost
                    </div>

                </div>

            </section>

            <!--Download day/wek/month-->

            <div class="report-controls">

                <div class="report-period">

                    <div class="period-dropdown">

                        <button type="button" class="period-select" id="periodSelect">
                            <span id="selectedPeriod">
                                <?php
                                $periodLabels = [
                                    "today" => "Today",
                                    "this_week" => "This Week",
                                    "last_week" => "Last Week",
                                    "this_month" => "This Month",
                                    "last_month" => "Last Month",
                                    "custom" => "Custom Date"
                                ];

                                echo $periodLabels[$period] ?? "Today";
                                ?>
                            </span>

                            <span class="period-arrow"></span>
                        </button>

                        <div class="period-menu" id="periodMenu">
                            <button
                                type="button"
                                class="period-option <?php echo $period === 'this_week' ? 'active' : ''; ?>"
                                data-period="this_week">
                                This Week
                            </button>

                            <button
                                type="button"
                                class="period-option <?php echo $period === 'last_week' ? 'active' : ''; ?>"
                                data-period="last_week">
                                Last Week
                            </button>

                            <button
                                type="button"
                                class="period-option <?php echo $period === 'this_month' ? 'active' : ''; ?>"
                                data-period="this_month">
                                This Month
                            </button>

                            <button
                                type="button"
                                class="period-option <?php echo $period === 'last_month' ? 'active' : ''; ?>"
                                data-period="last_month">
                                Last Month
                            </button>

                            <button
                                type="button"
                                class="period-option <?php echo $period === 'custom' ? 'active' : ''; ?>"
                                data-period="custom">
                                Custom Date
                            </button>

                        </div>

                    </div>

                    <div class="custom-date-picker" id="customDatePicker">

                        <input
                            type="date"
                            id="customDate"
                            value="<?php echo htmlspecialchars($customDate); ?>"
                        >

                        <button type="button" id="applyCustomDate">
                            Apply
                        </button>

                    </div>

                </div>

                <div class="download-dropdown">

                    <button type="button" class="download-btn" id="downloadBtn">
                        Download
                        <span class="download-arrow"></span>
                    </button>

                    <div class="download-menu" id="downloadMenu">
                        <button type="button" class="download-option" data-download="today">
                            Today
                        </button>

                        <button type="button" class="download-option" data-download="this_week">
                            This Week
                        </button>

                        <button type="button" class="download-option" data-download="last_week">
                            Last Week
                        </button>

                        <button type="button" class="download-option" data-download="this_month">
                            This Month
                        </button>

                        <button type="button" class="download-option" data-download="last_month">
                            Last Month
                        </button>

                        <button type="button" class="download-option" data-download="custom">
                            Custom Date
                        </button>
                    </div>

                    <div class="download-custom-date" id="downloadCustomDate">
                        <input type="date" id="downloadDate">
                        <button type="button" id="applyDownloadDate">
                            Download
                        </button>
                    </div>

                </div>

            </div>


            <!--chart-->
            <div class="chart-card">

                <div class="chart-head">

                    <div>
                        <h2 class="chart-title">Sales Overview</h2>

                        <p class="chart-sub">
                            Track your sales performance over time.
                        </p>
                    </div>

                </div>

                <div class="chart-wrap">

                    <?php if (empty($chartData)): ?>

                        <div style="
                            height: 350px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">
                            <span class="chart-sub">
                                No sales data for the selected period.
                            </span>
                        </div>

                    <?php else: ?>

                        <?php
                        $chartCount = count($chartData);

                        $chartWidth = max(1000, $chartCount * 60);

                        $chartHeight = 360;

                        $left = 65;
                        $right = 30;
                        $top = 40;
                        $plotHeight = 240;
                        $plotWidth = $chartWidth - $left - $right;

                        $slotWidth = $plotWidth / $chartCount;
                        $barWidth = min(26, $slotWidth * 0.65);

                        if ($maxDailySales <= 0) {
                            $maxDailySales = 1;
                        }

                        $labelStep = 1;
                        ?>

                        <div
                            class="chart-scroll"
                            style="width: <?php echo $chartWidth; ?>px;"
                        >

                            <svg
                                class="chart"
                                width="<?php echo $chartWidth; ?>"
                                height="360"
                                viewBox="0 0 <?php echo $chartWidth; ?> 360"
                            >

                            <!-- Grid Lines -->

                            <?php for ($i = 0; $i <= 4; $i++): ?>

                                <?php
                                $gridY = $top + ($plotHeight / 4) * $i;

                                $gridValue =
                                    $maxDailySales -
                                    ($maxDailySales / 4) * $i;
                                ?>

                                <line
                                    x1="<?php echo $left; ?>"
                                    y1="<?php echo $gridY; ?>"
                                    x2="<?php echo $chartWidth - $right; ?>"
                                    y2="<?php echo $gridY; ?>"
                                    class="gridline"
                                />

                                <text
                                    x="<?php echo $left - 10; ?>"
                                    y="<?php echo $gridY + 4; ?>"
                                    text-anchor="end"
                                    class="axis-label"
                                >
                                    ₱<?php echo number_format($gridValue, 0); ?>
                                </text>

                            <?php endfor; ?>


                            <!-- Sales Bars -->

                            <?php foreach ($chartData as $index => $item): ?>

                                <?php
                                if ($item["sales"] > 0) {

                                    $barHeight =
                                        ($item["sales"] / $maxDailySales) *
                                        $plotHeight;

                                    $isZeroBar = false;

                                } else {

                                    // Small visible bar for a ₱0 sales day
                                    $barHeight = 3;
                                    $isZeroBar = true;
                                }

                                $x =
                                    $left +
                                    ($index * $slotWidth) +
                                    (($slotWidth - $barWidth) / 2);

                                $y =
                                    $top +
                                    $plotHeight -
                                    $barHeight;

                                $isPeak =
                                    $item["sales"] == $maxDailySales && !$isZeroBar;
                                ?>

                                <rect
                                    x="<?php echo $x; ?>"
                                    y="<?php echo $y; ?>"
                                    width="<?php echo $barWidth; ?>"
                                    height="<?php echo $barHeight; ?>"
                                    rx="5"
                                    class="bar <?php
                                        echo $isPeak ? 'peak ' : '';
                                        echo $isZeroBar ? 'zero-bar' : '';
                                    ?>"
                                />

                                <text
                                    x="<?php echo $x + ($barWidth / 2); ?>"
                                    y="<?php echo $y - 8; ?>"
                                    text-anchor="middle"
                                    class="bar-value"
                                >
                                    ₱<?php echo number_format($item["sales"], 0); ?>
                                </text>

                                <?php if (
                                    $index % $labelStep === 0 ||
                                    $index === $chartCount - 1
                                ): ?>

                                    <text
                                        x="<?php echo $x + ($barWidth / 2); ?>"
                                        y="<?php echo $top + $plotHeight + 32; ?>"
                                        text-anchor="middle"
                                        class="day-label"
                                    >
                                        <?php echo date("M d", strtotime($item["date"])); ?>
                                    </text>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </svg>
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </main>
    </div>

    
<script>
const periodDropdown = document.querySelector(".period-dropdown");
const periodSelect = document.getElementById("periodSelect");
const periodOptions = document.querySelectorAll(".period-option");

const customDatePicker = document.getElementById("customDatePicker");
const customDate = document.getElementById("customDate");
const applyCustomDate = document.getElementById("applyCustomDate");


// Open / close dropdown
periodSelect.addEventListener("click", function() {
    periodDropdown.classList.toggle("open");
});


// Period option selection
periodOptions.forEach(option => {

    option.addEventListener("click", function() {

        const selectedPeriod = this.dataset.period;
        const selectedText = this.textContent.trim();

        document.getElementById("selectedPeriod").textContent = selectedText;

        periodOptions.forEach(item => {
            item.classList.remove("active");
        });

        this.classList.add("active");

        periodDropdown.classList.remove("open");


        // Custom Date
        if (selectedPeriod === "custom") {

            customDatePicker.classList.add("show");

        } else {

            customDatePicker.classList.remove("show");

            window.location.href =
                "salesReport.php?period=" + selectedPeriod;
        }

    });

});


// Show custom date picker automatically
// when Custom Date is already selected
<?php if ($period === "custom"): ?>
customDatePicker.classList.add("show");
<?php endif; ?>



// Apply Custom Date
applyCustomDate.addEventListener("click", function() {

    if (!customDate.value) {
        alert("Please select a date.");
        return;
    }

    window.location.href =
        "salesReport.php?period=custom&custom_date=" +
        encodeURIComponent(customDate.value);

});


// Close dropdown when clicking outside
document.addEventListener("click", function(event) {

    if (!periodDropdown.contains(event.target)) {
        periodDropdown.classList.remove("open");
    }

});

const downloadDropdown =
    document.querySelector(".download-dropdown");

const downloadBtn =
    document.getElementById("downloadBtn");

const downloadOptions =
    document.querySelectorAll(".download-option");


// Open download menu
downloadBtn.addEventListener("click", function(event) {

    event.stopPropagation();

    downloadDropdown.classList.toggle("open");

});


const downloadCustomDate = document.getElementById("downloadCustomDate");
const downloadDate = document.getElementById("downloadDate");
const applyDownloadDate = document.getElementById("applyDownloadDate");

// Click download option
downloadOptions.forEach(option => {
    option.addEventListener("click", function(event) {
        event.preventDefault();
        event.stopPropagation();

        const selectedDownload = this.dataset.download;

        downloadDropdown.classList.remove("open");

        if (selectedDownload === "today") {
            window.location.href =
                "download_sales.php?period=today";
        }

        if (selectedDownload === "this_week") {
            window.location.href =
                "download_sales.php?period=this_week";
        }

        if (selectedDownload === "last_week") {
            window.location.href =
                "download_sales.php?period=last_week";
        }
        
        if (selectedDownload === "this_month") {
            window.location.href =
                "download_sales.php?period=this_month";
        }
        
        if (selectedDownload === "last_month") {
            window.location.href =
                "download_sales.php?period=last_month";
        }

        if (selectedDownload === "custom") {
            downloadCustomDate.classList.add("show");
            return;
        }
    });
});

applyDownloadDate.addEventListener("click", function() {

    if (!downloadDate.value) {
        alert("Please select a date.");
        return;
    }

    window.location.href =
        "download_sales.php?period=custom&custom_date=" +
        encodeURIComponent(downloadDate.value);
});


// Close when clicking outside
document.addEventListener("click", function(event) {

    if (!downloadDropdown.contains(event.target)) {
        downloadDropdown.classList.remove("open");
    }

});
</script>
</body>
</html>