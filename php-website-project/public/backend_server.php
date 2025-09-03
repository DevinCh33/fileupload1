<?php
// backend_server.php - Backend server with file upload validation
// This simulates a real backend that handles validation

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Backend configuration
$uploadDir = 'backend_uploads/';
$logFile = 'backend_logs/server.log';
$configFile = 'backend_config/database.conf';

// Ensure directories exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
if (!is_dir('backend_logs')) {
    mkdir('backend_logs', 0755, true);
}
if (!is_dir('backend_config')) {
    mkdir('backend_config', 0755, true);
}

// Initialize backend data
if (!file_exists($configFile)) {
    $defaultConfig = [
        'database_host' => 'localhost',
        'database_user' => 'admin',
        'database_pass' => 'admin',
        'api_key' => 'sk-1234567890abcdef',
        'admin_email' => 'admin@company.com',
        'debug_mode' => 'true'
    ];
    file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT));
}

if (!file_exists($logFile)) {
    file_put_contents($logFile, "Backend Server Started: " . date('Y-m-d H:i:s') . "\n");
}

// Log function
function logBackend($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// File validation functions
function validateFileType($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    return in_array($mimeType, $allowedTypes);
}

function validateFileExtension($fileName) {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    return in_array($extension, $allowedExtensions);
}

function validateFileSize($file, $maxSize = 2097152) { // 2MB default
    return $file['size'] <= $maxSize;
}

function validateFileContent($file) {
    $content = file_get_contents($file['tmp_name']);
    
    // Check for PHP tags
    if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false) {
        return false;
    }
    
    // Check for other dangerous patterns
    $dangerousPatterns = [
        'system(', 'exec(', 'shell_exec(', 'passthru(',
        'eval(', 'assert(', 'include(', 'require(',
        'file_get_contents(', 'file_put_contents('
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        if (strpos($content, $pattern) !== false) {
            return false;
        }
    }
    
    return true;
}

// Simulate backend operations
function getBackendStatus() {
    return [
        'status' => 'running',
        'uptime' => time() - filemtime('backend_logs/server.log'),
        'memory_usage' => memory_get_usage(true),
        'cpu_load' => sys_getloadavg(),
        'active_connections' => rand(5, 50),
        'database_connected' => true,
        'last_backup' => date('Y-m-d H:i:s', time() - 3600)
    ];
}

function getSensitiveData() {
    global $configFile;
    if (file_exists($configFile)) {
        return json_decode(file_get_contents($configFile), true);
    }
    return ['error' => 'Configuration not found'];
}

function executeBackendCommand($command) {
    $output = '';
    $returnCode = 0;
    
    if (function_exists('exec')) {
        exec($command . ' 2>&1', $output, $returnCode);
    } else {
        $output = ['exec function disabled'];
        $returnCode = 1;
    }
    
    return [
        'command' => $command,
        'output' => $output,
        'return_code' => $returnCode,
        'executed_at' => date('Y-m-d H:i:s')
    ];
}

// Handle different backend endpoints
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$endpoint = basename($requestPath);

$response = [];

