<?php
// rklb1_backend.php - Specialized backend for RKLB1 lab with NO validation
// This backend is intentionally vulnerable for educational purposes

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Backend configuration
$uploadDir = 'uploads/';

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Function to extract metadata from files
function extractMetadata($filePath) {
    $metadata = [
        'file_size' => filesize($filePath),
        'modified_time' => date('Y-m-d H:i:s', filemtime($filePath)),
        'mime_type' => mime_content_type($filePath),
        'extension' => strtolower(pathinfo($filePath, PATHINFO_EXTENSION))
    ];
    
    // Try to extract additional metadata based on file type
    $extension = $metadata['extension'];
    
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        // Image metadata
        $imageInfo = getimagesize($filePath);
        if ($imageInfo) {
            $metadata['image_width'] = $imageInfo[0];
            $metadata['image_height'] = $imageInfo[1];
            $metadata['image_type'] = $imageInfo[2];
        }
        
        // Try to extract EXIF data for JPEG
        if ($extension === 'jpg' || $extension === 'jpeg') {
            if (function_exists('exif_read_data')) {
                $exif = @exif_read_data($filePath);
                if ($exif) {
                    $metadata['exif_data'] = $exif;
                }
            }
        }
    } elseif ($extension === 'pdf') {
        // PDF metadata
        $metadata['pdf_info'] = 'PDF file detected';
        
        // Try to read PDF content for metadata (basic attempt)
        $content = file_get_contents($filePath);
        if ($content) {
            // Look for PDF metadata patterns
            if (preg_match('/\/Title\s*\((.*?)\)/', $content, $matches)) {
                $metadata['pdf_title'] = $matches[1];
            }
            if (preg_match('/\/Author\s*\((.*?)\)/', $content, $matches)) {
                $metadata['pdf_author'] = $matches[1];
            }
            if (preg_match('/\/Subject\s*\((.*?)\)/', $content, $matches)) {
                $metadata['pdf_subject'] = $matches[1];
            }
        }
    }
    
    return $metadata;
}

// Function to process filename for "FINANCIAL_" requirement
function processFilename($fileName) {
    $processed = [
        'original_name' => $fileName,
        'contains_financial' => false,
        'processed_name' => $fileName,
        'analysis' => [],
        'financial_document_name' => null
    ];
    
    // Check if filename contains "FINANCIAL_"
    if (stripos($fileName, 'FINANCIAL_') !== false) {
        $processed['contains_financial'] = true;
        $processed['analysis'][] = 'Filename contains required "FINANCIAL_" prefix';
        
        // Extract the part after "FINANCIAL_"
        $parts = explode('FINANCIAL_', $fileName, 2);
        if (count($parts) > 1) {
            $processed['financial_suffix'] = $parts[1];
            $processed['analysis'][] = 'Financial suffix: ' . $parts[1];
            
            // Process financial document name (replace underscores with spaces, remove extension)
            $financialPart = $parts[1];
            $financialPart = pathinfo($financialPart, PATHINFO_FILENAME);
            $processed['financial_document_name'] = str_replace('_', ' ', $financialPart);
            $processed['analysis'][] = 'Processed financial name: ' . $processed['financial_document_name'];
        }
    } else {
        $processed['analysis'][] = 'WARNING: Filename does not contain "FINANCIAL_" prefix';
    }
    
    // Analyze filename structure
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $processed['extension'] = $extension;
    $processed['analysis'][] = 'File extension: ' . $extension;
    
    // Check for potential path traversal attempts
    if (strpos($fileName, '..') !== false) {
        $processed['analysis'][] = 'WARNING: Potential path traversal detected';
    }
    
    // Check for special characters
    if (preg_match('/[<>:"|?*]/', $fileName)) {
        $processed['analysis'][] = 'WARNING: Filename contains special characters';
    }
    
    return $processed;
}

// Handle different actions
$requestMethod = $_SERVER['REQUEST_METHOD'];
$response = [];

if ($requestMethod === 'POST') {
    $action = $_POST['action'] ?? 'upload';
    
    switch ($action) {
        case 'upload':
            if (isset($_FILES['file'])) {
                $file = $_FILES['file'];
                $fileName = basename($file['name']);
                $filePath = $uploadDir . $fileName;
                
                // Process filename for "FINANCIAL_" requirement
                $filenameAnalysis = processFilename($fileName);
                // NO VALIDATION - Accept any file
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    // Extract metadata
                    $metadata = extractMetadata($filePath);
                    $response = [
                        'status' => 'success',
                        'message' => 'File uploaded successfully (NO VALIDATION)',
                        'file_path' => $filePath,
                        'file_name' => $fileName,
                        'upload_time' => date('Y-m-d H:i:s'),
                        'filename_analysis' => $filenameAnalysis,
                        'metadata' => $metadata,
                        'financial_document_name' => $filenameAnalysis['financial_document_name'],
                        'vulnerability_note' => 'This backend has NO validation - any file type accepted'
                    ];
                    
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'Failed to move uploaded file',
                        'filename_analysis' => $filenameAnalysis
                    ];
                }
                
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'No file uploaded'
                ];
            }
            break;
            
        case 'clear':
            $files = glob($uploadDir . '*');
            $clearedCount = 0;
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (unlink($file)) {
                        $clearedCount++;
                    }
                }
            }
            
            $response = [
                'status' => 'success',
                'message' => "Cleared $clearedCount files",
                'cleared_count' => $clearedCount
            ];
            break;
            
        default:
            $response = [
                'status' => 'error',
                'message' => 'Invalid action'
            ];
            break;
    }
    
} else {
    // GET request - return backend info
    $response = [
        'status' => 'info',
        'backend' => 'RKLB1 Lab Backend',
        'description' => 'Specialized backend for file upload vulnerability lab',
        'features' => [
            'no_validation' => true,
            'filename_processing' => true,
            'metadata_extraction' => true,
            'file_clearing' => true
        ],
        'upload_directory' => realpath($uploadDir),
    // 'log_file' => realpath($logFile),
        'warning' => 'This backend is intentionally vulnerable for educational purposes'
    ];
}

// Send response
echo json_encode($response, JSON_PRETTY_PRINT);
?>
