<?php
$pageTitle = 'Newsletter Subscribers';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

requireAdmin(); // Admin only

$pdo = connectDB();

$q = trim(get('q', ''));
$status = get('status', '');

$whereClauses = [];
$params = [];

if ($q !== '') {
    $whereClauses[] = "email LIKE :q";
    $params[':q'] = '%' . $q . '%';
}
if ($status !== '' && in_array($status, ['active', 'unsubscribed'])) {
    $whereClauses[] = "status = :status";
    $params[':status'] = $status;
}

$whereSQL = count($whereClauses) > 0 ? "WHERE " . implode(' AND ', $whereClauses) : '';

$page = (int)get('page', 1);
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM newsletter_subscribers $whereSQL");
    $countStmt->execute($params);
    $totalSubs = $countStmt->fetchColumn();
    $totalPages = ceil($totalSubs / $perPage);
    if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }
    
    $sql = "SELECT * FROM newsletter_subscribers $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $subscribers = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage("Failed to retrieve subscribers.", "error");
    $subscribers = [];
    $totalPages = 0;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Newsletter Subscribers</h1>
    <a href="export.php" class="btn btn-outline-success">
        <i class="fas fa-file-csv me-1"></i> Export CSV
    </a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-bold">Search Email</label>
                <input type="text" name="q" class="form-control" value="<?= e($q) ?>" placeholder="Keywords...">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="unsubscribed" <?= $status === 'unsubscribed' ? 'selected' : '' ?>>Unsubscribed</option>
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
                        <th class="px-3">Email</th>
                        <th>Status</th>
                        <th>Subscribed Date</th>
                        <th class="text-end px-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscribers)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No subscribers found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($subscribers as $sub): ?>
                        <tr>
                            <td class="px-3 fw-bold"><a href="mailto:<?= e($sub['email']) ?>" class="text-decoration-none"><?= e($sub['email']) ?></a></td>
                            <td>
                                <?php if ($sub['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Unsubscribed</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= date('M j, Y H:i', strtotime($sub['created_at'])) ?></td>
                            <td class="text-end px-3">
                                <form method="POST" action="toggle-status.php" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $sub['status'] === 'active' ? 'unsubscribed' : 'active' ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-<?= $sub['status'] === 'active' ? 'warning' : 'success' ?> me-1" title="<?= $sub['status'] === 'active' ? 'Unsubscribe' : 'Activate' ?>">
                                        <i class="fas fa-<?= $sub['status'] === 'active' ? 'bell-slash' : 'bell' ?>"></i>
                                    </button>
                                </form>
                                
                                <form method="POST" action="delete.php" class="d-inline" onsubmit="return confirm('Delete this subscriber permanently?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
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
                    <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($q) ?>&status=<?= urlencode($status) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
