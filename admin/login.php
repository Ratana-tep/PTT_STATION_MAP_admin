<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Check if user is active
        if ($row['status'] != 'active') {
            echo "<div class='alert alert-danger'>Your account is inactive. Please contact the administrator.</div>";
        } else {
            // Verify the hashed password
            if (password_verify($password, $row['password'])) {
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $row['role']; // Store the role in the session
                $_SESSION['login_time'] = time(); // Store the login time

                if ($row['role'] == 'admin') {
                    // Redirect to admin_dashboard.php if the user is admin
                    header("Location: admin_dashboard.php");
                } else {
                    // Redirect to index.php for regular users
                    header("Location: index.php");
                }
                exit();
            } else {
                echo "<div class='alert alert-danger'>Invalid username or password.</div>";
            }
        }
    } else {
        echo "<div class='alert alert-danger'>Invalid username or password.</div>";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login {
            height: 100vh;
            width: 100%;
            background: radial-gradient(#653d84, #332042);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login_box {
            background: #fff;
            border-radius: 10px;
            box-shadow: 1px 4px 22px -8px #0004;
            display: flex;
            overflow: hidden;
            flex-direction: column;
            max-width: 100%;
            width: 500px;
        }
        .login_box .left, .login_box .right {
            padding: 25px;
        }
        .left {
            background: linear-gradient(-45deg, #dcd7e0, #fff);
        }
        .left .contact {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        .left h3 {
            text-align: center;
            margin-bottom: 40px;
        }
        .left input {
            border: none;
            margin: 15px 0px;
            border-bottom: 1px solid #4f30677d;
            padding: 7px 9px;
            width: 100%;
            background: transparent;
            font-weight: 600;
            font-size: 14px;
        }
        .submit {
            border: none;
            padding: 15px 70px;
            border-radius: 8px;
            display: block;
            margin: auto;
            margin-top: 20px;
            background: #583672;
            color: #fff;
            font-weight: bold;
            box-shadow: 0px 9px 15px -11px rgba(88, 54, 114, 1);
        }
        .right {
            background: linear-gradient(212.38deg, rgba(242, 57, 127, 0.7) 0%, rgba(175, 70, 189, 0.71) 100%), url(https://static.seattletimes.com/wp-content/uploads/2019/01/web-typing-ergonomics-1020x680.jpg);
            background-size: cover;
            background-position: center;
            color: #fff;
            text-align: center;
            padding: 50px 20px;
        }
        .right .right-inductor img {
            width: 70px;
        }
        .right-text {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        @media (min-width: 768px) {
            .login_box {
                flex-direction: row;
                max-width: 900px;
            }
            .login_box .left, .login_box .right {
                width: 50%;
            }
        }
    </style>
</head>
<body>
    <section class="login">
        <div class="login_box">
            <div class="left">
                <div class="contact">
                    <form action="login.php" method="post">
                        <h3>SIGN IN</h3>
                        <input type="text" name="username" placeholder="USERNAME" required>
                        <input type="password" name="password" placeholder="PASSWORD" required>
                        <button type="submit" class="submit">LET'S GO</button>
                    </form>
                </div>
            </div>
            <div class="right">
                <div class="right-text">
                    <h2>LONYX</h2>
                    <h5>A UX BASED CREATIVE AGENCY</h5>
                </div>
                <div class="right-inductor"><img src="https://lh3.googleusercontent.com/fife/ABSRlIoGiXn2r0SBm7bjFHea6iCUOyY0N2SrvhNUT-orJfyGNRSMO2vfqar3R-xs5Z4xbeqYwrEMq2FXKGXm-l_H6QAlwCBk9uceKBfG-FjacfftM0WM_aoUC_oxRSXXYspQE3tCMHGvMBlb2K1NAdU6qWv3VAQAPdCo8VwTgdnyWv08CmeZ8hX_6Ty8FzetXYKnfXb0CTEFQOVF4p3R58LksVUd73FU6564OsrJt918LPEwqIPAPQ4dMgiH73sgLXnDndUDCdLSDHMSirr4uUaqbiWQq-X1SNdkh-3jzjhW4keeNt1TgQHSrzW3maYO3ryueQzYoMEhts8MP8HH5gs2NkCar9cr_guunglU7Zqaede4cLFhsCZWBLVHY4cKHgk8SzfH_0Rn3St2AQen9MaiT38L5QXsaq6zFMuGiT8M2Md50eS0JdRTdlWLJApbgAUqI3zltUXce-MaCrDtp_UiI6x3IR4fEZiCo0XDyoAesFjXZg9cIuSsLTiKkSAGzzledJU3crgSHjAIycQN2PH2_dBIa3ibAJLphqq6zLh0qiQn_dHh83ru2y7MgxRU85ithgjdIk3PgplREbW9_PLv5j9juYc1WXFNW9ML80UlTaC9D2rP3i80zESJJY56faKsA5GVCIFiUtc3EewSM_C0bkJSMiobIWiXFz7pMcadgZlweUdjBcjvaepHBe8wou0ZtDM9TKom0hs_nx_AKy0dnXGNWI1qftTjAg=w1920-h979-ft" alt=""></div>
            </div>
        </div>
    </section>
</body>
</html>
