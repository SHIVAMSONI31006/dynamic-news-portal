<?php
$pageTitle = 'User Management';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// ONLY Admin can access users. Editors are blocked.
if (!isAdmin()) {
    setFlashMessage("Access Denied.", "error");
    redirect('admin/index.php'); // Or to 403
}

$pdo = connectDB();

$q = trim(get('q', ''));
$role = get('role', '');
$status = get('status', '');

$whereClauses = [];
$params = [];

if ($q !== '') {
    $whereClauses[] = "(name LIKE :q OR email LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
if ($role !== '' && in_array($role, ['admin', 'editor', 'user'])) {
    $whereClauses[] = "role = :role";
    $params[':role'] = $role;
}
if ($status !== '' && in_array($status, ['active', 'inactive'])) {
    $whereClauses[] = "status = :status";
    $params[':status'] = $status;
}

$whereSQL = '';
if (count($whereClauses) > 0) {
    $whereSQL = "WHERE " . implode(' AND ', $whereClauses);
}

// Pagination
$page = (int)get('page', 1);
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSQL");
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);
    if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }
    
    $sql = "SELECT id, name, email, role, status, created_at FROM users $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage("Failed to retrieve users.", "error");
    $users = [];
    $totalPages = 0;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h1 class="admin-page-title mb-1">User Management</h1>
        <p class="admin-page-subtitle mb-0">Control administrator privileges, editorial staff permissions, and reader accounts.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="create.php" class="btn btn-primary shadow-sm">
            <i class="fas fa-user-plus me-2"></i> Add New User
        </a>
    </div>
</div>

<!-- Search & Filters -->
<div class="admin-card mb-4">
    <div class="admin-card-body p-3">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted mb-1" for="filterSearch">Search Name or Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="filterSearch" name="q" class="form-control" value="<?= e($q) ?>" placeholder="Search by name, email...">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1" for="filterRole">Role</label>
                <select id="filterRole" name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    <option value="editor" <?= $role === 'editor' ? 'selected' : '' ?>>Editor</option>
                    <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User / Reader</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1" for="filterStatus">Status</label>
                <select id="filterStatus" name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
                <?php if ($q !== '' || $role !== '' || $status !== ''): ?>
                    <a href="index.php" class="btn btn-outline-secondary" title="Clear Filters" aria-label="Clear Filters">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Users Table Card -->
<div class="admin-card">
    <div class="admin-card-header d-flex justify-content-between align-items-center">
        <h5 class="admin-card-title mb-0">
            <i class="fas fa-users-gear me-2 text-primary"></i> System Users
        </h5>
        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill font-monospace">
            <?= number_format($totalUsers ?? 0) ?> Total User<?= ($totalUsers ?? 0) === 1 ? '' : 's' ?>
        </span>
    </div>
    <div class="admin-card-body p-0">
        <div class="table-responsive">
            <table class="table table-admin align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-users-slash fs-1 text-muted opacity-50 mb-3 d-block"></i>
                            <h6 class="fw-bold mb-1">No users match your criteria</h6>
                            <p class="small mb-3">Try adjusting your search keywords or active role filters.</p>
                            <?php if ($q !== '' || $role !== '' || $status !== ''): ?>
                                <a href="index.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-arrow-rotate-left me-1"></i> Reset Filters
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-size: 0.95rem; flex-shrink: 0;">
                                        <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                            <?= e($user['name']) ?>
                                            <?php if ($user['id'] === currentUserId()): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small px-2 py-0.5">You</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">ID: #<?= (int)$user['id'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="mailto:<?= e($user['email']) ?>" class="text-decoration-none text-dark fw-medium small">
                                    <i class="fas fa-envelope me-1 text-muted opacity-75"></i> <?= e($user['email']) ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill">
                                        <i class="fas fa-shield-halved me-1"></i> Admin
                                    </span>
                                <?php elseif ($user['role'] === 'editor'): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 rounded-pill">
                                        <i class="fas fa-pen-nib me-1"></i> Editor
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1.5 rounded-pill">
                                        <i class="fas fa-user me-1"></i> Reader
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill">
                                        <i class="fas fa-circle-check me-1"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1.5 rounded-pill">
                                        <i class="fas fa-circle-pause me-1"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted">
                                <?= date('M j, Y', strtotime($user['created_at'])) ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex gap-1">
                                    <a href="edit.php?id=<?= $user['id'] ?>" class="btn-action edit" title="Edit User" aria-label="Edit User <?= e($user['name']) ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id'] !== currentUserId()): ?>
                                        <form method="POST" action="toggle-status.php" class="d-inline" onsubmit="return confirm('Are you sure you want to <?= $user['status'] === 'active' ? 'deactivate' : 'activate' ?> user <?= e(addslashes($user['name'])) ?>?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= $user['status'] === 'active' ? 'inactive' : 'active' ?>">
                                            <button type="submit" class="btn-action <?= $user['status'] === 'active' ? 'delete' : 'view' ?>" title="<?= $user['status'] === 'active' ? 'Deactivate User' : 'Activate User' ?>" aria-label="<?= $user['status'] === 'active' ? 'Deactivate' : 'Activate' ?> User">
                                                <i class="fas fa-<?= $user['status'] === 'active' ? 'ban' : 'check' ?>"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-4" aria-label="User Pagination">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($q) ?>&role=<?= urlencode($role) ?>&status=<?= urlencode($status) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
