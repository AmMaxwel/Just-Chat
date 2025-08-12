<?php
require('db.php');
session_start();

// Handle registration
if (isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = $_POST['pss']; // Form field is named 'pss'
    $question = $_POST['question'];
    $answer = $_POST['answer'];

    // Validation: Check required fields
    if (empty($username) || empty($password) || empty($question) || empty($answer)) {
        $error = "All fields are required.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters long.";
    } elseif (strlen($username) > 30) {
        $error = "Username cannot exceed 30 characters.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // In registration.php - Generate and store the code
		$recovery_code = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);	   // Store in database using simple SHA256 hash (not password_hash)
	   
        $trn_date = date("Y-m-d H:i:s");

        // Use prepared statement with correct column names
        $stmt = $conn->prepare("INSERT INTO users (username, pss, trn_date, question, answer, recovery_code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $hashed_password, $trn_date, $question, $answer, $recovery_code);

if ($stmt->execute()) {
    // Display the recovery code in full-page retro style
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>RECOVERY CODE - JUST CHAT</title>
        <style>
            @font-face {
                font-family: 'Pixel';
                src: url('https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap');
            }
            
            body {
                background-color: #000;
                margin: 0;
                padding: 0;
                height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                font-family: 'Press Start 2P', cursive;
                color: white;
                text-align: center;
                overflow: hidden;
                position: relative;
            }
            
            .scanlines {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: repeating-linear-gradient(
                    to bottom,
                    transparent 0%,
                    rgba(0, 255, 0, 0.05) 1px,
                    transparent 2px
                );
                pointer-events: none;
                z-index: 1;
            }
            
            .glitch-effect {
                position: relative;
                animation: glitch 2s infinite linear alternate;
            }
            
            @keyframes glitch {
                0% { transform: translate(0); }
                20% { transform: translate(-2px, 2px); }
                40% { transform: translate(-2px, -2px); }
                60% { transform: translate(2px, 2px); }
                80% { transform: translate(2px, -2px); }
                100% { transform: translate(0); }
            }
            
            .container {
                position: relative;
                z-index: 2;
                padding: 20px;
                border: 4px solid #00ff00;
                box-shadow: 0 0 15px #00ff00;
                max-width: 90%;
                margin: 0 auto;
            }
            
            h1 {
                font-size: clamp(12px, 4vw, 24px);
                margin-bottom: 30px;
                color: white;
                text-shadow: 0 0 5px #00ff00;
            }
            
            .code-display {
                font-size: clamp(20px, 10vw, 60px);
                letter-spacing: 5px;
                color: #00ff00;
                background-color: #111;
                padding: 20px;
                border: 3px solid #00ff00;
                margin: 20px 0;
                text-shadow: 0 0 10px #00ff00;
                animation: pulse 1.5s infinite alternate;
            }
            
            @keyframes pulse {
                from { opacity: 0.8; }
                to { opacity: 1; }
            }
            
            .warning {
                font-size: clamp(8px, 2.5vw, 14px);
                margin: 30px 0;
                line-height: 1.6;
                max-width: 500px;
            }
            
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: #000;
                color: #00ff00;
                border: 2px solid #00ff00;
                font-family: 'Press Start 2P', cursive;
                font-size: clamp(8px, 2.5vw, 14px);
                text-decoration: none;
                margin-top: 20px;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .btn:hover {
                background: #00ff00;
                color: #000;
                box-shadow: 0 0 15px #00ff00;
            }
            
            .pixel-corner {
                position: absolute;
                width: 20px;
                height: 20px;
                border: 3px solid #00ff00;
            }
            
            .top-left {
                top: -10px;
                left: -10px;
                border-right: none;
                border-bottom: none;
            }
            
            .top-right {
                top: -10px;
                right: -10px;
                border-left: none;
                border-bottom: none;
            }
            
            .bottom-left {
                bottom: -10px;
                left: -10px;
                border-right: none;
                border-top: none;
            }
            
            .bottom-right {
                bottom: -10px;
                right: -10px;
                border-left: none;
                border-top: none;
            }
            
            @media (max-width: 600px) {
                .container {
                    padding: 15px;
                }
                
                .code-display {
                    padding: 15px;
                    font-size: 24px;
                }
            }
        </style>
    </head>
    <body>
        <div class='scanlines'></div>
        <div class='container'>
            <div class='pixel-corner top-left'></div>
            <div class='pixel-corner top-right'></div>
            <div class='pixel-corner bottom-left'></div>
            <div class='pixel-corner bottom-right'></div>
            
            <h1 class='glitch-effect'>SECURE RECOVERY CODE</h1>
            <div class='code-display'>$recovery_code</div>
            <div class='warning'>
                WARNING: THIS CODE WILL ONLY BE SHOWN ONCE.<br>
                WRITE IT DOWN AND STORE IT SECURELY.<br>
                YOU WILL NEED IT TO RECOVER YOUR ACCOUNT.
            </div>
            <a href='index.php' class='btn'>CONTINUE TO LOGIN</a>
        </div>
    </body>
    </html>";
    exit();

        } else {
            $error = "Registration failed. Please try again. Error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Register - JUST CHAT</title>
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
            --success: #2e8b57;   /* Green for success */
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
            overflow-y: auto;
            overflow-x: hidden;
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

        .container {
            max-width: 100%;
            width: 90%;
            margin: 20px auto;
            padding: 20px;
            z-index: 10;
            position: relative;
        }

        .logo {
            width: 180px;
            height: auto;
            margin: 20px auto;
            border: 4px solid var(--border);
            border-radius: 0;
            box-shadow: -4px 4px 0 var(--accent);
        }

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
            from { transform: scale(1); }
            to { transform: scale(1.05); }
        }

        .form {
            background-color: var(--input-bg);
            border: 4px solid var(--border);
            border-radius: 0;
            padding: 25px;
            margin: 30px auto;
            max-width: 450px;
            box-shadow: -6px 6px 0 var(--accent);
        }

        .form h1 {
            margin-bottom: 20px;
            color: var(--text);
            font-size: 18px;
        }

        input[type="text"],
        input[type="password"],
        select {
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

        select {
            cursor: pointer;
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

        /* Error Message */
        .form-error {
            max-width: 450px;
            margin: 30px auto;
            padding: 20px;
            background-color: rgba(220, 20, 60, 0.1);
            border: 3px solid #dc143c;
            border-radius: 0;
            color: #8b0000;
            font-size: 14px;
            box-shadow: -4px 4px 0 var(--accent);
        }

        /* Success Message */
        .form-success {
            max-width: 450px;
            margin: 30px auto;
            padding: 20px;
            background-color: rgba(46, 139, 87, 0.1);
            border: 3px solid var(--success);
            border-radius: 0;
            color: var(--success);
            font-size: 14px;
            box-shadow: -4px 4px 0 var(--accent);
        }

        .form-success a {
            color: var(--success);
            display: inline-block;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .container { width: 95%; padding: 15px; }
            .brand { font-size: 24px; }
            .form { padding: 20px; }
            .logo { width: 150px; }
        }

        @media (max-width: 480px) {
            body { font-size: 12px; }
            .brand { font-size: 20px; letter-spacing: 1px; }
            .form { padding: 18px; }
            input[type="text"], input[type="password"], select {
                padding: 12px;
                font-size: 13px;
            }
            input[type="submit"] {
                font-size: 14px;
                padding: 10px 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="logo.png" alt="Logo" class="logo" />
        <h1 class="brand">JUST CHAT</h1>

        <div class="form">
            <h1>Register</h1>
            <?php if (isset($error)): ?>
                <div class="form-error">
                    <h3><?php echo htmlspecialchars($error); ?></h3>
                </div>
            <?php else: ?>
                <form action="" method="post" name="registration">
					<input type="text" name="username" placeholder="USERNAME" pattern=".*" title="Any characters are allowed." required />
                    <input type="password" name="pss" placeholder="PASSWORD" minlength="6" required />

                    <select name="question" required>
                        <option value="">--- Choose Security Question ---</option>
                        <option>what is your favorite game</option>
                        <option>what is your favorite color</option>
                        <option>who is your favorite singer</option>
                        <option>who is your favorite character</option>
                    </select>

                    <input type="text" name="answer" placeholder="ANSWER" required />

                    <input type="submit" name="submit" value="Register" />
                </form>
                <p>Already have an account? <a href="index.php">Log In</a></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>