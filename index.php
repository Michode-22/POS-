<?php
//check if user log in
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
}
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
      <a class="nav-item" href="#">
        <svg viewBox="0 0 24 24" fill="none"><path d="M4 4V15C4 17.2091 5.79086 19 8 19H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M8 15L12 10L15 13L20 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Sales History
      </a>
      <a class="nav-item" href="#">
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
        <div class="icon-btn">
          <svg viewBox="0 0 24 24" fill="none"><path d="M12 3C13.5 3 14.5 4.2 14.5 5.5V6.2C17 6.9 18.7 9.1 18.7 11.7V15.2L20.3 17.3C20.6 17.7 20.3 18.3 19.8 18.3H4.2C3.7 18.3 3.4 17.7 3.7 17.3L5.3 15.2V11.7C5.3 9.1 7 6.9 9.5 6.2V5.5C9.5 4.2 10.5 3 12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9.5 20.5C9.9 21.4 10.9 22 12 22C13.1 22 14.1 21.4 14.5 20.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
          <span class="dot"></span>
        </div>
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
      <div class="stat-card">
        <div class="stat-top">
          <span class="stat-label">TODAY'S SALE</span>
          <div class="stat-icon icon-blue">
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 2V22" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M17 6.5C17 4.6 14.8 3 12 3C9.2 3 7 4.6 7 6.5C7 8.4 9.2 9.4 12 10C14.8 10.6 17 11.6 17 13.5C17 15.4 14.8 17 12 17C9.2 17 7 15.4 7 13.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
          </div>
        </div>
        <div class="stat-value placeholder">$—.—</div>
        <div class="stat-foot">No sales recorded yet today</div>
      </div>

      <div class="stat-card">
        <div class="stat-top">
          <span class="stat-label">TOTAL PRODUCTS</span>
          <div class="stat-icon icon-blue">
            <svg viewBox="0 0 24 24" fill="none"><path d="M3 7.5L12 3L21 7.5V16.5L12 21L3 16.5V7.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M3 7.5L12 12M12 12L21 7.5M12 12V21" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
          </div>
        </div>
        <div class="stat-value placeholder">—</div>
        <div class="stat-foot">Across all categories</div>
      </div>

      <div class="stat-card">
        <div class="stat-top">
          <span class="stat-label">LOW STOCK</span>
          <div class="stat-icon icon-amber">
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 3L22 20H2L12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M12 9.5V13.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="12" cy="16.3" r="0.9" fill="currentColor"/></svg>
          </div>
        </div>
        <div class="stat-value placeholder">—</div>
        <div class="stat-foot">Items need restocking</div>
      </div>
    </section>

    <section class="chart-card">
      <div class="chart-head">
        <div>
          <h2 class="chart-title">Sales Overview</h2>
          <p class="chart-sub">Revenue trend for the selected period</p>
        </div>
        <div class="period-select">
          Last 7 Days
          <svg viewBox="0 0 24 24" fill="none"><path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
      </div>

      <div class="chart-wrap">
        <svg class="chart" viewBox="0 0 760 300" preserveAspectRatio="xMidYMid meet">
          <!-- gridlines -->
          <line class="gridline" x1="46" y1="20"  x2="740" y2="20"/>
          <line class="gridline" x1="46" y1="70"  x2="740" y2="70"/>
          <line class="gridline" x1="46" y1="120" x2="740" y2="120"/>
          <line class="gridline" x1="46" y1="170" x2="740" y2="170"/>
          <line class="gridline" x1="46" y1="220" x2="740" y2="220"/>
          <line class="gridline" x1="46" y1="250" x2="740" y2="250" stroke="var(--ink-400)"/>

          <!-- y axis labels -->
          <text class="axis-label" x="10" y="24">$10000</text>
          <text class="axis-label" x="18" y="74">$8000</text>
          <text class="axis-label" x="18" y="124">$6000</text>
          <text class="axis-label" x="18" y="174">$4000</text>
          <text class="axis-label" x="18" y="224">$2000</text>
          <text class="axis-label" x="30" y="254">$0</text>

          <!-- bars: baseline y=250, scale 230/10000 -->
          <!-- Mon 4200 -->
          <rect class="bar" x="70"  y="153.7" width="46" rx="6" height="96.3"/>
          <!-- Tue 1800 -->
          <rect class="bar" x="163" y="208.6" width="46" rx="6" height="41.4"/>
          <!-- Wed 8000 -->
          <rect class="bar" x="256" y="66"   width="46" rx="6" height="184"/>
          <!-- Thu 4900 -->
          <rect class="bar" x="349" y="137.3" width="46" rx="6" height="112.7"/>
          <!-- Fri 10200 peak -->
          <rect class="bar peak" x="442" y="15.4" width="46" rx="6" height="234.6"/>
          <!-- Sat 4700 -->
          <rect class="bar" x="535" y="141.9" width="46" rx="6" height="108.1"/>
          <!-- Sun 8300 -->
          <rect class="bar" x="628" y="59.1" width="46" rx="6" height="190.9"/>

          <!-- day labels -->
          <text class="day-label" x="93"  y="272" text-anchor="middle">Mon</text>
          <text class="day-label" x="186" y="272" text-anchor="middle">Tue</text>
          <text class="day-label" x="279" y="272" text-anchor="middle">Wed</text>
          <text class="day-label" x="372" y="272" text-anchor="middle">Thu</text>
          <text class="day-label" x="465" y="272" text-anchor="middle">Fri</text>
          <text class="day-label" x="558" y="272" text-anchor="middle">Sat</text>
          <text class="day-label" x="651" y="272" text-anchor="middle">Sun</text>
        </svg>
      </div>
    </section>
  </main>
</div>

<script src="script.js"></script>
</body>
</html>