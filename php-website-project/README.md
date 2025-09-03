# PHP Website Project

## Overview
This project is a PHP-based web application that includes a weak login page vulnerable to SQL injection and five pages with varying levels of safeguarding against file upload vulnerabilities. The application is designed to demonstrate common security pitfalls and best practices in web development.

## Project Structure
```
php-website-project
├── public
│   ├── index.php          # Homepage with links to other pages
│   ├── login.php          # Weak login form vulnerable to SQL injection
│   ├── page1.php          # Minimal safeguards for file uploads
│   ├── page2.php          # Moderate safeguards for file uploads
│   ├── page3.php          # Improved safeguards for file uploads
│   ├── page4.php          # Strong safeguards for file uploads
│   └── page5.php          # Most robust safeguards for file uploads
├── src
│   ├── db.php             # Database connection and queries
│   ├── auth.php           # Authentication logic
│   └── upload_handlers
│       ├── handler1.php   # Upload logic for page 1
│       ├── handler2.php   # Upload logic for page 2
│       ├── handler3.php   # Upload logic for page 3
│       ├── handler4.php   # Upload logic for page 4
│       └── handler5.php   # Upload logic for page 5
├── tests
│   └── test_app.php       # Tests for login and upload functionalities
├── .gitignore              # Files and directories to ignore in version control
└── README.md               # Documentation for the project
```

## Setup Instructions
1. Clone the repository to your local machine.
2. Navigate to the project directory.
3. Ensure you have a web server with PHP support (e.g., Apache, Nginx) set up.
4. Configure your database connection in `src/db.php`.
5. Access the application through your web server's URL (e.g., `http://localhost/php-website-project/public/index.php`).

## Features
- **Weak Login Page**: Demonstrates SQL injection vulnerabilities.
- **File Upload Pages**: Each page showcases different levels of security for file uploads, from minimal to robust safeguards.
- **Testing**: Includes tests to verify the functionality of login and file upload features.

## Security Notice
This project is intended for educational purposes only. It is crucial to implement proper security measures in production applications to protect against vulnerabilities such as SQL injection and file upload attacks.


HR - accepts resume
