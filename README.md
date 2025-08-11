# Just Chat 💬

A retro-styled, real-time chat web application built with PHP, MySQL, and JavaScript.  
"**Just Chat**" brings back the nostalgic feel of 90s computer chatrooms with a modern twist — including **mentions, reactions, notifications, image uploads, and more** — all wrapped in a warm, pixel-art-inspired design.

🎯 **Live, social, and fun — just like chatting should be.**

---

## 🎮 Features

- ✅ **Retro Aesthetic**  
  Pixel font (`Press Start 2P`), CRT scanlines, warm color palette, and game-like UI.

- ✅ **Real-Time Messaging**  
  Messages auto-refresh every 2 seconds — no page reload needed.

- ✅ **Rich Media Support**  
  Users can upload **photos** directly in chat. Click to **enlarge**, click outside to close.

- ✅ **@Mentions with Suggestions**  
  Type `@` to see a dropdown of users — just like Facebook or Twitter.

- ✅ **Emoji Reactions**  
  React to messages with ❤️, 👍, 😂 — instantly visible.

- ✅ **Smart Notifications**  
  - 🔔 Get notified when someone **mentions you**
  - 🔔 Get notified when someone **reacts to your message**
  - 🔴 Red dot badge (Facebook-style) — disappears when viewed

- ✅ **Click to Jump**  
  Click any notification to **scroll directly to the message** with a smooth highlight effect.

- ✅ **Sound Effects**  
  - 🔊 Play sound when new message arrives
  - 🔊 Play retro "click" sound when sending a message

- ✅ **Send Button with Icon & Glow**  
  - 📤 Send button uses a paper-plane emoji
  - 💡 Textarea glows when typing
  - 🎮 Button has press-down animation

- ✅ **Responsive Design**  
  Works perfectly on desktop, tablet, and mobile.

- ✅ **All-in-One File**  
  Entire chat logic runs in a single `index.php` — easy to deploy and maintain.

---

## 🛠️ Tech Stack

- **Frontend**: HTML, CSS, JavaScript
- **Styling**: Retro pixel design with scanlines and warm tones
- **Backend**: PHP (sessions, file upload, MySQL)
- **Database**: MariaDB/MySQL with tables: `users`, `messages`, `reactions`, `notifications`
- **UI Enhancements**: Emoji picker (optional), dynamic loading, smooth animations

---

## 📸 Screenshots

> *Coming soon — add screenshots of your app in action!*

---

## 🚀 How to Run

1. **Install XAMPP** (or any PHP/MySQL server)
2. Place project in `htdocs/`
3. Import database schema via phpMyAdmin
4. Create uploads folder: `uploads/` (make writable)
5. Visit `http://localhost/your-folder/index.php`
6. Log in and start chatting!

---

## 🗂️ Database Setup

Run these SQL commands in phpMyAdmin:

```sql
-- Users table (existing)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    pss VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    message TEXT,
    media_path VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reactions table
CREATE TABLE reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT,
    username VARCHAR(255),
    reaction VARCHAR(10),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE,
    UNIQUE KEY unique_reaction (message_id, username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    sender VARCHAR(255) NOT NULL,
    message_id INT,
    type ENUM('mention', 'reaction') NOT NULL,
    reaction VARCHAR(10) NULL,
    read_status TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
