<?php
$pageTitle = 'User Management';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">User Management</h1>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-bold">Search Name/Email</label>
                <input type="text" name="q" class="form-control" value="<?= e($q) ?>" placeholder="Keywords...">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Role</label>
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="editor" <?= $role === 'editor' ? 'selected' : '' ?>>Editor</option>
                    <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="fas fa-search me-2"></i>Find</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end px-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No users found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-3 fw-bold">
                                <?= e($user['name']) ?>
                                <?php if ($user['id'] === currentUserId()): ?>
                                    <span class="badge bg-info ms-1">You</span>
                                <?php endif; ?>
                            </td>
                            <td><a href="mailto:<?= e($user['email']) ?>" class="text-decoration-none"><?= e($user['email']) ?></a></td>
                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php elseif ($user['role'] === 'editor'): ?>
                                    <span class="badge bg-primary">Editor</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                            <td class="text-end px-3">
                                <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($user['id'] !== currentUserId()): ?>
                                    <form method="POST" action="toggle-status.php" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $user['status'] === 'active' ? 'inactive' : 'active' ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-<?= $user['status'] === 'active' ? 'warning' : 'success' ?>" title="<?= $user['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                                            <i class="fas fa-<?= $user['status'] === 'active' ? 'ban' : 'check-circle' ?>"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
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
    <nav class="mt-4">
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
