<?php
require('db.php');
session_start();

$error = '';
$success = '';

if (isset($_POST['recover'])) {
    // Get and clean inputs
    $username = trim($_POST['username']);
    $recovery_code_input = trim($_POST['recovery_code']);
    $new_password = $_POST['new_password'];

    // Basic validation
    if (empty($username) || empty($recovery_code_input) || empty($new_password)) {
        $error = "ALL FIELDS ARE REQUIRED!";
    } elseif (strlen($new_password) < 6) {
        $error = "PASSWORD MUST BE AT LEAST 6 CHARACTERS!";
    } else {
        // Get stored hash
        $stmt = $conn->prepare("SELECT recovery_code FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "USER NOT FOUND!";
        } else {
            $user = $result->fetch_assoc();
            $stored_hash = $user['recovery_code'];
            
            // Hash the input exactly the same way
            $input_hash = hash('sha256', $recovery_code_input);
            
            // Compare hashes securely
            if ($recovery_code_input === $user['recovery_code']) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET pss = ? WHERE username = ?");
                $update_stmt->bind_param("ss", $hashed_password, $username);
                
                if ($update_stmt->execute()) {
                    $success = "PASSWORD UPDATED!<br>YOU CAN NOW <a href='index.php'>LOGIN</a>.";
                } else {
                    $error = "UPDATE FAILED! TRY AGAIN.";
                }
                $update_stmt->close();
            } else {
                $error = "INVALID RECOVERY CODE!";
                // Temporary debug output:
                echo "<div style='color:white;background:black;padding:10px;'>";
                echo "DEBUG INFO:<br>";
                echo "You entered: ".htmlspecialchars($recovery_code_input)."<br>";
                echo "Hashed input: ".$input_hash."<br>";
                echo "Stored hash: ".$stored_hash."<br>";
                echo "</div>";
            }
        }
        $stmt->close();
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PASSWORD RECOVERY - JUST CHAT</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #2a1a1a;        /* Warm dark brown */
            --text: #ffd8a8;     /* Warm cream */
            --accent: #ff7b25;   /* Warm orange */
            --error: #ff3d3d;     /* Warm red */
            --success: #4caf50;   /* Green */
            --input-bg: #3a2a2a;  /* Darker warm brown */
        }

        body {
            background-color: var(--bg);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Press Start 2P', cursive;
            color: var(--text);
            text-align: center;
            position: relative;
            background-image: 
                radial-gradient(circle at 1px 1px, #3a2a2a 1px, transparent 0),
                radial-gradient(circle at 1px 1px, #3a2a2a 1px, var(--bg) 0);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
        }

        .container {
            width: 90%;
            max-width: 400px;
            padding: 30px;
            border: 4px solid var(--accent);
            background-color: rgba(42, 26, 26, 0.9);
            box-shadow: 0 0 20px rgba(255, 123, 37, 0.5);
            margin: 20px;
            position: relative;
            z-index: 10;
        }

        h1 {
            font-size: 20px;
            margin-bottom: 30px;
            color: var(--accent);
            text-shadow: 0 0 5px rgba(255, 123, 37, 0.7);
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            background: var(--input-bg);
            border: 2px solid var(--accent);
            color: var(--text);
            font-family: 'Press Start 2P', cursive;
            font-size: 12px;
            outline: none;
        }

        input::placeholder {
            color: #a58a6e;
            opacity: 1;
        }

        input[type="submit"] {
            background: var(--accent);
            color: #2a1a1a;
            border: none;
            padding: 15px;
            font-family: 'Press Start 2P', cursive;
            font-size: 12px;
            margin-top: 20px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
        }

        input[type="submit"]:hover {
            background: #ff8c42;
            box-shadow: 0 0 15px var(--accent);
        }

        .error {
            color: var(--error);
            margin: 15px 0;
            font-size: 10px;
            line-height: 1.5;
            text-shadow: 0 0 3px var(--error);
        }

        .success {
            color: var(--success);
            margin: 15px 0;
            font-size: 10px;
            line-height: 1.5;
            text-shadow: 0 0 3px var(--success);
        }

        .back-link {
            display: block;
            margin-top: 20px;
            color: var(--accent);
            font-size: 10px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: #ff9a3d;
            text-shadow: 0 0 5px rgba(255, 154, 61, 0.7);
        }

        /* Pixel corners */
        .pixel-corner {
            position: absolute;
            width: 15px;
            height: 15px;
            border: 3px solid var(--accent);
        }
        .top-left { top: -8px; left: -8px; border-right: none; border-bottom: none; }
        .top-right { top: -8px; right: -8px; border-left: none; border-bottom: none; }
        .bottom-left { bottom: -8px; left: -8px; border-right: none; border-top: none; }
        .bottom-right { bottom: -8px; right: -8px; border-left: none; border-top: none; }

        @media (max-width: 480px) {
            h1 {
                font-size: 16px;
            }
            
            input[type="text"],
            input[type="password"] {
                padding: 10px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="pixel-corner top-left"></div>
        <div class="pixel-corner top-right"></div>
        <div class="pixel-corner bottom-left"></div>
        <div class="pixel-corner bottom-right"></div>
        
        <h1>PASSWORD RECOVERY</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php else: ?>
            <form method="post" action="">
                <input type="text" name="username" placeholder="USERNAME" required>
                <input type="text" name="recovery_code" placeholder="RECOVERY CODE" required>
                <input type="password" name="new_password" placeholder="NEW PASSWORD" required>
                <input type="submit" name="recover" value="RESET PASSWORD">
            </form>
        <?php endif; ?>
        
        <a href="index.php" class="back-link">← BACK TO LOGIN</a>
    </div>
</body>
</html>