switch ($endpoint) {
    case 'status':
        $response = getBackendStatus();
        logBackend("Status endpoint accessed");
        break;
        
    case 'config':
        $response = getSensitiveData();
        logBackend("Config endpoint accessed - SENSITIVE DATA EXPOSED");
        break;
        
    case 'logs':
        if (file_exists($logFile)) {
            $response = [
                'log_file' => $logFile,
                'log_size' => filesize($logFile),
                'last_entries' => array_slice(file($logFile), -10)
            ];
        } else {
            $response = ['error' => 'Log file not found'];
        }
        logBackend("Logs endpoint accessed");
        break;
        
    case 'upload':
        if ($requestMethod === 'POST') {
            if (isset($_FILES['file'])) {
                $file = $_FILES['file'];
                $fileName = basename($file['name']);
                $filePath = $uploadDir . $fileName;
                $source = $_POST['source'] ?? 'unknown';
                
                logBackend("File upload attempt from: $source, file: $fileName");
                
                // SPECIAL CASE: Page1 bypasses all validation
                if ($source === 'page1') {
                    logBackend("PAGE1 BYPASS: Skipping all validation for $fileName");
                    
                    if (move_uploaded_file($file['tmp_name'], $filePath)) {
                        $response = [
                            'status' => 'success',
                            'message' => 'File uploaded successfully (PAGE1 BYPASS)',
                            'file_path' => $filePath,
                            'file_size' => filesize($filePath),
                            'upload_time' => date('Y-m-d H:i:s'),
                            'validation_bypassed' => true,
                            'source' => $source
                        ];
                        
                        logBackend("PAGE1 BYPASS: File uploaded without validation: $fileName");
                        
                        // Auto-execute PHP files for page1
                        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        if ($fileExtension === 'php') {
                            logBackend("PAGE1 BYPASS: Auto-executing PHP file: $fileName");
                            
                            ob_start();
                            try {
                                include($filePath);
                                $executionOutput = ob_get_clean();
                                
                                $response['php_execution'] = [
                                    'executed' => true,
                                    'output' => $executionOutput,
                                    'execution_time' => date('Y-m-d H:i:s'),
                                    'bypass_mode' => true
                                ];
                                
                                logBackend("PAGE1 BYPASS: PHP file executed successfully: $fileName");
                                
                            } catch (Exception $e) {
                                $response['php_execution'] = [
                                    'executed' => false,
                                    'error' => $e->getMessage(),
                                    'bypass_mode' => true
                                ];
                                logBackend("PAGE1 BYPASS: PHP execution failed: " . $e->getMessage());
                            }
                        }
                        
                    } else {
                        $response = [
                            'status' => 'error',
                            'message' => 'Failed to move uploaded file',
                            'source' => $source
                        ];
                        logBackend("PAGE1 BYPASS: File upload failed: $fileName");
                    }
                    
                } else {
                    // Normal validation for other pages
                    logBackend("Normal validation for source: $source");
                    
                    $validationErrors = [];
                    
                    // Validate file type
                    if (!validateFileType($file)) {
                        $validationErrors[] = 'Invalid file type (MIME validation failed)';
                    }
                    
                    // Validate extension
                    if (!validateFileExtension($fileName)) {
                        $validationErrors[] = 'Invalid file extension';
                    }
                    
                    // Validate file size
                    if (!validateFileSize($file)) {
                        $validationErrors[] = 'File size exceeds limit';
                    }
                    
                    // Validate file content
                    if (!validateFileContent($file)) {
                        $validationErrors[] = 'File content validation failed (dangerous content detected)';
                    }
                    
                    if (empty($validationErrors)) {
                        if (move_uploaded_file($file['tmp_name'], $filePath)) {
                            $response = [
                                'status' => 'success',
                                'message' => 'File uploaded successfully (VALIDATED)',
                                'file_path' => $filePath,
                                'file_size' => filesize($filePath),
                                'upload_time' => date('Y-m-d H:i:s'),
                                'validation_passed' => true,
                                'source' => $source
                            ];
                            
                            logBackend("File uploaded with validation: $fileName from $source");
                            
                        } else {
                            $response = [
                                'status' => 'error',
                                'message' => 'Failed to move uploaded file',
                                'source' => $source
                            ];
                            logBackend("File upload failed: $fileName from $source");
                        }
                    } else {
                        $response = [
                            'status' => 'error',
                            'message' => 'File validation failed',
                            'validation_errors' => $validationErrors,
                            'source' => $source
                        ];
                        logBackend("File validation failed: " . implode(', ', $validationErrors) . " for $fileName from $source");
                    }
                }
                
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'No file uploaded'
                ];
            }
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Method not allowed'
            ];
        }
        break;
        
    case 'execute':
        if ($requestMethod === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (isset($input['command'])) {
                $command = $input['command'];
                $result = executeBackendCommand($command);
                
                $response = [
                    'status' => 'success',
                    'command_result' => $result
                ];
                
                logBackend("Command executed: $command");
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'No command provided'
                ];
            }
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Method not allowed'
            ];
        }
        break;
        
    case 'files':
        $files = [];
        if (is_dir($uploadDir)) {
            $uploadedFiles = scandir($uploadDir);
            foreach ($uploadedFiles as $file) {
                if ($file != '.' && $file != '..') {
                    $filePath = $uploadDir . $file;
                    $files[] = [
                        'name' => $file,
                        'size' => filesize($filePath),
                        'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                        'type' => mime_content_type($filePath)
                    ];
                }
            }
        }
        
        $response = [
            'upload_directory' => realpath($uploadDir),
            'files' => $files,
            'total_files' => count($files)
        ];
        
        logBackend("File listing requested");
        break;
        
    case 'system':
        $response = [
            'server_info' => [
                'software' => $_SERVER['SERVER_SOFTWARE'],
                'php_version' => phpversion(),
                'os' => php_uname(),
                'current_user' => function_exists('posix_getpwuid') && function_exists('posix_geteuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Unknown',
                'working_directory' => getcwd(),
                'document_root' => $_SERVER['DOCUMENT_ROOT']
            ],
            'php_config' => [
                'allow_url_fopen' => ini_get('allow_url_fopen'),
                'allow_url_include' => ini_get('allow_url_include'),
                'file_uploads' => ini_get('file_uploads'),
                'max_execution_time' => ini_get('max_execution_time'),
                'memory_limit' => ini_get('memory_limit'),
                'disable_functions' => ini_get('disable_functions')
            ],
            'environment' => [
                'server_ip' => $_SERVER['SERVER_ADDR'],
                'client_ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ]
        ];
        
        logBackend("System information requested");
        break;
        
    default:
        $response = [
            'status' => 'error',
            'message' => 'Endpoint not found',
            'available_endpoints' => [
                'status' => 'GET - Get backend status',
                'config' => 'GET - Get configuration (SENSITIVE)',
                'logs' => 'GET - Get server logs',
                'upload' => 'POST - Upload file (multipart/form-data)',
                'execute' => 'POST - Execute command (JSON)',
                'files' => 'GET - List uploaded files',
                'system' => 'GET - Get system information'
            ]
        ];
        break;
}

// Send response
echo json_encode($response, JSON_PRETTY_PRINT);
?>
