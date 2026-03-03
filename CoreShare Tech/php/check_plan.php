<?php
// This script assumes db_connect.php has already been required and session_start() is active.
$userPlan = 'free';

if (isset($_SESSION['user_id'])) {
    $cp_uid = intval($_SESSION['user_id']);
    $cp_stmt = $conn->prepare("SELECT plan, plan_expires FROM users WHERE id = ? LIMIT 1");
    
    if ($cp_stmt) {
        $cp_stmt->bind_param('i', $cp_uid);
        $cp_stmt->execute();
        $cp_stmt->bind_result($cp_plan, $cp_expires);
        
        if ($cp_stmt->fetch()) {
            $userPlan = $cp_plan ?: 'free';
            $cp_expired = false;
            
            // Check if it's a paid plan, has an expiry date, and the time has passed
            if ($userPlan !== 'free' && $cp_expires !== null) {
                if (strtotime($cp_expires) < time()) {
                    $userPlan = 'free';
                    $cp_expired = true;
                }
            }
        }
        $cp_stmt->close();
        
        // If the deadline passed, formally downgrade them in the database
        if (isset($cp_expired) && $cp_expired) {
            $cp_down = $conn->prepare("UPDATE users SET plan = 'free', plan_expires = NULL WHERE id = ?");
            if ($cp_down) {
                $cp_down->bind_param('i', $cp_uid);
                $cp_down->execute();
                $cp_down->close();
            }
        }
        $_SESSION['plan'] = $userPlan;
    }
}
?>