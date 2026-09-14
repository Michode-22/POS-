<?php
//check if user log in
session_start();
if (isset($_SESSION["user"])) {
    header("Location: index.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="container">
        <?php
        //to check if user click submit
        if (isset($_POST["submit"])) {
            $email = $_POST["email"];
            $password = $_POST["password"];
            //database connection
            require_once "dbase.php";
            $sql = "SELECT * FROM users WHERE EMAIL='$email'";
            $result = mysqli_query($conn, $sql);
            $user = mysqli_fetch_array($result, MYSQLI_ASSOC);

            //return false if email does not exist in the database
            if ($user) {
                //check if user passward match in hashedpassword in the database
                if(password_verify($password, $user["PASSWORD"])) {
                    session_start();
                    $_SESSION["user"] = "Sarinas lang masarap";
                    header("Location: index.php");
                } else {
                    echo "<div class='alert alert-danger'>Password is incorrect</div>";
                }
                
                
            } else {
                echo "<div class='alert alert-danger'>Email Does Not Exist</div>";
            }
        }
        ?>



        <form action="login.php" method="post">
            <div class="form-group">
                <input type="text" class="form-control" name="email" placeholder="Email:">
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="password" placeholder="Password:">
            </div>
            <div class="form-btn">
                <input type="submit" class="btn btn-primary" name="submit" value="submit">
            </div>
        </form>
    </div>
    
</body>
</html>