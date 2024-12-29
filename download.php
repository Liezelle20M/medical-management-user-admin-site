<?php
session_start();
include 'includes/db_connection.php';

/**
 * Function to handle direct file downloads via 'file' parameter.
 */
function handleDirectFileDownload($file) {
    $baseDir = __DIR__ . '/downloads/';
    $fileName = basename($file);
    $filePath = realpath($baseDir . $fileName);

    if ($filePath === false || strpos($filePath, realpath($baseDir)) !== 0) {
        http_response_code(403);
        echo "Access denied.";
        exit;
    }

    if (!is_file($filePath)) {
        http_response_code(404);
        echo "File not found.";
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));

    while (ob_get_level()) {
        ob_end_clean();
    }

    readfile($filePath);
    exit;
}

/**
 * Function to handle database-driven file downloads.
 */
function handleDatabaseFileDownload($conn) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        die("Access denied.");
    }

    if (!isset($_GET['file_type'], $_GET['query_id'], $_GET['type'])) {
        http_response_code(400);
        die("Missing required parameters.");
    }

    $fileType = $_GET['file_type'];
    $queryId = intval($_GET['query_id']);
    $queryType = $_GET['type'];
    $userId = $_SESSION['user_id'];

    $allowedFileTypes = ['pre_report', 'final_report', 'additional_document', 'invoice'];
    if (!in_array($fileType, $allowedFileTypes)) {
        http_response_code(400);
        die("Invalid file type.");
    }

    $allowedQueryTypes = ['clinical', 'medico'];
    if (!in_array($queryType, $allowedQueryTypes)) {
        http_response_code(400);
        die("Invalid query type.");
    }

    // Fetch reports data based on type
    if ($queryType === 'clinical') {
        $sql = "SELECT r.pre_reports, r.final_reports, r.additional_documents, r.invoice
                FROM reports r
                JOIN claimsqueries cc ON r.clinical_id = cc.clinical_id
                WHERE r.clinical_id = ? AND cc.user_id = ?";
    } else {
        $sql = "SELECT r.pre_reports, r.final_reports, r.additional_documents, r.invoice
                FROM reports r
                JOIN medicolegal m ON r.medico_id = m.medico_id
                WHERE r.medico_id = ? AND m.user_id = ?";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("ii", $queryId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 0) {
        http_response_code(404);
        die("No reports found or access denied.");
    }

    $report = $result->fetch_assoc();

    // Determine the file path based on file_type and file_index
    $file_path = '';
    $file_name = '';

    switch ($fileType) {
        case 'pre_report':
            $filesData = $report['pre_reports'];
            $files = json_decode($filesData, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($files)) {
                $files = !empty($filesData) ? [$filesData] : [];
            }
            if (isset($_GET['file_index'])) {
                $fileIndex = intval($_GET['file_index']);
                if (!isset($files[$fileIndex])) {
                    http_response_code(400);
                    die("Invalid file index.");
                }
                $filePathFromDB = $files[$fileIndex];
            } else {
                $filePathFromDB = $files[0];
            }
            break;
        case 'final_report':
            $filesData = $report['final_reports'];
            $files = json_decode($filesData, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($files)) {
                $files = !empty($filesData) ? [$filesData] : [];
            }
            if (isset($_GET['file_index'])) {
                $fileIndex = intval($_GET['file_index']);
                if (!isset($files[$fileIndex])) {
                    http_response_code(400);
                    die("Invalid file index.");
                }
                $filePathFromDB = $files[$fileIndex];
            } else {
                $filePathFromDB = $files[0];
            }
            break;
        case 'additional_document':
            $filesData = $report['additional_documents'];
            $files = json_decode($filesData, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($files)) {
                $files = !empty($filesData) ? [$filesData] : [];
            }
            if (isset($_GET['file_index'])) {
                $fileIndex = intval($_GET['file_index']);
                if (!isset($files[$fileIndex])) {
                    http_response_code(400);
                    die("Invalid file index.");
                }
                $filePathFromDB = $files[$fileIndex];
            } else {
                $filePathFromDB = $files[0];
            }
            break;
        case 'invoice':
            $filePathFromDB = $report['invoice'];
            break;
        default:
            http_response_code(400);
            die("Invalid file type.");
    }

    if (empty($filePathFromDB)) {
        http_response_code(404);
        die("File not found.");
    }

    // Define subdirectories based on file_type
    switch ($fileType) {
        case 'pre_report':
            $subDir = 'pre_reports/';
            break;
        case 'final_report':
            $subDir = 'final_reports/';
            break;
        case 'additional_document':
            $subDir = 'documents/';
            break;
        case 'invoice':
            $subDir = 'invoices/';
            break;
        default:
            http_response_code(400);
            die("Invalid file type.");
    }

    $baseDir = __DIR__ . '/Admin/uploads/' . $subDir;
    $fileName = basename($filePathFromDB);
    $fullFilePath = realpath($baseDir . $fileName);

    // Security check to prevent directory traversal
    $allowedDir = realpath($baseDir);
    if ($fullFilePath === false || strpos($fullFilePath, $allowedDir) !== 0) {
        http_response_code(403);
        die("Access denied.");
    }

    if (!is_file($fullFilePath)) {
        http_response_code(404);
        die("File not found.");
    }

    // Determine if the file should be viewed inline or downloaded
    $view = (isset($_GET['view']) && $_GET['view'] == '1');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fullFilePath);
    finfo_close($finfo);

    if ($view) {
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . $fileName . '"');
    } else {
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Transfer-Encoding: binary');
    }
    header('Content-Length: ' . filesize($fullFilePath));

    // Clean output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Serve the file
    readfile($fullFilePath);
    exit;
}

