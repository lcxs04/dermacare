================================================================================
                    DERMACARE - REMOTE DERMATOLOGY CARE SYSTEM
                     WITH SECURE PHOTOS AND PATIENT DATA
================================================================================

Final Year Project (FYP)
Student Name : Law Cheng Xian
Student ID   : 1211109969
Programme    : Bachelor of Information Technology (Hons) Security Technology
Faculty      : Faculty of Information Science & Technology (FIST)
University   : Multimedia University, Malacca Campus
Supervisor   : Mrs Tan Choo Peng
Project ID   : T88J559



================================================================================
1. REQUIRED TOOLS AND VERSIONS
================================================================================

TOOL              VERSION          DOWNLOAD LINK
----------------  ---------------  ------------------------------------------
XAMPP             8.2.12 or above  https://www.apachefriends.org/download.html
                  (includes Apache 2.4.58, PHP 8.2.12, MySQL/MariaDB 10.4,
                   phpMyAdmin)

Web Browser       Latest version   Google Chrome: https://www.google.com/chrome/
                                   Microsoft Edge: https://www.microsoft.com/edge

Text Editor       (Optional)       Visual Studio Code:
                                   https://code.visualstudio.com/download

Gmail Account     -                Required only for the password reset email
                                   feature. A Gmail App Password must be
                                   created (see Section 6).

LIBRARY           VERSION          NOTES
----------------  ---------------  ------------------------------------------
PHPMailer         6.x (latest)     Auto-downloaded by the included installer
                                   tool (see Section 5, Step 6). Source:
                                   https://github.com/PHPMailer/PHPMailer

No other external libraries, frameworks, or package managers are required.
The system uses vanilla HTML5, CSS3, JavaScript, PHP 8.2, and MySQL only.

================================================================================
3. FOLDER STRUCTURE
================================================================================

dermacare/
|-- DermaCare.html                  Main frontend homepage
|-- home.php                        Serves homepage with session data
|-- index.php                       Entry point (redirects to home.php)
|
|-- config/
|   |-- db.php                      PDO database connection settings
|   |-- session.php                 Session, CSRF, role guards, audit log
|   |-- mailer.php                  PHPMailer Gmail SMTP configuration
|
|-- auth/
|   |-- login_page.php              Login user interface
|   |-- login.php                   Login endpoint (POST)
|   |-- register_page.php           Registration user interface
|   |-- register.php                Registration endpoint (POST)
|   |-- logout.php                  Logout and session destroy
|   |-- forgot_password.php         Forgot password page + email sending
|   |-- reset_password.php          Reset password page + token validation
|   |-- check_session.php           Session status JSON endpoint
|   |-- unauthorized.php            403 access denied page
|
|-- patient/
|   |-- dashboard.php               Patient dashboard
|   |-- submit_case.php             Case submission endpoint
|   |-- get_case.php                Case detail JSON endpoint
|
|-- dermatologist/
|   |-- dashboard.php               Dermatologist dashboard
|   |-- get_case.php                Case detail JSON endpoint
|   |-- save_feedback.php           Feedback saving endpoint
|
|-- database/
|   |-- dermacare_db.sql            Main database schema (4 tables)
|   |-- add_password_resets.sql     Password resets table migration
|
|-- tools/
|   |-- create_doctor.php           One-time dermatologist account creator
|   |-- install_phpmailer.php       One-time PHPMailer installer
|
|-- uploads/                        (empty - created for case photos)
|-- logs/                           (empty - created for fallback logs)
|-- vendor/                         (created by PHPMailer installer)



================================================================================
4. INSTALLATION AND EXECUTION STEPS
================================================================================

STEP 1 - INSTALL XAMPP
----------------------
1. Download XAMPP 8.2.12 from https://www.apachefriends.org/download.html
2. Run the installer and install to the default location (C:\xampp)
3. Open "XAMPP Control Panel"
4. Click "Start" next to Apache
5. Click "Start" next to MySQL
6. Both should turn green. Keep XAMPP running.

STEP 2 - COPY PROJECT FILES
---------------------------
1. Download and extract the source code from the link in Section 4
2. Copy the entire "dermacare" folder into:  C:\xampp\htdocs\
3. Final path should be:  C:\xampp\htdocs\dermacare\

STEP 3 - CREATE THE DATABASE
----------------------------
1. Open your browser and go to:  http://localhost/phpmyadmin
2. Click "New" on the left sidebar
3. Enter database name:  dermacare_db
4. Click "Create"
5. Click on "dermacare_db" in the left sidebar
6. Click the "Import" tab at the top
7. Click "Choose File" and select:  dermacare/database/dermacare_db.sql
8. Click "Go" - you should see 4 tables created:
   users, cases, images, audit_log
9. Repeat the Import step with:  dermacare/database/add_password_resets.sql
10. This adds the 5th table: password_resets

