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

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $errors = array();

            if (empty($fullname) OR empty($email) OR empty($password) OR empty($confirmPassword)) {
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
                $sql = "INSERT INTO users (FULL_NAME, EMAIL, PASSWORD) VALUES(?, ?, ?)";
                $stmt = mysqli_stmt_init($conn);
                $prepareStmt = mysqli_stmt_prepare($stmt, $sql);
                if ($prepareStmt) {
                    mysqli_stmt_bind_param($stmt, "sss", $fullname, $email, $hashedPassword);
                    mysqli_stmt_execute($stmt);
                    echo "<div class='alert alert-success'>Registered Successfully</div>";
                } else {
                    die("Something went wrong");
                }

            }
        }
        ?>

        <form action="register.php" method="post">
            <div class="form-group">
                <input type="text" class="form-control" name="fullname" placeholder="Fullname:">
            </div>
            <div class="form-group">
                <input type="email" class="form-control" name="email" placeholder="Email:">
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="password" placeholder="Password:">
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="repeatpassword" placeholder="Confirm Password:">
            </div>
            <div class="form-btn">
                <input type="submit" class="btn btn-primary" value="Register" name="submit">
            </div>
        </form>
    </div>
    
</body>
</html>