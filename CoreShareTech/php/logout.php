<?php
// 1. Load the database connection AND our custom session handler
require 'db_connect.php';

// 2. Clear all the variables out of the $_SESSION array
session_unset();

// 3. Destroy the session (This now deletes the row in your MySQL database!)
session_destroy();

// 4. Redirect to the Guest Dashboard
header("Location: ../html/dashboard.php?success=logged_out");
exit;
?>