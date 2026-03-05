<?php
// Load the AWS SDK we just installed via Composer
require_once __DIR__ . '/../vendor/autoload.php';
use Aws\S3\S3Client;

// === DIGITALOCEAN SPACES CONFIGURATION ===
$spaces_key = 'DO801PLRB7UMXEP3GCGM';
$spaces_secret = '2z3BkVLTyZ8CchEyXKiZF3VOglXlD7BaCwCxhTVsRr8';
$spaces_region = 'fra1'; 
$spaces_endpoint = 'https://fra1.digitaloceanspaces.com'; 
$spaces_bucket = 'coreshare-files'; 

// Connect to the Cloud Locker
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => $spaces_region,
    'endpoint' => $spaces_endpoint,
    'credentials' => [
        'key'    => $spaces_key,
        'secret' => $spaces_secret,
    ],
]);
?>