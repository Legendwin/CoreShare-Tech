<?php
// Prevent any whitespace/errors from breaking the file stream
ob_start();
require 'db_connect.php';
require 'spaces_connect.php';
ob_end_clean();

// Provide a clean error message if the user isn't logged in
if (!isset($_SESSION['user_id'])) {
    die("<div style='font-family:sans-serif; text-align:center; padding:40px; color:#EF4444;'>Please log in to preview files.</div>");
}

if (isset($_GET['file'])) {
    $filepath = $_GET['file'];
    $s3_key = ltrim($filepath, './'); 
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

    // 1. Force the cloud to tell the browser EXACTLY how to render the file
    $contentType = 'application/octet-stream';
    if ($ext === 'pdf') $contentType = 'application/pdf';
    elseif (in_array($ext, ['jpg', 'jpeg'])) $contentType = 'image/jpeg';
    elseif ($ext === 'png') $contentType = 'image/png';
    elseif ($ext === 'webp') $contentType = 'image/webp';

    try {
        // 2. GENERATE A PRE-SIGNED URL FOR "INLINE" VIEWING
        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $spaces_bucket,
            'Key'    => $s3_key,
            'ResponseContentDisposition' => 'inline',
            'ResponseContentType' => $contentType // <-- THIS FIXES THE BLANK SCREEN
        ]);

        $request = $s3->createPresignedRequest($cmd, '+15 minutes');
        $presignedUrl = (string) $request->getUri();

        // 3. Redirect the iframe to the secure preview link
        header("Location: " . $presignedUrl);
        exit;
        
    } catch (\Throwable $e) {
        // If the file is missing from the cloud, show a clean message inside the iframe
        http_response_code(404);
        die("<div style='font-family:sans-serif; text-align:center; padding:40px; color:#64748B;'><h3>Preview not available</h3><p>This file might have been deleted or corrupted.</p></div>");
    }
} else {
    die("No file specified.");
}
?>