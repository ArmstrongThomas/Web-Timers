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
1. **Requirements**:
   - PHP 8.0+, MySQL 5.7+, Composer.
   - SMTP server for emails (e.g., Mailtrap for development).

2. **Installation**:
   ```bash
   git clone https://github.com/ArmstrongThomas/Web-Timers
   cd Web-Timers
   composer install
   cp .env.example .env  # Configure DB and email settings
```
