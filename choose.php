<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Just Chat</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap');
        
        :root {
            --bg: #f3e6d0;        /* Creamy beige background */
            --text: #8b4000;      /* Deep burnt orange text */
            --accent: #ff6f31;    /* Bright orange */
            --button: #e0561c;    /* Slightly darker orange for button */
            --input-bg: #fff4e6;  /* Soft peach for inputs */
            --border: #c85e0d;    /* Dark orange border */
            --link: #9c27b0;      /* Purple for links */
            --scanline: rgba(139, 64, 0, 0.05);
        }
        
        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Press Start 2P', cursive;
            font-size: 16px;
            text-align: center;
            background-image: 
                radial-gradient(circle at 1px 1px, #d9c4a5 1px, transparent 0),
                radial-gradient(circle at 1px 1px, #d9c4a5 1px, var(--bg) 0);
            background-size: 20px 20px, 20px 20px;
            background-position: 0 0, 10px 10px;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        /* CRT Scanline Effect */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(
                0deg,
                var(--scanline) 0px,
                var(--scanline) 1px,
                transparent 1px,
                transparent 2px
            );
            background-size: 100% 2px;
            pointer-events: none;
            z-index: 1000;
            animation: scan 8s linear infinite;
        }
        
        @keyframes scan {
            0% { transform: translateY(0); }
            100% { transform: translateY(-100%); }
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 10;
        }
        
        .logo {
            width: 200px;
            height: auto;
            margin: 20px auto;
            border: 4px solid var(--border);
            border-radius: 0;
            box-shadow: -8px 8px 0 var(--accent);
            transition: all 0.3s;
        }
        
        .logo:hover {
            transform: translate(2px, -2px);
            box-shadow: -6px 6px 0 var(--accent);
        }
        
        h1 {
            color: var(--text);
            text-shadow: 
                4px 4px 0 var(--accent),
                6px 6px 0 rgba(0, 0, 0, 0.2);
            font-size: 2.5em;
            margin: 20px 0;
            letter-spacing: 3px;
            animation: pulse 2s infinite alternate;
        }
        
        @keyframes pulse {
            from { transform: scale(1); }
            to { transform: scale(1.05); }
        }
        
        .warning-box {
            background-color: var(--input-bg);
            border: 4px solid var(--border);
            border-radius: 0;
            padding: 20px;
            margin: 30px auto;
            max-width: 90%;
            box-shadow: -8px 8px 0 var(--accent);
            position: relative;
            text-align: left;
            font-size: 0.9em;
            line-height: 1.6;
        }
        
        .warning-box::before {
            content: "! WARNING !";
            position: absolute;
            top: -12px;
            left: 20px;
            background-color: var(--bg);
            padding: 0 10px;
            font-size: 0.8em;
            color: var(--accent);
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 15px;
            background-color: var(--button);
            color: white;
            text-decoration: none;
            border: none;
            font-family: 'Press Start 2P', cursive;
            font-size: 1em;
            cursor: pointer;
            border-radius: 0;
            box-shadow: -6px 6px 0 var(--border);
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        
        .btn:hover {
            transform: translate(-2px, 2px);
            box-shadow: -4px 4px 0 var(--border);
            background-color: #f36f31;
        }
        
        .btn::after {
            content: "";
            position: absolute;
            top: -50%;
            left: -60%;
            width: 50%;
            height: 200%;
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(30deg);
            transition: all 0.3s;
        }
        
        .btn:hover::after {
            left: 120%;
        }
        
        .pixel-corners {
            clip-path: 
                polygon(
                    0% 5px, 5px 5px, 5px 0%, calc(100% - 5px) 0%, 
                    calc(100% - 5px) 5px, 100% 5px, 100% calc(100% - 5px), 
                    calc(100% - 5px) calc(100% - 5px), calc(100% - 5px) 100%, 
                    5px 100%, 5px calc(100% - 5px), 0% calc(100% - 5px)
                );
        }
        
        /* Floating orange pixels */
        .pixel {
            position: absolute;
            width: 8px;
            height: 8px;
            background-color: var(--accent);
            opacity: 0;
            animation: float-pixel 10s infinite;
        }
        
        @keyframes float-pixel {
            0% { transform: translate(0, 0); opacity: 0; }
            10% { opacity: 0.7; }
            90% { opacity: 0.7; }
            100% { transform: translate(random(200) - 100px, random(200) - 100px); opacity: 0; }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            h1 {
                font-size: 1.8em;
            }
            
            .btn {
                padding: 12px 24px;
                font-size: 0.9em;
            }
            
            .warning-box {
                padding: 15px;
                font-size: 0.8em;
            }
        }
    </style>
</head>
<body>
    <!-- Floating pixels -->
    <div id="pixels-container"></div>
    
    <div class="container">
        <img src="logo.png" alt="Chat Logo" class="logo pixel-corners">
        
        <h1>JUST CHAT</h1>
        
        <div class="warning-box pixel-corners">
            WELCOME TO OUR CHAT SYSTEM!<br><br>
            PLEASE FOLLOW THESE RULES:<br>
            • NO ABUSE OR HARASSMENT<br>
            • NO ILLEGAL CONTENT<br>
            • NO SPAMMING<br>
            VIOLATORS WILL BE BANNED!
        </div>
        
        <div style="margin: 30px 0;">
            <a href="@@cht" class="btn pixel-corners">ENTER CHAT</a>
            <a href="logout.php" class="btn pixel-corners" style="background-color: var(--link);">LOG OUT</a>
        </div>
        
        <div style="font-size: 0.8em; margin-top: 40px; color: var(--text);">
            SYSTEM STATUS: <span style="color: var(--button);">ONLINE</span> | 

			SERVER: <span style="color: var(--button);">192.168.1.100</span> |
			       
            VERSION: <span style="color: var(--button);">2.4.8</span>
        </div>
    </div>
    
    <script>
        // Create floating pixels
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('pixels-container');
            for (let i = 0; i < 20; i++) {
                const pixel = document.createElement('div');
                pixel.classList.add('pixel');
                pixel.style.left = Math.random() * 100 + 'vw';
                pixel.style.top = Math.random() * 100 + 'vh';
                pixel.style.animationDelay = Math.random() * 10 + 's';
                pixel.style.animationDuration = 5 + Math.random() * 10 + 's';
                container.appendChild(pixel);
            }
        });
        
        // Add keypress sound effect (placeholder)
        document.addEventListener('keydown', function() {
            // In a real implementation, you'd play a sound here
        });
    </script>
</body>
</html>