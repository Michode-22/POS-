<?php
//check if user log in
session_start();
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php
        if (isset($_POST["submit"])) {
            $fullname = $_POST["fullname"];
            $email = $_POST["email"];
            $password = $_POST["password"];
            $confirmPassword = $_POST["repeatpassword"];
            $storeName = $_POST["storename"];

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $errors = array();

            if (empty($fullname) OR empty($email) OR empty($password) OR empty($confirmPassword) OR empty($storeName)) {
                array_push($errors, "All field must be fill");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                array_push($errors, "Please enter a valid email");
            }

            if (strlen($password) < 8){
                array_push($errors, "Password must be atleast 8 character long");
            }
            
            if ($confirmPassword != $password) {
                array_push($errors, "Confirm password is not match to your password");
            }

            require_once "dbase.php";
            $sql = "SELECT * FROM users WHERE EMAIL = '$email'";
            $result = mysqli_query($conn,$sql);
            $countRow = mysqli_num_rows($result);
            if ($countRow > 0) {
                array_push($errors, "Email already exist");
            }
            
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    echo "<div class='alert alert-danger'>$error</div>";
                }
            } else {
                
                mysqli_begin_transaction($conn);

                try {
                    // Create the store first
                    $sqlStore = "INSERT INTO stores (store_name) VALUES (?)";

                    $stmtStore = mysqli_stmt_init($conn);

                    if (!mysqli_stmt_prepare($stmtStore, $sqlStore)) {
                        throw new Exception("Could not create store.");
                    }

                    mysqli_stmt_bind_param(
                        $stmtStore,
                        "s",
                        $storeName
                    );

                    mysqli_stmt_execute($stmtStore);

                    // Get the newly created store ID
                    $storeId = mysqli_insert_id($conn);


                    // Create the user and connect it to the store
                    $sqlUser = "
                        INSERT INTO users
                        (FULL_NAME, EMAIL, PASSWORD, store_id)
                        VALUES (?, ?, ?, ?)
                    ";

                    $stmtUser = mysqli_stmt_init($conn);

                    if (!mysqli_stmt_prepare($stmtUser, $sqlUser)) {
                        throw new Exception("Could not create user.");
                    }

                    mysqli_stmt_bind_param(
                        $stmtUser,
                        "sssi",
                        $fullname,
                        $email,
                        $hashedPassword,
                        $storeId
                    );

                    mysqli_stmt_execute($stmtUser);


                    // Save both changes
                    mysqli_commit($conn);

                    echo "<div class='alert alert-success'>
                            Registered Successfully
                        </div>";

                } catch (Exception $e) {

                    // Undo everything if something fails
                    mysqli_rollback($conn);

                    echo "<div class='alert alert-danger'>
                            Registration failed.
                        </div>";
                }

            }
        }
        ?>

        <form action="register.php" method="post">
            <div class="form-group">
                <input
                    type="text"
                    class="form-control"
                    name="fullname"
                    placeholder="Fullname:">
            </div>

            <div class="form-group">
                <input
                    type="email"
                    class="form-control"
                    name="email"
                    placeholder="Email:">
            </div>

            <div class="form-group">
                <input
                    type="password"
                    class="form-control"
                    name="password"
                    placeholder="Password:">
            </div>

            <div class="form-group">
                <input
                    type="password"
                    class="form-control"
                    name="repeatpassword"
                    placeholder="Confirm Password:">
            </div>

            <div class="form-group">
                <input
                    type="text"
                    class="form-control"
                    name="storename"
                    placeholder="Store Name:">
            </div>

            <div class="form-btn">
                <input type="submit" class="btn btn-primary" value="Register" name="submit">
            </div>
        </form>
    </div>
    
</body>
</html>