/**
 * Function to handle downloading all files in a ZIP archive.
 */
function handleDownloadAll($conn) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        die("Access denied.");
    }

    if (!isset($_GET['query_id'], $_GET['type'])) {
        http_response_code(400);
        die("Missing required parameters.");
    }

    $queryId = intval($_GET['query_id']);
    $queryType = $_GET['type'];
    $userId = $_SESSION['user_id'];

    $allowedQueryTypes = ['clinical', 'medico'];
    if (!in_array($queryType, $allowedQueryTypes)) {
        http_response_code(400);
        die("Invalid query type.");
    }

    // Fetch reports data based on type
    if ($queryType === 'clinical') {
        $sql = "SELECT r.pre_reports, r.final_reports, r.additional_documents, r.invoice
                FROM reports r
                JOIN claimsqueries cc ON r.clinical_id = cc.clinical_id
                WHERE r.clinical_id = ? AND cc.user_id = ?";
    } else {
        $sql = "SELECT r.pre_reports, r.final_reports, r.additional_documents, r.invoice
                FROM reports r
                JOIN medicolegal m ON r.medico_id = m.medico_id
                WHERE r.medico_id = ? AND m.user_id = ?";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("ii", $queryId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 0) {
        http_response_code(404);
        die("No reports found or access denied.");
    }

    $report = $result->fetch_assoc();
    $filePaths = [];

    // Define mapping of report types to subdirectories
    $reportTypeMap = [
        'pre_reports' => 'pre_reports',
        'final_reports' => 'final_reports',
        'additional_documents' => 'documents',
        'invoice' => 'invoices'
    ];

    // Function to add files to the list
    function addFilesToList($filesData, $subDir, &$filePaths) {
        if (!empty($filesData)) {
            $files = json_decode($filesData, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($files)) {
                $files = !empty($filesData) ? [$filesData] : [];
            }
            foreach ($files as $file) {
                $fileName = basename($file);
                $filePath = realpath(__DIR__ . '/Admin/uploads/' . $subDir . '/' . $fileName);
                if ($filePath && is_file($filePath)) {
                    $filePaths[] = $filePath;
                }
            }
        }
    }

    // Add pre_reports
    addFilesToList($report['pre_reports'], $reportTypeMap['pre_reports'], $filePaths);

    // Add final_reports
    addFilesToList($report['final_reports'], $reportTypeMap['final_reports'], $filePaths);

    // Add additional_documents
    addFilesToList($report['additional_documents'], $reportTypeMap['additional_documents'], $filePaths);

    // Add invoice
    if (!empty($report['invoice'])) {
        $invoiceFileName = basename($report['invoice']);
        $invoiceFilePath = realpath(__DIR__ . '/Admin/uploads/' . $reportTypeMap['invoice'] . '/' . $invoiceFileName);
        if ($invoiceFilePath && is_file($invoiceFilePath)) {
            $filePaths[] = $invoiceFilePath;
        }
    }

    if (empty($filePaths)) {
        http_response_code(404);
        die("No files available for download.");
    }

    // Create ZIP archive
    $zip = new ZipArchive();
    $zipFileName = tempnam(sys_get_temp_dir(), 'download_all_') . '.zip';

    if ($zip->open($zipFileName, ZipArchive::CREATE) !== TRUE) {
        http_response_code(500);
        die("Could not create ZIP file.");
    }

    foreach ($filePaths as $filePath) {
        $relativePath = substr($filePath, strlen(realpath(__DIR__ . '/Admin/uploads/')) + 1);
        $zip->addFile($filePath, $relativePath);
    }

    $zip->close();

    // Serve the ZIP file
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="all_reports.zip"');
    header('Content-Length: ' . filesize($zipFileName));

    // Clean output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    readfile($zipFileName);
    unlink($zipFileName);
    exit;
}

/**
 * Main Execution Logic
 */

if (isset($_GET['download_all']) && $_GET['download_all'] == '1') {
    handleDownloadAll($conn);
} elseif (isset($_GET['file_type'], $_GET['query_id'], $_GET['type'])) {
    handleDatabaseFileDownload($conn);
} elseif (isset($_GET['file'])) {
    handleDirectFileDownload($_GET['file']);
} else {
    http_response_code(400);
    echo "No valid parameters provided.";
    exit;
}
?>
