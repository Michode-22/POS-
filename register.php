
<?php
session_start();

// Redirect users who are already logged in.
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

$errors = [];
$fullname = "";
$email = "";
$storeName = "";
$registered = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["repeatpassword"] ?? "";
    $storeName = trim($_POST["storename"] ?? "");

    // Validate the submitted fields.
    if (
        $fullname === "" ||
        $email === "" ||
        $password === "" ||
        $confirmPassword === "" ||
        $storeName === ""
    ) {
        $errors[] = "Please complete all fields.";
    }

    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($password !== "" && strlen($password) < 8) {
        $errors[] = "Your password must be at least 8 characters long.";
    }

    if (
        $password !== "" &&
        $confirmPassword !== "" &&
        $password !== $confirmPassword
    ) {
        $errors[] = "Your passwords do not match.";
    }

    // Only access the database if the input is valid.
    if (empty($errors)) {
        require_once "dbase.php";

        try {
            mysqli_begin_transaction($conn);

            // Check whether this email already exists.
            $sqlEmail = "SELECT ID FROM users WHERE EMAIL = ? LIMIT 1";
            $stmtEmail = mysqli_prepare($conn, $sqlEmail);

            if (!$stmtEmail) {
                throw new Exception("Unable to prepare email lookup.");
            }

            mysqli_stmt_bind_param($stmtEmail, "s", $email);

            if (!mysqli_stmt_execute($stmtEmail)) {
                throw new Exception("Unable to check email.");
            }

            mysqli_stmt_store_result($stmtEmail);
            $emailExists = mysqli_stmt_num_rows($stmtEmail) > 0;

            mysqli_stmt_close($stmtEmail);

            if ($emailExists) {
                mysqli_rollback($conn);
                $errors[] = "An account with this email already exists.";
            } else {
                // Hash the password before saving it.
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                if ($hashedPassword === false) {
                    throw new Exception("Unable to secure password.");
                }

                // Create the store.
                $sqlStore = "INSERT INTO stores (store_name) VALUES (?)";
                $stmtStore = mysqli_prepare($conn, $sqlStore);

                if (!$stmtStore) {
                    throw new Exception("Unable to prepare store creation.");
                }

                mysqli_stmt_bind_param($stmtStore, "s", $storeName);

                if (!mysqli_stmt_execute($stmtStore)) {
                    throw new Exception("Unable to create store.");
                }

                $storeId = (int) mysqli_insert_id($conn);
                mysqli_stmt_close($stmtStore);

                // Create the user and link the user to the new store.
                $sqlUser = "
                    INSERT INTO users (FULL_NAME, EMAIL, PASSWORD, store_id)
                    VALUES (?, ?, ?, ?)
                ";

                $stmtUser = mysqli_prepare($conn, $sqlUser);

                if (!$stmtUser) {
                    throw new Exception("Unable to prepare account creation.");
                }

                mysqli_stmt_bind_param(
                    $stmtUser,
                    "sssi",
                    $fullname,
                    $email,
                    $hashedPassword,
                    $storeId
                );

                if (!mysqli_stmt_execute($stmtUser)) {
                    throw new Exception("Unable to create account.");
                }

                mysqli_stmt_close($stmtUser);

                // Save both the store and user together.
                mysqli_commit($conn);

                $registered = true;

                // Clear the form after successful registration.
                $fullname = "";
                $email = "";
                $storeName = "";
            }
        } catch (Throwable $e) {
            mysqli_rollback($conn);

            // Log technical details privately instead of showing them to users.
            error_log("StockIT registration error: " . $e->getMessage());

            $errors[] = "We couldn't create your account. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | StockIT</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="register.css">
</head>

<body class="stockit-register-page">

<main class="stockit-register-card">

    <section class="stockit-register-content">

        <header class="stockit-register-heading">
            <span class="stockit-register-kicker">
                GET STARTED WITH STOCKIT
            </span>

            <h1>Create your account.</h1>

            <p>
                Set up your store workspace to manage inventory,
                record sales, and track your business performance.
            </p>
        </header>

        <?php if ($registered): ?>
            <div class="stockit-register-alert success" role="status">
                <span class="stockit-register-alert-icon">✓</span>

                <div>
                    <strong>Account created successfully!</strong>
                    <p>Your store workspace is ready. You can now sign in.</p>

                    <a href="login.php" class="stockit-success-link">
                        Continue to sign in →
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="stockit-register-alert error" role="alert">
                <span class="stockit-register-alert-icon">!</span>

                <div>
                    <strong>Please check the following:</strong>

                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li>
                                <?= htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$registered): ?>
        <form action="register.php" method="post" class="stockit-register-form">

            <div class="stockit-register-field">
                <label for="fullname">Full name</label>

                <input
                    type="text"
                    id="fullname"
                    name="fullname"
                    placeholder="Enter your full name"
                    value="<?= htmlspecialchars($fullname, ENT_QUOTES, "UTF-8") ?>"
                    autocomplete="name"
                    maxlength="100"
                    required
                >
            </div>

            <div class="stockit-register-field">
                <label for="email">Email address</label>

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

            <div class="stockit-register-field">
                <label for="storename">Store name</label>

                <input
                    type="text"
                    id="storename"
                    name="storename"
                    placeholder="Enter your store name"
                    value="<?= htmlspecialchars($storeName, ENT_QUOTES, "UTF-8") ?>"
                    maxlength="100"
                    required
                >

                <small class="stockit-field-hint">
                    This name identifies your store workspace.
                </small>
            </div>

            <div class="stockit-register-password-row">

                <div class="stockit-register-field">
                    <label for="password">Password</label>

                    <div class="stockit-register-password-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="At least 8 characters"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="stockit-register-toggle"
                            data-password="password"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            Show
                        </button>
                    </div>
                </div>

                <div class="stockit-register-field">
                    <label for="repeatpassword">Confirm password</label>

                    <div class="stockit-register-password-wrap">
                        <input
                            type="password"
                            id="repeatpassword"
                            name="repeatpassword"
                            placeholder="Re-enter password"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="stockit-register-toggle"
                            data-password="repeatpassword"
                            aria-label="Show confirm password"
                            aria-pressed="false"
                        >
                            Show
                        </button>
                    </div>
                </div>

            </div>

            <button
                type="submit"
                name="submit"
                value="register"
                class="stockit-register-submit"
            >
                <span>Create StockIT account</span>

                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path
                        d="M5 12h14m-6-6 6 6-6 6"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </button>

        </form>
        <?php endif; ?>

        <div class="stockit-register-security">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path
                    d="M12 3 19 6v5c0 4.4-2.8 7.8-7 10-4.2-2.2-7-5.6-7-10V6l7-3Z"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linejoin="round"
                />
                <path
                    d="m9 12 2 2 4-4"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>

            <span>Your password is securely hashed before being stored.</span>
        </div>

        <div class="stockit-register-footer">
            <span>Already using StockIT?</span>
            <a href="login.php">Sign in to your account</a>
        </div>

        <p class="stockit-register-copyright">
            © <?= date("Y") ?> StockIT. All rights reserved.
        </p>

    </section>

</main>

<script>
    // Toggle visibility of each password field independently.
    document.querySelectorAll(".stockit-register-toggle").forEach(function (button) {
        button.addEventListener("click", function () {
            const input = document.getElementById(button.dataset.password);
            const showPassword = input.type === "password";

            input.type = showPassword ? "text" : "password";
            button.textContent = showPassword ? "Hide" : "Show";
            button.setAttribute(
                "aria-label",
                showPassword ? "Hide password" : "Show password"
            );
            button.setAttribute("aria-pressed", String(showPassword));
        });
    });

    // Check that both passwords match before submitting.
    const registerForm = document.querySelector(".stockit-register-form");

    if (registerForm) {
        registerForm.addEventListener("submit", function (event) {
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("repeatpassword").value;

            if (password !== confirmPassword) {
                event.preventDefault();
                document.getElementById("repeatpassword").setCustomValidity(
                    "Your passwords do not match."
                );
                document.getElementById("repeatpassword").reportValidity();
            }
        });

        document.getElementById("repeatpassword").addEventListener("input", function () {
            this.setCustomValidity("");
        });
    }
</script>

</body>
</html>
