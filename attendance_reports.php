<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

include 'header.php';
include '../includes/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}

// Check users table structure and identify column names
$id_column = 'id'; 
$name_column = 'name';
$columns = [];

// Check if users table exists
$table_exists = mysqli_query($connection, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($table_exists) > 0) {
    // Table exists, check for structure
    $result = mysqli_query($connection, "DESCRIBE users");
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = $row['Field'];
            if ($row['Key'] == 'PRI') {
                if ($row['Field'] == 'sno') {
                    $id_column = 'sno';
                }
            }
            if ($row['Field'] == 'name') {
                $has_name = true;
            }
            if ($row['Field'] == 'fname') {
                $has_fname = true;
            }
            if ($row['Field'] == 'lname') {
                $has_lname = true;
            }
        }
    }
    
    // Determine name column
    if (!isset($has_name) && isset($has_fname) && isset($has_lname)) {
        $name_column = "CONCAT(fname, ' ', lname) as name";
    } else if (!isset($has_name) && isset($has_fname)) {
        $name_column = "fname as name";
    } else if (isset($has_name)) {
        $name_column = "name";
    } else {
        $name_column = "email as name";
    }
}

// Create attendance table if it doesn't exist
$create_attendance_table_sql = "CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `meal_type` enum('Breakfast','Lunch','Dinner') NOT NULL,
  `attendance_date` date NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`user_id`,`meal_type`,`attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($connection, $create_attendance_table_sql)) {
    die("Error creating attendance table: " . mysqli_error($connection));
}

// Get date parameters
$today = date('Y-m-d');
$start_date = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : $today;

// Make sure date is valid
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $start_date = $today;
}

// Format date for display
$date_display = date('l, F d, Y', strtotime($start_date));

// Handle attendance updates
$update_message = '';
$success = false; // Initialize success flag

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_attendance'])) {
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    $meal_type = isset($_POST['meal_type']) ? $_POST['meal_type'] : '';
    $date = isset($_POST['date']) ? $_POST['date'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Validate input data
    if (empty($user_id) || empty($meal_type) || empty($date) || empty($status)) {
        $update_message = '<div class="alert alert-danger py-2 mb-3">Missing required data for attendance update.</div>';
    } else {
        if ($status == 'present') {
            // Add attendance record
            $query = "INSERT IGNORE INTO attendance (user_id, meal_type, attendance_date) 
                     VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $meal_type, $date);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            // Remove attendance record
            $query = "DELETE FROM attendance 
                     WHERE user_id = ? AND meal_type = ? AND attendance_date = ?";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $meal_type, $date);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        if ($success) {
            $update_message = '<div class="alert alert-success py-2 mb-3">Attendance updated successfully!</div>';
        } else {
            $update_message = '<div class="alert alert-danger py-2 mb-3">Error updating attendance: ' . mysqli_error($connection) . '</div>';
        }
    }
    
    // If it's an AJAX request, return JSON response
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        $response = [
            'success' => $success,
            'message' => strip_tags($update_message),
            'newStatus' => $status
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Handle sample data generation
if (isset($_GET['generate']) && $_GET['generate'] == '1') {
    // Code for generating sample data (kept but hidden from interface)
    // ... existing code ...
}

// End output buffering
ob_end_flush();
?>

<div class="container-fluid py-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <!-- Simple header with date selector -->
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                <h5 class="m-0 text-muted">Attendance</h5>
                <form method="GET" action="" class="d-flex align-items-center">
                    <input type="date" class="form-control form-control-sm border-0 bg-light me-2" 
                           id="date" name="date" value="<?php echo $start_date; ?>">
                    <button type="submit" class="btn btn-sm btn-primary px-3">Go</button>
                </form>
            </div>
            
            <!-- Date display -->
            <div class="mb-3 small text-center text-muted">
                <?php echo $date_display; ?>
                <div class="mt-1 small text-primary">Click on a status to toggle attendance</div>
            </div>
            
            <!-- Show update message if any -->
            <?php echo $update_message; ?>

            <!-- Simple table -->
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th class="text-center" width="100px">Breakfast</th>
                            <th class="text-center" width="100px">Lunch</th>
                            <th class="text-center" width="100px">Dinner</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get users with their attendance data for the selected date
                        $user_query = "SELECT u." . ($id_column == 'sno' ? "sno as id" : "id") . ", 
                                    " . $name_column . ", 
                                    (SELECT COUNT(*) FROM attendance a WHERE a.user_id = u." . ($id_column == 'sno' ? "sno" : "id") . " 
                                     AND a.meal_type = 'Breakfast' AND a.attendance_date = ?) AS breakfast,
                                    (SELECT COUNT(*) FROM attendance a WHERE a.user_id = u." . ($id_column == 'sno' ? "sno" : "id") . " 
                                     AND a.meal_type = 'Lunch' AND a.attendance_date = ?) AS lunch,
                                    (SELECT COUNT(*) FROM attendance a WHERE a.user_id = u." . ($id_column == 'sno' ? "sno" : "id") . " 
                                     AND a.meal_type = 'Dinner' AND a.attendance_date = ?) AS dinner
                                    FROM users u
                                    ORDER BY " . (isset($has_name) ? "name" : "email");
                        
                        $user_stmt = mysqli_prepare($connection, $user_query);
                        mysqli_stmt_bind_param($user_stmt, "sss", 
                                            $start_date, $start_date, $start_date);
                        mysqli_stmt_execute($user_stmt);
                        $users_result = mysqli_stmt_get_result($user_stmt);
                        
                        if ($users_result && mysqli_num_rows($users_result) > 0):
                            while ($user = mysqli_fetch_assoc($users_result)): 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td class="text-center attendance-cell" 
                                    data-user-id="<?php echo $user['id']; ?>" 
                                    data-meal-type="Breakfast" 
                                    data-date="<?php echo $start_date; ?>"
                                    data-status="<?php echo ($user['breakfast'] > 0) ? 'present' : 'absent'; ?>">
                                    <?php if ($user['breakfast'] > 0): ?>
                                        <span class="badge bg-success px-3 py-2">Present</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger px-3 py-2">Absent</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center attendance-cell"
                                    data-user-id="<?php echo $user['id']; ?>" 
                                    data-meal-type="Lunch" 
                                    data-date="<?php echo $start_date; ?>"
                                    data-status="<?php echo ($user['lunch'] > 0) ? 'present' : 'absent'; ?>">
                                    <?php if ($user['lunch'] > 0): ?>
                                        <span class="badge bg-success px-3 py-2">Present</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger px-3 py-2">Absent</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center attendance-cell"
                                    data-user-id="<?php echo $user['id']; ?>" 
                                    data-meal-type="Dinner" 
                                    data-date="<?php echo $start_date; ?>"
                                    data-status="<?php echo ($user['dinner'] > 0) ? 'present' : 'absent'; ?>">
                                    <?php if ($user['dinner'] > 0): ?>
                                        <span class="badge bg-success px-3 py-2">Present</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger px-3 py-2">Absent</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                            <tr>
                                <td colspan="4" class="text-center py-3">No attendance data found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for attendance updates -->
<form id="attendanceForm" method="POST" style="display: none;">
    <input type="hidden" name="update_attendance" value="1">
    <input type="hidden" name="user_id" id="user_id">
    <input type="hidden" name="meal_type" id="meal_type">
    <input type="hidden" name="date" id="date">
    <input type="hidden" name="status" id="status">
</form>

<style>
.table th {
    font-weight: 500;
}

.table td {
    vertical-align: middle;
}

.attendance-cell {
    cursor: pointer;
}

.attendance-cell:hover .badge {
    opacity: 0.8;
}

/* Print styles */
@media print {
    .card {
        box-shadow: none !important;
        border: none !important;
    }
    
    form, .no-print, .alert {
        display: none !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple search functionality
    document.getElementById('searchInput')?.addEventListener('keyup', function() {
        let input = this.value.toLowerCase();
        let rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(input) ? '' : 'none';
        });
    });

    // Click handler for attendance cells
    document.querySelectorAll('.attendance-cell').forEach(cell => {
        cell.addEventListener('click', function() {
            // Get data attributes
            const userId = this.getAttribute('data-user-id');
            const mealType = this.getAttribute('data-meal-type');
            const date = this.getAttribute('data-date');
            const currentStatus = this.getAttribute('data-status');
            
            // Visual feedback - show loading state
            const badge = this.querySelector('.badge');
            const originalText = badge.textContent;
            badge.textContent = 'Updating...';
            badge.classList.remove('bg-success', 'bg-danger');
            badge.classList.add('bg-secondary');
            
            // Toggle status
            const newStatus = currentStatus === 'present' ? 'absent' : 'present';
            
            // Set form values
            document.getElementById('user_id').value = userId;
            document.getElementById('meal_type').value = mealType;
            document.getElementById('date').value = date;
            document.getElementById('status').value = newStatus;
            
            // Submit form using fetch API for better experience
            const form = document.getElementById('attendanceForm');
            const formData = new FormData(form);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Update the UI immediately
                if (data.success) {
                    if (data.newStatus === 'present') {
                        badge.textContent = 'Present';
                        badge.classList.remove('bg-secondary');
                        badge.classList.add('bg-success');
                        this.setAttribute('data-status', 'present');
                    } else {
                        badge.textContent = 'Absent';
                        badge.classList.remove('bg-secondary');
                        badge.classList.add('bg-danger');
                        this.setAttribute('data-status', 'absent');
                    }
                    
                    // Show success message
                    const successAlert = document.createElement('div');
                    successAlert.className = 'alert alert-success py-2 mb-3';
                    successAlert.textContent = data.message;
                    
                    const messageContainer = document.querySelector('.mb-3.small.text-center').nextElementSibling;
                    if (messageContainer && messageContainer.classList.contains('alert')) {
                        messageContainer.replaceWith(successAlert);
                    } else {
                        document.querySelector('.mb-3.small.text-center').after(successAlert);
                    }
                    
                    // Auto-dismiss alert
                    setTimeout(() => {
                        successAlert.style.transition = 'opacity 0.5s ease';
                        successAlert.style.opacity = '0';
                        setTimeout(() => successAlert.remove(), 500);
                    }, 2000);
                } else {
                    // Show error
                    alert(data.message || 'Failed to update attendance');
                    
                    // Revert to original state
                    badge.textContent = originalText;
                    badge.classList.remove('bg-secondary');
                    badge.classList.add(currentStatus === 'present' ? 'bg-success' : 'bg-danger');
                }
            })
            .catch(error => {
                // Revert to original state on error
                console.error('Error updating attendance:', error);
                badge.textContent = originalText;
                badge.classList.remove('bg-secondary');
                badge.classList.add(currentStatus === 'present' ? 'bg-success' : 'bg-danger');
                alert('Failed to update attendance. Please try again.');
            });
            
            // Prevent normal form submission which would cause page reload
            return false;
        });
    });
    
    // Auto-dismiss alerts after 3 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.style.display = 'none', 500);
        });
    }, 3000);
});
</script>

<?php include 'footer.php'; ?> 