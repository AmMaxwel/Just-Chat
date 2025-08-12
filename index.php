<?php
require('db.php');
session_start();

// Handle login
if (isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = $_POST['pss']; // The plain text password from the form

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
        // Verify the password against the hashed version
        if (password_verify($password, $row['pss'])) {
            $_SESSION['username'] = $username;
            $_SESSION['fullname'] = $row['fullname'];
            header("Location: choose.php");
            exit();
        } else {
            echo "<div class='form-error'>
                    <h3>Username or password is incorrect.</h3>
                    <a href='index.php'>← Back to Login</a>
                  </div>";
        }
    } else {
        echo "<div class='form-error'>
                <h3>Username or password is incorrect.</h3>
                <a href='index.php'>← Back to Login</a>
              </div>";
    }
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Login - JUST CHAT</title>
    <!-- Retro Pixel Font -->
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        /* Warm Retro Color Palette */
        :root {
            --bg: #f3e6d0;        /* Creamy beige background */
            --text: #8b4000;      /* Deep burnt orange text */
            --accent: #ff6f31;    /* Bright orange */
            --button: #e0561c;    /* Slightly darker orange for button */
            --input-bg: #fff4e6;  /* Soft peach for inputs */
            --border: #c85e0d;    /* Dark orange border */
            --link: #9c27b0;      /* Purple for links */
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Press Start 2P', cursive;
            font-size: 14px;
            text-align: center;
            background-image: 
                radial-gradient(circle at 1px 1px, #d9c4a5 1px, transparent 0),
                radial-gradient(circle at 1px 1px, #d9c4a5 1px, var(--bg) 0);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
            min-height: 100vh;
            position: relative;
            overflow-y: auto;     /* ✅ Allows vertical scrolling */
            overflow-x: hidden;   /* Prevents horizontal scroll */
        }

        /* CRT Scanline Effect */
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
            background: repeating-linear-gradient(
                0deg,
                rgba(0,0,0,0.03) 0px,
                rgba(0,0,0,0.03) 1px,
                transparent 1px,
                transparent 2px
            );
            z-index: 1;
            opacity: 0.3;
        }

        /* Container */
        .container {
            max-width: 100%;
            width: 90%;
            margin: 20px auto;
            padding: 20px;
            z-index: 10;
            position: relative;
        }

        /* Logo */
        .logo {
            width: 180px;
            height: auto;
            margin: 20px auto;
            border: 4px solid var(--border);
            border-radius: 0;
            box-shadow: -4px 4px 0 var(--accent);
        }

        /* JUST CHAT Title */
        .brand {
            font-size: 28px;
            font-weight: bold;
            color: var(--accent);
            text-shadow: -2px 2px 0 var(--text), 2px -2px 0 var(--text);
            margin: 20px 0;
            letter-spacing: 2px;
            text-transform: uppercase;
            animation: pulse 2s infinite alternate;
        }

        @keyframes pulse {
            from {
                transform: scale(1);
            }
            to {
                transform: scale(1.05);
            }
        }

        /* Form */
        .form {
            background-color: var(--input-bg);
            border: 4px solid var(--border);
            border-radius: 0;
            padding: 25px;
            margin: 30px auto;
            max-width: 400px;
            box-shadow: -6px 6px 0 var(--accent);
        }

        .form h1 {
            margin-bottom: 20px;
            color: var(--text);
            font-size: 18px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px;
            margin: 12px 0;
            border: 3px solid var(--border);
            background-color: white;
            color: var(--text);
            font-family: 'Press Start 2P', cursive;
            font-size: 14px;
            border-radius: 0;
            box-shadow: inset -2px 2px 0 var(--accent);
        }

        input[type="submit"] {
            background-color: var(--button);
            color: white;
            border: 4px solid #000;
            padding: 12px 24px;
            font-family: 'Press Start 2P', cursive;
            font-size: 16px;
            cursor: pointer;
            margin-top: 15px;
            border-radius: 0;
            box-shadow: -4px 4px 0 #000;
            transition: all 0.1s;
        }

        input[type="submit"]:hover {
            transform: translate(2px, -2px);
            box-shadow: -2px 2px 0 #000;
        }

        .form p {
            margin: 15px 0;
            font-size: 13px;
        }

        a {
            color: var(--link);
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            color: #6a0dad;
        }

        /* Error Message Styling */
        .form-error {
            max-width: 400px;
            margin: 30px auto;
            padding: 20px;
            background-color: rgba(220, 20, 60, 0.1);
            border: 3px solid #dc143c;
            border-radius: 0;
            color: #8b0000;
            font-size: 14px;
            box-shadow: -4px 4px 0 var(--accent);
        }

        .form-error a {
            color: #8b0000;
            display: inline-block;
            margin-top: 10px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 15px;
            }
            .brand {
                font-size: 24px;
            }
            .form {
                padding: 20px;
            }
            input[type="submit"] {
                font-size: 14px;
                padding: 10px 18px;
            }
            .logo {
                width: 150px;
            }
        }

        @media (max-width: 480px) {
            body {
                font-size: 12px;
            }
            .brand {
                font-size: 20px;
                letter-spacing: 1px;
            }
            .form {
                padding: 18px;
            }
            input[type="number"],
            input[type="password"] {
                padding: 12px;
                font-size: 13px;
            }
            input[type="submit"] {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="logo.png" alt="Logo" class="logo" />
        
        <!-- Prominent JUST CHAT Branding -->
        <h1 class="brand">JUST CHAT</h1>

        <div class="form">
            <h1>Log In</h1>
            <form action="" method="post" name="login">
                <input type="text" name="username" placeholder="USERNAME" pattern=".*" title="Any characters are allowed." required />
                <input type="password" name="pss" placeholder="PASSWORD" required />
                <input name="submit" type="submit" value="Login" />
            </form>
            <p>Not registered yet? <a href='registration.php'>Register Here</a></p>
            <p>
                Forgot Password?
				<br>
                <a href="pwordrecover.php">
                   Click Here
                </a>
            </p>
        </div>
    </div>

    <?php } ?>
</body>
</html>