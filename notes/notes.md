```sql
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
   remember_me BOOLEAN DEFAULT FALSE,           -- "Remember Me" feature
   session_key VARCHAR(255) NULL,               -- Stores persistent login key
   session_expires TIMESTAMP NULL,              -- Expiration time for persistent session
   user_agent VARCHAR(512) NULL,                -- Stores device/browser info for session tracking
   last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP  -- Tracks last interaction
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
    length INT NOT NULL,  -- Total countdown duration (seconds)
    remaining_time INT NULL,  -- Time left when paused
    repeat_timer BOOLEAN DEFAULT FALSE,
    notification_enabled BOOLEAN DEFAULT FALSE,
    notification_time INT DEFAULT NULL,
    timezone VARCHAR(50) DEFAULT NULL,
    start_time TIMESTAMP NULL,
    end_time TIMESTAMP NULL,
    paused_at TIMESTAMP NULL,  -- Timestamp when paused
    status ENUM('active', 'paused', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    position INT DEFAULT '0',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


```




---

### **How the Logic Works**
1. **Starting a Timer**
    - `start_time` is set to `NOW()`.
    - `remaining_time` is initially set to `length`.

2. **Pausing a Timer**
    - `paused_at = NOW()`.
    - `remaining_time = end_time - NOW()` (calculate time left).
    - `status = 'paused'`.

3. **Resuming a Timer**
    - Calculate new `end_time = NOW() + remaining_time`.
    - Reset `paused_at` to `NULL`.
    - `status = 'active'`.

---

### **Example Workflow**
#### **1. User starts a 10-minute timer (`600s`) at `12:00 PM`**
```sql
INSERT INTO timers (user_id, name, length, start_time, end_time, status)
VALUES (1, 'Game Cooldown', 600, NOW(), NOW() + INTERVAL 600 SECOND, 'active');
```

#### **2. User pauses it at `12:05 PM` (300s left)**
```sql
UPDATE timers
SET paused_at = NOW(),
    remaining_time = TIMESTAMPDIFF(SECOND, NOW(), end_time),
    status = 'paused'
WHERE id = 1;
```
✅ `paused_at = 12:05 PM`, `remaining_time = 300`

#### **3. User resumes at `12:10 PM`**
```sql
UPDATE timers
SET start_time = NOW(),
    end_time = NOW() + INTERVAL remaining_time SECOND,
    paused_at = NULL,
    status = 'active'
WHERE id = 1;
```
✅ `new end_time = 12:15 PM`, countdown resumes.

---
