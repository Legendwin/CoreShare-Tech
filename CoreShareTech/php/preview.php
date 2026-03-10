<?php
// Prevent any whitespace/errors from breaking the file stream
ob_start();
require 'db_connect.php';
require 'spaces_connect.php';
ob_end_clean();

// Only allow logged-in users to preview files
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

if (isset($_GET['file'])) {
    $filepath = $_GET['file'];
    $s3_key = ltrim($filepath, './'); 

    try {
        // GENERATE A PRE-SIGNED URL FOR "INLINE" VIEWING (Not downloading)
        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $spaces_bucket,
            'Key'    => $s3_key,
            // 'inline' tells Chrome/Safari to render the PDF instead of downloading it
            'ResponseContentDisposition' => 'inline' 
        ]);

        $request = $s3->createPresignedRequest($cmd, '+15 minutes');
        $presignedUrl = (string) $request->getUri();

        // Redirect the iframe to the secure preview link
        header("Location: " . $presignedUrl);
        exit;
        
    } catch (Aws\Exception\AwsException $e) {
        http_response_code(404);
        die("Error: Preview not available.");
    }
}
?>