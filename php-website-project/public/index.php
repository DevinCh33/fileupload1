<?php
// This is the homepage of the website.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload Vulnerability Lab</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .header {
            text-align: center;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .section {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .section h2 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .nav-list {
            list-style: none;
            padding: 0;
        }
        .nav-list li {
            margin: 10px 0;
        }
        .nav-list a {
            display: block;
            padding: 15px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .nav-list a:hover {
            background: #0056b3;
        }
        .danger {
            background: #dc3545 !important;
        }
        .danger:hover {
            background: #c82333 !important;
        }
        .warning {
            background: #ffc107 !important;
            color: #212529 !important;
        }
        .warning:hover {
            background: #e0a800 !important;
        }
        .success {
            background: #28a745 !important;
        }
        .success:hover {
            background: #218838 !important;
        }
        .info {
            background: #17a2b8 !important;
        }
        .info:hover {
            background: #138496 !important;
        }
        .description {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔒 File Upload Vulnerability Lab</h1>
        <p>Educational environment for practicing file upload security vulnerabilities</p>
    </div>

    <div class="section">
        <h2>🎯 Frontend File Upload Pages</h2>
        <p>Traditional file upload vulnerability testing pages with varying security levels:</p>
        <ul class="nav-list">
            <li>
                <a href="page1_HR.php" class="danger">Page 1 - WEAK Security (Vulnerable)</a>
                <div class="description">Minimal safeguards with intentional vulnerabilities for practice</div>
            </li>
            <li>
                <a href="page2.php" class="warning">Page 2 - Basic Security</a>
                <div class="description">Basic file type and size validation</div>
            </li>
            <li>
                <a href="page3.php" class="warning">Page 3 - Moderate Security</a>
                <div class="description">MIME type validation and content checking</div>
            </li>
            <li>
                <a href="page4.php" class="success">Page 4 - Strong Security</a>
                <div class="description">Comprehensive validation with multiple security layers</div>
            </li>
            <li>
                <a href="page5.php" class="success">Page 5 - Secure with Authentication</a>
                <div class="description">Authentication required + file validation + logging</div>
            </li>
        </ul>
    </div>

    <div class="section">
        <h2>🔴 Backend Exploitation Interface</h2>
        <p>Realistic backend server simulation for advanced file upload exploitation:</p>
        <ul class="nav-list">
            <li>
                <a href="backend_exploit_interface.php" class="danger">Backend Exploitation Interface</a>
                <div class="description">Hacker-style interface for exploiting backend server vulnerabilities</div>
            </li>
            <li>
                <a href="backend_server.php" class="info">Backend Server API</a>
                <div class="description">Direct access to backend server endpoints (JSON API)</div>
            </li>
        </ul>
    </div>

    <div class="section">
        <h2>📚 Learning Resources</h2>
        <ul class="nav-list">
            <li>
                <a href="login.php" class="info">Authentication System</a>
                <div class="description">Login system for testing authenticated file uploads</div>
            </li>
        </ul>
    </div>

    <div class="section">
        <h2>⚠️ Important Notes</h2>
        <ul>
            <li><strong>Educational Purpose:</strong> This lab is designed for learning and practicing security vulnerabilities</li>
            <li><strong>Controlled Environment:</strong> All vulnerabilities are intentional and contained</li>
            <li><strong>Realistic Scenarios:</strong> The backend server simulates real-world exploitable systems</li>
            <li><strong>Safe Practice:</strong> Use these tools responsibly and only in controlled environments</li>
        </ul>
    </div>

    <div class="section">
        <h2>🎯 Recommended Learning Path</h2>
        <ol>
            <li><strong>Start with Page 1:</strong> Understand basic file upload vulnerabilities</li>
            <li><strong>Progress through Pages 2-5:</strong> Learn about different security measures</li>
            <li><strong>Try Backend Exploitation:</strong> Practice realistic attack scenarios</li>
            <li><strong>Analyze Responses:</strong> Understand how payloads affect the backend</li>
            <li><strong>Test Different Payloads:</strong> Experiment with various exploitation techniques</li>
        </ol>
    </div>
</body>
</html>