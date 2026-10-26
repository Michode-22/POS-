<?php
session_start();

// Redirect users who are already logged in.
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

$loginError = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $loginError = "Please enter your email and password.";
    } else {
        require_once "dbase.php";

        // Use a prepared statement to look up the account safely.
        $sql = "SELECT ID, EMAIL, PASSWORD, store_id
                FROM users
                WHERE EMAIL = ?
                LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if ($user && password_verify($password, $user["PASSWORD"])) {

                // StockIT accounts must belong to a store.
                if ($user["store_id"] === null || $user["store_id"] === "") {
                    $loginError = "This account is not assigned to a store. Please contact your administrator.";
                } else {
                    // Refresh the session ID after successful authentication.
                    session_regenerate_id(true);

                    $_SESSION["user"] = $user["ID"];
                    $_SESSION["store_id"] = $user["store_id"];

                    header("Location: index.php");
                    exit();
                }
            } else {
                $loginError = "The email or password you entered is incorrect.";
            }
        } else {
            $loginError = "We couldn't sign you in right now. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign In | StockIT</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Keep your existing stylesheet for the other pages. -->
    <link rel="stylesheet" href="style.css">

    <!-- New stylesheet used only for the StockIT login page. -->
    <link rel="stylesheet" href="login.css">
</head>

<body class="stockit-login-page">

    <main class="stockit-auth-layout">

        <!-- LEFT: StockIT branding panel -->
        <section class="stockit-brand-panel">

            <a class="stockit-brand" href="login.php" aria-label="StockIT home">
                <span class="stockit-brand-mark">S</span>

                <span class="stockit-brand-text">
                    <strong>StockIT</strong>
                    <small>RETAIL MANAGEMENT SYSTEM</small>
                </span>
            </a>

            <div class="stockit-brand-content">
                <span class="stockit-eyebrow">
                    <span class="stockit-eyebrow-dot"></span>
                    SMARTER STORE MANAGEMENT
                </span>

                <h1>
                    Your store.<br>
                    Your numbers.<br>
                    <span>One clear view.</span>
                </h1>

                <p class="stockit-brand-description">
                    Keep your inventory organized, process sales with confidence,
                    and understand your store's performance in one place.
                </p>

                <div class="stockit-feature-list">

                    <div class="stockit-feature">
                        <span class="stockit-feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"
                                      stroke="currentColor" stroke-width="1.7"
                                      stroke-linejoin="round"/>
                                <path d="m4.5 7.7 7.5 4.4 7.5-4.4M12 12.1V21"
                                      stroke="currentColor" stroke-width="1.7"
                                      stroke-linejoin="round"/>
                            </svg>
                        </span>

                        <span>
                            <strong>Inventory control</strong>
                            <small>Keep products and stock organized.</small>
                        </span>
                    </div>

                    <div class="stockit-feature">
                        <span class="stockit-feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="3" y="5" width="18" height="15" rx="2"
                                      stroke="currentColor" stroke-width="1.7"/>
                                <path d="M7 9h10M7 13h3m3 0h4m-10 4h4"
                                      stroke="currentColor" stroke-width="1.7"
                                      stroke-linecap="round"/>
                            </svg>
                        </span>

                        <span>
                            <strong>Faster checkout</strong>
                            <small>Record transactions in one workflow.</small>
                        </span>
                    </div>

                    <div class="stockit-feature">
                        <span class="stockit-feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4 19V5m0 14h17"
                                      stroke="currentColor" stroke-width="1.7"
                                      stroke-linecap="round"/>
                                <path d="m7 15 4-4 3 2 5-6"
                                      stroke="currentColor" stroke-width="1.9"
                                      stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>

                        <span>
                            <strong>Sales insights</strong>
                            <small>Review performance and sales reports.</small>
                        </span>
                    </div>

                </div>

                <!-- Decorative dashboard preview -->
                <div class="stockit-preview" aria-hidden="true">
                    <div class="stockit-preview-header">
                        <span class="stockit-preview-heading">
                            <span class="stockit-preview-dot"></span>
                            STORE WORKSPACE
                        </span>
                        <span class="stockit-preview-label">StockIT</span>
                    </div>

                    <div class="stockit-preview-body">
                        <div class="stockit-preview-sidebar">
                            <span class="preview-nav active"></span>
                            <span class="preview-nav"></span>
                            <span class="preview-nav"></span>
                            <span class="preview-nav short"></span>
                        </div>

                        <div class="stockit-preview-main">
                            <span class="preview-line wide"></span>
                            <span class="preview-line medium"></span>

                            <div class="preview-mini-cards">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>

                            <div class="preview-chart">
                                <span style="height: 32%"></span>
                                <span style="height: 49%"></span>
                                <span style="height: 39%"></span>
                                <span style="height: 66%"></span>
                                <span style="height: 53%"></span>
                                <span style="height: 83%"></span>
                                <span style="height: 71%"></span>
                                <span style="height: 94%"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stockit-brand-footer">
                <span class="stockit-footer-line"></span>
                <span>BUILT FOR BETTER STORE OPERATIONS</span>
            </div>

        </section>


        <!-- RIGHT: Login form -->
        <section class="stockit-login-panel">

            <div class="stockit-login-content">

                <!-- Visible on small screens when the branding panel is hidden -->
                <a class="stockit-mobile-brand" href="login.php">
                    <span class="stockit-brand-mark">S</span>
                    <strong>StockIT</strong>
                </a>

                <div class="stockit-login-heading">
                    <span class="stockit-login-kicker">SECURE ACCOUNT ACCESS</span>

                    <h2>Welcome back.</h2>

                    <p>
                        Sign in to access your store dashboard and manage your business.
                    </p>
                </div>

                <?php if ($loginError !== ""): ?>
                    <div class="stockit-login-alert" role="alert">
                        <span class="stockit-alert-icon" aria-hidden="true">!</span>
                        <span>
                            <?= htmlspecialchars($loginError, ENT_QUOTES, "UTF-8") ?>
                        </span>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="post" class="stockit-login-form">

                    <div class="stockit-form-group">
                        <label for="email">Email address</label>

                        <div class="stockit-input-wrap">
                            <span class="stockit-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="5" width="18" height="14" rx="2"
                                          stroke="currentColor" stroke-width="1.7"/>
                                    <path d="m4 7 8 6 8-6"
                                          stroke="currentColor" stroke-width="1.7"
                                          stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="you@example.com"
                                value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>"
                                autocomplete="email"
                                required
                            >
                        </div>
                    </div>

                    <div class="stockit-form-group">
                        <div class="stockit-label-row">
                            <label for="password">Password</label>
                        </div>

                        <div class="stockit-input-wrap">
                            <span class="stockit-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="4" y="10" width="16" height="11" rx="2"
                                          stroke="currentColor" stroke-width="1.7"/>
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3"
                                          stroke="currentColor" stroke-width="1.7"
                                          stroke-linecap="round"/>
                                    <path d="M12 14v3"
                                          stroke="currentColor" stroke-width="1.7"
                                          stroke-linecap="round"/>
                                </svg>
                            </span>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="stockit-toggle-password"
                                id="togglePassword"
                                aria-label="Show password"
                                aria-pressed="false"
                            >
                                Show
                            </button>
                        </div>
                    </div>

                    <button type="submit" name="submit" class="stockit-submit-btn">
                        <span>Sign in to StockIT</span>

                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"
                                  stroke="currentColor" stroke-width="1.8"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                </form>

                <div class="stockit-security-note">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3 19 6v5c0 4.4-2.8 7.8-7 10-4.2-2.2-7-5.6-7-10V6l7-3Z"
                              stroke="currentColor" stroke-width="1.7"
                              stroke-linejoin="round"/>
                        <path d="m9 12 2 2 4-4"
                              stroke="currentColor" stroke-width="1.7"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>

                    <span>Your store workspace is protected by account sign-in.</span>
                </div>

                <div class="stockit-register-prompt">
                    <span>New to StockIT?</span>
                    <a href="register.php">Create an account</a>
                </div>

                <p class="stockit-login-copyright">
                    © <?= date("Y") ?> StockIT. All rights reserved.
                </p>

            </div>

        </section>

    </main>

    <script>
        // Show or hide the password.
        const passwordInput = document.getElementById("password");
        const togglePassword = document.getElementById("togglePassword");

        togglePassword.addEventListener("click", function () {
            const isPassword = passwordInput.type === "password";

            passwordInput.type = isPassword ? "text" : "password";
            togglePassword.textContent = isPassword ? "Hide" : "Show";
            togglePassword.setAttribute("aria-label",
                isPassword ? "Hide password" : "Show password");
            togglePassword.setAttribute("aria-pressed", String(isPassword));
        });

        // Refresh the page if the browser restores it from its back-forward cache.
        window.addEventListener("pageshow", function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>

</body>
</html>