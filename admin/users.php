<?php
require_once '../config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'User Management';
$currentPage = 'users';

// Initialize messages
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_user_status'])) {
        $user_id = intval($_POST['user_id']);
        $status = $_POST['status'];
        
        if (in_array($status, ['active', 'suspended', 'banned'])) {
            $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ? AND is_admin = 0");
            if ($stmt->execute([$status, $user_id])) {
                $success_message = "User status updated successfully!";
            } else {
                $error_message = "Failed to update user status.";
            }
        }
    }
    
    if (isset($_POST['promote_user'])) {
        $user_id = intval($_POST['user_id']);
        
        $stmt = $pdo->prepare("UPDATE users SET is_admin = 1, updated_at = NOW() WHERE id = ? AND is_admin = 0");
        if ($stmt->execute([$user_id])) {
            $success_message = "User promoted to admin successfully!";
        } else {
            $error_message = "Failed to promote user.";
        }
    }
    
    if (isset($_POST['demote_admin'])) {
        $user_id = intval($_POST['user_id']);
        
        // Don't allow demoting yourself
        if ($user_id == $_SESSION['user_id']) {
            $error_message = "You cannot demote yourself.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_admin = 0, updated_at = NOW() WHERE id = ? AND is_admin = 1");
            if ($stmt->execute([$user_id])) {
                $success_message = "Admin demoted to user successfully!";
            } else {
                $error_message = "Failed to demote admin.";
            }
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        
        // Don't allow deleting yourself
        if ($user_id == $_SESSION['user_id']) {
            $error_message = "You cannot delete yourself.";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Delete user's data
                $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $stmt = $pdo->prepare("DELETE FROM play_history WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $stmt = $pdo->prepare("DELETE FROM playlist_tracks WHERE playlist_id IN (SELECT id FROM playlists WHERE user_id = ?)");
                $stmt->execute([$user_id]);
                
                $stmt = $pdo->prepare("DELETE FROM playlists WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
                $stmt->execute([$user_id]);
                
                $pdo->commit();
                $success_message = "User deleted successfully!";
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = "Failed to delete user: " . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['send_notification'])) {
        $user_id = intval($_POST['user_id']);
        $message = sanitize($_POST['message']);
        
        if (!empty($message)) {
            // Here you would implement notification system
            // For now, we'll just show success
            $success_message = "Notification sent successfully!";
        } else {
            $error_message = "Notification message cannot be empty.";
        }
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_role = $_GET['role'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($filter_status !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $filter_status;
}

if ($filter_role !== 'all') {
    if ($filter_role === 'admin') {
        $where_conditions[] = "is_admin = 1";
    } else {
        $where_conditions[] = "is_admin = 0";
    }
}

if (!empty($search_query)) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Validate sort column
$valid_sorts = ['username', 'email', 'full_name', 'created_at', 'last_login', 'status'];
if (!in_array($sort_by, $valid_sorts)) {
    $sort_by = 'created_at';
}

$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Get users with pagination
try {
    // Count total users
    $count_sql = "SELECT COUNT(*) FROM users $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_users = $stmt->fetchColumn();
    
    // Get users
    $sql = "
        SELECT u.*, 
               (SELECT COUNT(*) FROM playlists WHERE user_id = u.id) as playlist_count,
               (SELECT COUNT(*) FROM favorites WHERE user_id = u.id) as favorite_count,
               (SELECT COUNT(*) FROM play_history WHERE user_id = u.id AND played_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as plays_last_30_days
        FROM users u 
        $where_clause 
        ORDER BY $sort_by $sort_order 
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Calculate pagination
    $total_pages = ceil($total_users / $per_page);
    
} catch (PDOException $e) {
    $users = [];
    $total_users = 0;
    $total_pages = 0;
    $error_message = "Failed to load users.";
}

// Get statistics
try {
    $stats = [];
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
    $stats['total_users'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1");
    $stats['total_admins'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $stats['active_users'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'");
    $stats['suspended_users'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'banned'");
    $stats['banned_users'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['new_users_30_days'] = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $stats = [
        'total_users' => 0,
        'total_admins' => 0,
        'active_users' => 0,
        'suspended_users' => 0,
        'banned_users' => 0,
        'new_users_30_days' => 0
    ];
}

include '../includes/header.php';
?>

<div class="container-fluid admin-users-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center py-4">
                <div class="col-md-8">
                    <h1 class="page-title">
                        <i class="fas fa-users me-3"></i>User Management
                    </h1>
                    <p class="page-subtitle">Manage users, permissions, and access controls</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="header-actions">
                        <a href="index.php" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['total_admins']); ?></div>
                        <div class="stat-label">Admins</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['active_users']); ?></div>
                        <div class="stat-label">Active</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-pause-circle text-warning"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['suspended_users']); ?></div>
                        <div class="stat-label">Suspended</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-ban text-danger"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['banned_users']); ?></div>
                        <div class="stat-label">Banned</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-plus text-info"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['new_users_30_days']); ?></div>
                        <div class="stat-label">New (30d)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="filters-section mb-4">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search Users</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search_query); ?>" 
                                   placeholder="Username, email, or name...">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="suspended" <?php echo $filter_status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="banned" <?php echo $filter_status === 'banned' ? 'selected' : ''; ?>>Banned</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="all" <?php echo $filter_role === 'all' ? 'selected' : ''; ?>>All Roles</option>
                                <option value="user" <?php echo $filter_role === 'user' ? 'selected' : ''; ?>>Users</option>
                                <option value="admin" <?php echo $filter_role === 'admin' ? 'selected' : ''; ?>>Admins</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Join Date</option>
                                <option value="username" <?php echo $sort_by === 'username' ? 'selected' : ''; ?>>Username</option>
                                <option value="email" <?php echo $sort_by === 'email' ? 'selected' : ''; ?>>Email</option>
                                <option value="last_login" <?php echo $sort_by === 'last_login' ? 'selected' : ''; ?>>Last Login</option>
                                <option value="status" <?php echo $sort_by === 'status' ? 'selected' : ''; ?>>Status</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="order" class="form-label">Order</label>
                            <select class="form-select" id="order" name="order">
                                <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                                <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="users-table-section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Users 
                        <span class="badge bg-secondary"><?php echo number_format($total_users); ?></span>
                    </h5>
                    <div class="table-actions">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="exportUsers()">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshTable()">
                                <i class="fas fa-sync me-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($users)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No Users Found</h5>
                            <p class="text-muted">No users match your current filters.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Activity</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr class="user-row" data-user-id="<?php echo $user['id']; ?>">
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        <?php if ($user['avatar']): ?>
                                                            <img src="<?php echo UPLOAD_URL . '/avatars/' . $user['avatar']; ?>" 
                                                                 alt="Avatar" class="avatar-img">
                                                        <?php else: ?>
                                                            <div class="avatar-placeholder">
                                                                <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="user-details">
                                                        <div class="username">
                                                            <?php echo htmlspecialchars($user['username']); ?>
                                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                                <span class="badge bg-info ms-1">You</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if ($user['full_name']): ?>
                                                            <div class="full-name">
                                                                <?php echo htmlspecialchars($user['full_name']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="email-info">
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                    <?php if (isset($user['email_verified']) && $user['email_verified']): ?>
                                                        <i class="fas fa-check-circle text-success ms-1" title="Verified"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-exclamation-circle text-warning ms-1" title="Unverified"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($user['is_admin']): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-user-shield me-1"></i>Admin
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-user me-1"></i>User
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $user_status = $user['status'] ?? 'active';
                                                $status_class = match($user_status) {
                                                    'active' => 'bg-success',
                                                    'suspended' => 'bg-warning',
                                                    'banned' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                                $status_icon = match($user_status) {
                                                    'active' => 'fa-check-circle',
                                                    'suspended' => 'fa-pause-circle',
                                                    'banned' => 'fa-ban',
                                                    default => 'fa-question-circle'
                                                };
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <i class="fas <?php echo $status_icon; ?> me-1"></i>
                                                    <?php echo ucfirst($user_status); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="activity-info">
                                                    <div class="activity-stats">
                                                        <span class="stat">
                                                            <i class="fas fa-list me-1"></i>
                                                            <?php echo $user['playlist_count']; ?> playlists
                                                        </span>
                                                        <span class="stat">
                                                            <i class="fas fa-heart me-1"></i>
                                                            <?php echo $user['favorite_count']; ?> favorites
                                                        </span>
                                                        <span class="stat">
                                                            <i class="fas fa-play me-1"></i>
                                                            <?php echo $user['plays_last_30_days']; ?> plays
                                                        </span>
                                                    </div>
                                                    <?php if (isset($user['last_login']) && $user['last_login']): ?>
                                                        <div class="last-login">
                                                            Last: <?php echo date('M j, Y', strtotime($user['last_login'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="join-date">
                                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                type="button" data-bs-toggle="dropdown">
                                                            <i class="fas fa-cogs"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="#" onclick="viewUserDetails(<?php echo $user['id']; ?>)">
                                                                <i class="fas fa-eye me-2"></i>View Details
                                                            </a></li>
                                                            
                                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                                <li><a class="dropdown-item" href="#" onclick="sendNotification(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                                    <i class="fas fa-bell me-2"></i>Send Notification
                                                                </a></li>
                                                                
                                                                <li><hr class="dropdown-divider"></li>
                                                                
                                                                <?php if (!$user['is_admin']): ?>
                                                                    <li><a class="dropdown-item" href="#" onclick="changeUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['status'] ?? 'active'; ?>')">
                                                                        <i class="fas fa-edit me-2"></i>Change Status
                                                                    </a></li>
                                                                    <li><a class="dropdown-item" href="#" onclick="promoteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                                        <i class="fas fa-user-shield me-2"></i>Promote to Admin
                                                                    </a></li>
                                                                <?php else: ?>
                                                                    <li><a class="dropdown-item" href="#" onclick="demoteAdmin(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                                        <i class="fas fa-user me-2"></i>Demote to User
                                                                    </a></li>
                                                                <?php endif; ?>
                                                                
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                                    <i class="fas fa-trash me-2"></i>Delete User
                                                                </a></li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="card-footer">
                                <nav aria-label="Users pagination">
                                    <ul class="pagination justify-content-center mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        
                                        for ($i = $start_page; $i <= $end_page; $i++):
                                        ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                
                                <div class="text-center mt-2">
                                    <small class="text-muted">
                                        Showing <?php echo number_format(($page - 1) * $per_page + 1); ?> to 
                                        <?php echo number_format(min($page * $per_page, $total_users)); ?> of 
                                        <?php echo number_format($total_users); ?> users
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Status Modal -->
<div class="modal fade" id="changeStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change User Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="changeStatusForm">
                <div class="modal-body">
                    <input type="hidden" name="update_user_status" value="1">
                    <input type="hidden" name="user_id" id="statusUserId" value="">
                    
                    <p>Select new status for user <strong id="statusUsername"></strong>:</p>
                    
                    <div class="status-options">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="statusActive" value="active">
                            <label class="form-check-label" for="statusActive">
                                <i class="fas fa-check-circle text-success me-2"></i>Active
                                <div class="form-text">User can access all features normally</div>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="statusSuspended" value="suspended">
                            <label class="form-check-label" for="statusSuspended">
                                <i class="fas fa-pause-circle text-warning me-2"></i>Suspended
                                <div class="form-text">User can login but has limited access</div>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="statusBanned" value="banned">
                            <label class="form-check-label" for="statusBanned">
                                <i class="fas fa-ban text-danger me-2"></i>Banned
                                <div class="form-text">User cannot login or access the system</div>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Send Notification Modal -->
<div class="modal fade" id="sendNotificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="sendNotificationForm">
                <div class="modal-body">
                    <input type="hidden" name="send_notification" value="1">
                    <input type="hidden" name="user_id" id="notificationUserId" value="">
                    
                    <p>Send notification to <strong id="notificationUsername"></strong>:</p>
                    
                    <div class="mb-3">
                        <label for="notificationMessage" class="form-label">Message</label>
                        <textarea class="form-control" id="notificationMessage" name="message" rows="4" 
                                  placeholder="Enter your message..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Notification</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modals -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalTitle">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">
                <!-- Content will be set by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmActionBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Admin Users Page Styles -->
<style>
.admin-users-page {
    min-height: 100vh;
    background: var(--background-main);
}

.page-header {
    background: linear-gradient(135deg, var(--background-card) 0%, var(--background-hover) 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.page-title {
    font-size: 2.5rem;
    font-weight: bold;
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
}

.page-subtitle {
    color: var(--text-light);
    margin: 0;
    font-size: 1.1rem;
}

/* Statistics Cards */
.stat-card {
    background: var(--background-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
    height: 100%;
}

.stat-card:hover {
    background: var(--background-hover);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.stat-icon {
    font-size: 2rem;
    color: var(--primary-color);
    min-width: 50px;
    text-align: center;
}

.stat-info {
    flex: 1;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--text-white);
    line-height: 1;
}

.stat-label {
    font-size: 0.9rem;
    color: var(--text-light);
    margin-top: 0.25rem;
}

/* Filters Section */
.filters-section .card {
    background: var(--background-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.filters-section .form-label {
    color: var(--text-white);
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.filters-section .form-control,
.filters-section .form-select {
    background: var(--background-hover);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--text-white);
    border-radius: 8px;
}

.filters-section .form-control:focus,
.filters-section .form-select:focus {
    background: var(--background-hover);
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(29, 185, 84, 0.25);
    color: var(--text-white);
}

/* Users Table */
.users-table-section .card {
    background: var(--background-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.users-table-section .card-header {
    background: var(--background-hover);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--text-white);
}

.table {
    color: var(--text-white);
    margin-bottom: 0;
}

.table-dark {
    --bs-table-bg: var(--background-hover);
    --bs-table-border-color: rgba(255, 255, 255, 0.1);
}

.table > tbody > tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

.table td {
    border-color: rgba(255, 255, 255, 0.1);
    vertical-align: middle;
    padding: 1rem 0.75rem;
}

/* User Info */
.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar {
    position: relative;
}

.avatar-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--primary-color);
}

.avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
    font-size: 0.9rem;
}

.user-details {
    min-width: 0;
}

.username {
    font-weight: 600;
    color: var(--text-white);
    margin-bottom: 0.25rem;
}

.full-name {
    font-size: 0.85rem;
    color: var(--text-light);
}

.email-info {
    font-size: 0.9rem;
    color: var(--text-light);
}

/* Activity Info */
.activity-info {
    font-size: 0.85rem;
}

.activity-stats {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.activity-stats .stat {
    color: var(--text-light);
    white-space: nowrap;
}

.last-login {
    color: var(--text-light);
    font-size: 0.8rem;
}

.join-date {
    color: var(--text-light);
    font-size: 0.9rem;
}

/* Action Buttons */
.action-buttons {
    text-align: center;
}

.dropdown-menu {
    background: var(--background-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.dropdown-item {
    color: var(--text-white);
    transition: all 0.2s ease;
    padding: 0.5rem 1rem;
}

.dropdown-item:hover {
    background: var(--background-hover);
    color: var(--primary-color);
}

.dropdown-item.text-danger:hover {
    color: #dc3545;
}

/* Modal Styles */
.modal-content {
    background: var(--background-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--text-white);
}

.modal-header,
.modal-footer {
    border-color: rgba(255, 255, 255, 0.1);
}

.modal-title {
    color: var(--text-white);
}

.status-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-check {
    padding: 1rem;
    background: var(--background-hover);
    border-radius: 8px;
    border: 1px solid transparent;
    transition: all 0.3s ease;
}

.form-check:hover {
    border-color: var(--primary-color);
    background: rgba(29, 185, 84, 0.1);
}

.form-check-input:checked + .form-check-label {
    color: var(--primary-color);
}

.form-check-label {
    color: var(--text-white);
    font-weight: 500;
    cursor: pointer;
    width: 100%;
}

.form-text {
    font-size: 0.8rem;
    color: var(--text-light);
    margin-top: 0.25rem;
}

/* Pagination */
.pagination {
    --bs-pagination-bg: var(--background-hover);
    --bs-pagination-border-color: rgba(255, 255, 255, 0.1);
    --bs-pagination-color: var(--text-white);
    --bs-pagination-hover-bg: var(--primary-color);
    --bs-pagination-hover-border-color: var(--primary-color);
    --bs-pagination-hover-color: white;
    --bs-pagination-active-bg: var(--primary-color);
    --bs-pagination-active-border-color: var(--primary-color);
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
    
    .activity-stats {
        display: none;
    }
    
    .user-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .table-responsive {
        font-size: 0.85rem;
    }
}

/* Animations */
.user-row {
    transition: all 0.2s ease;
}

.user-row:hover {
    background: rgba(29, 185, 84, 0.05);
}

.stat-card,
.card {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Badge Styles */
.badge {
    font-size: 0.75rem;
    padding: 0.5em 0.75em;
}

/* Button Styles */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn-outline-light {
    color: var(--text-white);
    border-color: rgba(255, 255, 255, 0.3);
}

.btn-outline-light:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
    color: var(--text-white);
}
</style>

<!-- Admin Users JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.click();
            }
        });
    }, 5000);
});

// Change user status
function changeUserStatus(userId, currentStatus) {
    document.getElementById('statusUserId').value = userId;
    document.getElementById('statusUsername').textContent = 
        document.querySelector(`[data-user-id="${userId}"] .username`).textContent.trim();
    
    // Set current status as selected
    document.getElementById('status' + currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1)).checked = true;
    
    const modal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
    modal.show();
}

// Send notification
function sendNotification(userId, username) {
    document.getElementById('notificationUserId').value = userId;
    document.getElementById('notificationUsername').textContent = username;
    document.getElementById('notificationMessage').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('sendNotificationModal'));
    modal.show();
}

// View user details
function viewUserDetails(userId) {
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    const content = document.getElementById('userDetailsContent');
    
    content.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Loading...</div>';
    modal.show();
    
    // Here you would make an AJAX call to fetch user details
    setTimeout(() => {
        content.innerHTML = `
            <div class="user-details-content">
                <h6>User ID: ${userId}</h6>
                <p>Detailed user information would be loaded here via AJAX.</p>
                <p>This would include:</p>
                <ul>
                    <li>Complete profile information</li>
                    <li>Activity history</li>
                    <li>Login history</li>
                    <li>Playlist and favorite statistics</li>
                    <li>System interactions</li>
                </ul>
            </div>
        `;
    }, 1000);
}

// Promote user
function promoteUser(userId, username) {
    showConfirmModal(
        'Promote User',
        `Are you sure you want to promote "${username}" to admin? This will give them full administrative privileges.`,
        'btn-warning',
        'Promote',
        function() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="promote_user" value="1">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    );
}

// Demote admin
function demoteAdmin(userId, username) {
    showConfirmModal(
        'Demote Admin',
        `Are you sure you want to demote "${username}" from admin to regular user? This will remove their administrative privileges.`,
        'btn-warning',
        'Demote',
        function() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="demote_admin" value="1">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    );
}

// Delete user
function deleteUser(userId, username) {
    showConfirmModal(
        'Delete User',
        `Are you sure you want to permanently delete "${username}"? This action cannot be undone and will remove all their data including playlists, favorites, and play history.`,
        'btn-danger',
        'Delete',
        function() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="delete_user" value="1">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    );
}

// Show confirmation modal
function showConfirmModal(title, message, buttonClass, buttonText, confirmCallback) {
    document.getElementById('confirmModalTitle').textContent = title;
    document.getElementById('confirmModalBody').innerHTML = `<p>${message}</p>`;
    
    const confirmBtn = document.getElementById('confirmActionBtn');
    confirmBtn.className = `btn ${buttonClass}`;
    confirmBtn.textContent = buttonText;
    
    // Remove previous event listeners and add new one
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    newConfirmBtn.addEventListener('click', function() {
        confirmCallback();
        bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
    });
    
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
}

// Export users
function exportUsers() {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', '1');
    window.open(currentUrl.toString(), '_blank');
}

// Refresh table
function refreshTable() {
    window.location.reload();
}

// Auto-submit filters on change
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('.filters-section form');
    const filterInputs = filterForm.querySelectorAll('select, input[type="text"]');
    
    let timeout;
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.type === 'text') {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    filterForm.submit();
                }, 500);
            } else {
                filterForm.submit();
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