STEP 4 - VERIFY DATABASE CONNECTION SETTINGS
--------------------------------------------
1. Open  dermacare/config/db.php  in a text editor
2. Confirm these default XAMPP values:
       DB_HOST = 'localhost'
       DB_NAME = 'dermacare_db'
       DB_USER = 'root'
       DB_PASS = ''            (empty for default XAMPP)
3. Only change these if your MySQL has a custom username/password.

STEP 5 - CREATE REQUIRED EMPTY FOLDERS
--------------------------------------
Inside C:\xampp\htdocs\dermacare\ create these folders if not present:
1. uploads
2. logs

STEP 6 - INSTALL PHPMAILER (for password reset emails)
------------------------------------------------------
1. Ensure you have an internet connection
2. Go to:  http://localhost/dermacare/tools/install_phpmailer.php
3. The installer downloads 3 PHPMailer files from GitHub automatically
4. When you see "PHPMailer installed successfully", DELETE the file:
       dermacare/tools/install_phpmailer.php   (security precaution)

STEP 7 - CONFIGURE GMAIL SMTP (for password reset emails)
---------------------------------------------------------
The password reset feature sends real emails via Gmail SMTP.
To use your own Gmail account:

1. Go to https://myaccount.google.com/security
2. Enable "2-Step Verification" if not already enabled
3. Go to https://myaccount.google.com/apppasswords
4. Create an App Password named "DermaCare"
5. Copy the 16-character password shown
6. Open  dermacare/config/mailer.php  and update:
       MAIL_FROM     = 'your_email@gmail.com'
       MAIL_USERNAME = 'your_email@gmail.com'
       MAIL_PASSWORD = 'xxxx xxxx xxxx xxxx'   (your App Password)

NOTE: If you skip this step, the system still works fully EXCEPT
the password reset email will fail to send.

STEP 8 - CREATE A DERMATOLOGIST ACCOUNT
---------------------------------------
Dermatologist accounts cannot self-register (by design). Create one:
1. Go to:  http://localhost/dermacare/tools/create_doctor.php
2. Fill in name (e.g. Dr. Lee), email, and password
3. Click "Create Dermatologist Account"
4. DELETE the file  dermacare/tools/create_doctor.php  after use
   (security precaution)

STEP 9 - RUN THE SYSTEM
-----------------------
Open your browser and visit:

    http://localhost/dermacare/

You will be redirected to the DermaCare homepage.

================================================================================
6. USING THE SYSTEM (QUICK TEST GUIDE)
================================================================================

TEST AS A PATIENT:
1. Click "Get Started" on the homepage
2. Register a new patient account (password needs 8+ chars, uppercase,
   lowercase, number, special character)
3. You will be redirected to the Patient Dashboard
4. Submit a new case: enter a title, description, and upload a photo
5. The case appears under "My Cases" with status "Submitted"

TEST AS A DERMATOLOGIST:
1. Logout from the patient account
2. Click "Sign In", select "Dermatologist"
3. Login with the dermatologist account created in Step 8
4. The Dermatologist Dashboard shows all patient cases
5. Click a case, write feedback, click "Send Feedback"

VERIFY THE FEEDBACK LOOP:
1. Logout and login again as the patient
2. Click the case in "My Cases"
3. The dermatologist's feedback is displayed in the case modal

TEST PASSWORD RESET (requires Step 7 completed):
1. On the login page click "Forgot password?"
2. Enter the registered email and click "Send Reset Link"
3. Check the email inbox (and spam folder) for the reset email
4. Click the link, set a new password, and login with it
   (Reset links expire after 1 hour and can only be used once)

================================================================================
7. DEFAULT URLS SUMMARY
================================================================================

Homepage              http://localhost/dermacare/
Login                 http://localhost/dermacare/auth/login_page.php
Register              http://localhost/dermacare/auth/register_page.php
Forgot Password       http://localhost/dermacare/auth/forgot_password.php
Patient Dashboard     http://localhost/dermacare/patient/dashboard.php
Doctor Dashboard      http://localhost/dermacare/dermatologist/dashboard.php
phpMyAdmin            http://localhost/phpmyadmin

================================================================================
8. TROUBLESHOOTING
================================================================================

PROBLEM: "localhost refused to connect"
FIX    : Apache is not running. Open XAMPP Control Panel and start Apache.

PROBLEM: "Database unavailable" message
FIX    : MySQL is not running, or the database was not imported.
         Start MySQL in XAMPP and verify Step 3 was completed.

PROBLEM: 404 Not Found on all pages
FIX    : Check the folder is exactly  C:\xampp\htdocs\dermacare\
         and files are inside the correct subfolders (config, auth, etc.)

PROBLEM: Password reset email not received
FIX    : 1) Check spam folder
         2) Verify Step 6 (PHPMailer installed) and Step 7 (Gmail App
            Password configured in config/mailer.php)
         3) Check dermacare/logs/password_resets.log for error details

PROBLEM: "Link expired" on a fresh reset link
FIX    : Ensure BOTH database SQL files were imported (Step 3), including
         add_password_resets.sql.
