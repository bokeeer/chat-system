
## 🛠️ Technologies

*   **Backend**: PHP (PDO for Database Abstraction)
*   **Database**: MySQL / MariaDB
*   **Frontend**: HTML5, CSS3 (Variables, Flexbox, Grid), JavaScript (Vanilla ES6+)
*   **Server**: Apache (via XAMPP/WAMP or similar)

## 📦 Installation

1.  **Prerequisites**: Ensure you have XAMPP, WAMP, or a LAMP stack installed.
2.  **Clone/Copy**: Place the project folder into your web server's root directory (e.g., `C:\xampp\htdocs\chat-application-system`).
3.  **Database Setup**:
    *   Open phpMyAdmin (usually `http://localhost/phpmyadmin`).
    *   Create a new database named `chat_system`.
    *   Import the `database.sql` file located in the project root.
4.  **Configuration**:
    *   Open `db.php` and verify your database credentials (default is set to standard XAMPP settings: `root` user, no password).

## 🖥️ Usage

1.  Open your browser and navigate to `http://localhost/chat-application-system/`.
2.  **Register** a new account.
3.  **Login** to access the dashboard.
4.  **Test Distributed Messaging**:
    *   Open the application in a separate browser (or Incognito window) and login as a **different user**.
    *   Select the other user from the sidebar to start a private chat.
    *   Send messages and watch them appear instantly on the other screen with unread badges updating in real-time.

