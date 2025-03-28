# Web Timers

A web application for creating and managing custom timers with notifications, designed for tasks like cooldowns, reminders, or productivity tracking.

## Features

### **User Authentication**

- Secure login/registration with email verification.
- Password reset via email.
- Account lockout after failed attempts (with magic login links).
- CSRF protection for all forms.

### **Timer Management**

- Create timers with custom names, durations, and sounds.
- Pause/resume functionality.
- Reset timers to their original duration.
- Visual countdown with circular progress animation.
- Sound notifications on timer completion.

### **User Experience**

- Responsive design with consistent styling (`utility.css`).
- Auto-redirects for success/error messages (`response.php`).
- Timezone support (based on user device settings).
- Sound settings (enable/disable, volume control).

### **Technical Highlights**

- **Database**: MySQL with tables for `users` and `timers`.
- **Security**: Password hashing (bcrypt), CSRF tokens, input validation.
- **API**: RESTful endpoints for timer operations (`create`, `pause/resume`, `reset`, `delete`).
- **Frontend**: Vanilla JavaScript for dynamic timer animations and sound playback.

---

## Project Structure

### Key Files

- **`pages/`**:
  - Auth pages (`login.php`, `register.php`, `reset-password.php`).
  - Dashboard (`dashboard.php`) for timer management.
  - Utility pages (`response.php`, `verify.php`).
- **`public/`**:
  - Frontend assets (`styles.css`, `timer.js`).
  - API endpoints (`api/create_timer.php`, etc.).
- **`includes/`**:
  - Core classes (`Database.php`, `User.php`, `Timer.php`, `CSRF.php`).
  - Utilities (`Mailer.php`, `Session.php`, `Sound.php`).

### Database Schema

- **`users`**: Stores user accounts, sessions, and security codes.
- **`timers`**: Tracks timer details (name, duration, status, etc.).

---

## Planned Features

- Custom timer icons/colors.
- Push notifications (browser/desktop).
- Timer sounds continuing playing until dismissed.
- Timer sharing/collaboration.

---

## Setup Instructions

1.  **Requirements**:

    - PHP 8.0+, MySQL 5.7+, Composer.
    - SMTP server for emails (e.g., Mailtrap for development).

2.  **Installation**:

    ```bash
    git clone https://github.com/ArmstrongThomas/Web-Timers
    cd Web-Timers
    composer install
    cp .env.example .env  # Configure DB and email settings

    ```

3.  **Database**:

    - Run the SQL schema below:

      ```SQL
      -- Create Users Table
      CREATE TABLE users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      email VARCHAR(191) NOT NULL UNIQUE,
      password VARCHAR(255) NOT NULL,
      creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      creation_ip VARCHAR(45) NOT NULL,
      last_login TIMESTAMP NULL,
      last_login_ip VARCHAR(45) NULL,
      locked BOOLEAN DEFAULT FALSE,
      locked_until TIMESTAMP NULL,
      banned BOOLEAN DEFAULT FALSE,
      banned_until TIMESTAMP DEFAULT '1969-12-31 23:59:59',
      failed_logins INT DEFAULT 0,
      code1 VARCHAR(255) NULL,
      code1_timer TIMESTAMP NULL,
      code2 VARCHAR(255) NULL,
      code2_timer TIMESTAMP NULL,
      is_premium BOOLEAN DEFAULT FALSE,
      premium_expiration TIMESTAMP DEFAULT '1969-12-31 23:59:59',
      max_timers INT DEFAULT 50,
      remember_me BOOLEAN DEFAULT FALSE, -- "Remember Me" feature
      session_key VARCHAR(255) NULL, -- Stores persistent login key
      session_expires TIMESTAMP NULL, -- Expiration time for persistent session
      user_agent VARCHAR(512) NULL, -- Stores device/browser info for session tracking
      last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- Tracks last interaction
      );

      -- Create Timers Table
      CREATE TABLE timers (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      name VARCHAR(255) NOT NULL,
      description TEXT,
      icon VARCHAR(255),
      sound VARCHAR(255),
      color VARCHAR(7),
      length INT NOT NULL, -- Total countdown duration (seconds)
      remaining_time INT NULL, -- Time left when paused
      repeat_timer BOOLEAN DEFAULT FALSE,
      notification_enabled BOOLEAN DEFAULT FALSE,
      notification_time INT DEFAULT NULL,
      timezone VARCHAR(50) DEFAULT NULL,
      start_time TIMESTAMP NULL,
      end_time TIMESTAMP NULL,
      paused_at TIMESTAMP NULL, -- Timestamp when paused
      status ENUM('active', 'paused', 'completed') DEFAULT 'active',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
      );
      ```

4.  **Run**:

    - Configure a web server (e.g., Apache/Nginx) to point to the public/ directory.

## Screenshots

<img src="https://7db.pw/19cdca5">
<img src="https://7db.pw/d987db">
<img src="https://7db.pw/45783bb">
