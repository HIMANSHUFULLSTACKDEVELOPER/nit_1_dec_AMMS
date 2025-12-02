<?php
session_start();
include('db.php');

// Check if HOD is logged in
if(!isset($_SESSION['hod_id'])) {
    header("Location: login.php");
    exit();
}

// Check if ID is provided
if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $delete_query = "DELETE FROM nit_importnoticess WHERE id = $id";
    
    if(mysqli_query($conn, $delete_query)) {
        header("Location: hod_send_schedule.php?msg=deleted");
    } else {
        header("Location: hod_send_schedule.php?error=delete_failed");
    }
} else {
    header("Location: hod_send_schedule.php");
}
exit();
?>