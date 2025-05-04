<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Check if the user is an admin based on the role
if ($_SESSION['role'] != 'admin') {
    echo "Access denied.";
    exit();
}

include 'db.php';

// Update users table schema
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS date_created DATETIME DEFAULT CURRENT_TIMESTAMP");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'");
$conn->query("UPDATE users SET date_created = NOW() WHERE date_created IS NULL");
$conn->query("UPDATE users SET status = 'active' WHERE status IS NULL");

$sql = "SELECT * FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-users"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-chart-line"></i> Analytics</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-cogs"></i> Settings</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2 class="mb-3">Manage Users</h2>
        <div class="d-flex justify-content-between mb-3">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fas fa-user-plus"></i> Add User</button>
            <button class="btn btn-primary"><i class="fas fa-file-export"></i> Export to Excel</button>
        </div>
        <div class="table-responsive">
            <table id="usersTable" class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Date Created</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        $index = 1;
                        while($row = $result->fetch_assoc()) {
                            $statusClass = ($row['status'] == 'active') ? 'text-success' : 'text-danger';
                            $statusIcon = ($row['status'] == 'active') ? 'fas fa-check-circle' : 'fas fa-times-circle';
                            echo "<tr>";
                            echo "<td>{$index}</td>";
                            echo "<td>{$row['username']}</td>";
                            echo "<td>" . date("d/m/Y", strtotime($row['date_created'])) . "</td>";
                            echo "<td>{$row['role']}</td>";
                            echo "<td class='{$statusClass}'><i class='{$statusIcon}'></i> " . ucfirst($row['status']) . "</td>";
                            echo "<td>";
                            echo "<button class='btn btn-primary btn-sm me-2' onclick='showUpdateModal({$row['id']}, \"{$row['username']}\", \"{$row['role']}\", \"{$row['status']}\")'><i class='fas fa-edit'></i> Update</button>";
                            echo "<button class='btn btn-danger btn-sm' onclick='showDeleteModal({$row['id']})'><i class='fas fa-trash-alt'></i> Delete</button>";
                            echo "</td>";
                            echo "</tr>";
                            $index++;
                        }
                    } else {
                        echo "<tr><td colspan='6'>No users found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add_user.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" id="username" placeholder="Username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select name="role" class="form-select" id="role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update User Modal -->
    <div class="modal fade" id="updateUserModal" tabindex="-1" aria-labelledby="updateUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateUserModalLabel">Update User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="updateUserForm" action="update_user.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="updateUserId">
                        <div class="mb-3">
                            <label for="updateUsername" class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" id="updateUsername" placeholder="Username" required>
                        </div>
                        <div class="mb-3">
                            <label for="updatePassword" class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" id="updatePassword" placeholder="New Password" required>
                        </div>
                        <div class="mb-3">
                            <label for="updateRole" class="form-label">Role</label>
                            <select name="role" class="form-select" id="updateRole">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="updateStatus" class="form-label">Status</label>
                            <select name="status" class="form-select" id="updateStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="deleteUserForm" action="delete_user.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="deleteUserId">
                        <p>Are you sure you want to delete this user?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('content').classList.toggle('open-sidebar');
        }

        function showUpdateModal(id, username, role, status) {
            document.getElementById('updateUserId').value = id;
            document.getElementById('updateUsername').value = username;
            document.getElementById('updateRole').value = role;
            document.getElementById('updateStatus').value = status;
            var updateUserModal = new bootstrap.Modal(document.getElementById('updateUserModal'));
            updateUserModal.show();
        }

        function showDeleteModal(id) {
            document.getElementById('deleteUserId').value = id;
            var deleteUserModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
            deleteUserModal.show();
        }

        // Clear the form when the modal is hidden
        document.getElementById('updateUserModal').addEventListener('hidden.bs.modal', function (event) {
            document.getElementById('updateUserForm').reset();
        });

        $(document).ready(function() {
            $('#usersTable').DataTable({
                "pagingType": "full_numbers",
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search users",
                }
            });
        });
    </script>
</body>
</html>
