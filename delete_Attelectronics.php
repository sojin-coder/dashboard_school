<?php
// delete_Attelectronics.php - Delete attendance record from attendantelectronics table
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// Check if ID parameter exists
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']); // Convert to integer for security
    
    // First, fetch the record to get details for logging and confirmation message
    $select_sql = "SELECT student_id, attendance_date, subject, year, shift, status FROM attendantelectronic WHERE id = ?";
    $select_stmt = mysqli_prepare($conn, $select_sql);
    
    if ($select_stmt) {
        mysqli_stmt_bind_param($select_stmt, "i", $id);
        mysqli_stmt_execute($select_stmt);
        $result = mysqli_stmt_get_result($select_stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $record = mysqli_fetch_assoc($result);
            $student_id = $record['student_id'];
            $attendance_date = $record['attendance_date'];
            $subject = $record['subject'];
            $year = $record['year'];
            $shift = $record['shift'];
            $status = $record['status'];
            
            mysqli_stmt_close($select_stmt);
            
            // Proceed with deletion
            $delete_sql = "DELETE FROM attendantelectronic WHERE id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            
            if ($delete_stmt) {
                mysqli_stmt_bind_param($delete_stmt, "i", $id);
                
                if (mysqli_stmt_execute($delete_stmt)) {
                    // Check if any row was actually deleted
                    if (mysqli_stmt_affected_rows($delete_stmt) > 0) {
                        // Log the deletion (optional but recommended)
                        error_log("DELETED: Attendance record ID: $id for Student ID: $student_id on $attendance_date");
                        
                        // Set success message
                        $_SESSION['attendance_success'] = "✅ Attendance record successfully deleted!<br>
                                                            📚 Student ID: $student_id<br>
                                                            📅 Date: $attendance_date<br>
                                                            📖 Subject: $subject<br>
                                                            🎓 Year: $year<br>
                                                            ⏰ Shift: $shift<br>
                                                            📊 Status: $status";
                    } else {
                        $_SESSION['attendance_errors'] = ["No record found with ID: $id"];
                    }
                } else {
                    // Delete execution failed
                    $error_msg = "Failed to delete record: " . mysqli_stmt_error($delete_stmt);
                    error_log($error_msg);
                    $_SESSION['attendance_errors'] = [$error_msg];
                }
                mysqli_stmt_close($delete_stmt);
            } else {
                $_SESSION['attendance_errors'] = ["Database prepare error for deletion: " . mysqli_error($conn)];
            }
        } else {
            $_SESSION['attendance_errors'] = ["Record with ID: $id does not exist in the database"];
            mysqli_stmt_close($select_stmt);
        }
    } else {
        $_SESSION['attendance_errors'] = ["Database prepare error for select: " . mysqli_error($conn)];
    }
} else {
    $_SESSION['attendance_errors'] = ["Invalid request. No record ID provided."];
}

// Close database connection
mysqli_close($conn);

// Redirect back to attendance page
header("Location: attElectronics.php");
exit();
?>