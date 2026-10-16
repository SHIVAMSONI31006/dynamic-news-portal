<?php
$pageTitle = 'Newsletter Subscribers';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

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

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="h4 mb-0 text-dark fw-bold">Newsletter Subscribers</h2>
            <span class="badge bg-primary rounded-pill"><?= number_format($totalSubs) ?> Total</span>
        </div>
        <p class="text-muted small mb-0">Manage registered newsletter audience and export subscriber lists for campaigns.</p>
    </div>
    <a href="export.php" class="btn btn-outline-success btn-sm px-3 shadow-sm">
        <i class="fas fa-file-csv me-1"></i> Export Audience CSV
    </a>
</div>

<!-- Filters -->
<div class="admin-card mb-4">
    <div class="admin-card-body p-3">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-dark mb-1">Search Subscriber</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted"><i class="fas fa-magnifying-glass"></i></span>
                    <input type="text" name="q" class="form-control" value="<?= e($q) ?>" placeholder="Filter by email address...">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-dark mb-1">Subscription Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active Subscribers</option>
                    <option value="unsubscribed" <?= $status === 'unsubscribed' ? 'selected' : '' ?>>Unsubscribed</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fas fa-filter me-1"></i> Filter</button>
                <?php if ($q !== '' || $status !== ''): ?>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="fas fa-xmark"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Subscribers Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-admin align-middle mb-0">
            <thead>
                <tr>
                    <th class="px-3" style="min-width: 250px;">Subscriber Email</th>
                    <th>Subscription Status</th>
                    <th>Joined Date</th>
                    <th class="text-end px-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscribers)): ?>
                <tr>
                    <td colspan="4" class="text-center py-5">
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon"><i class="fas fa-paper-plane"></i></div>
                            <div class="admin-empty-title">No subscribers found</div>
                            <div class="admin-empty-desc">No email addresses found matching your search criteria.</div>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">View All Subscribers</a>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($subscribers as $sub): ?>
                    <tr>
                        <td class="px-3 fw-semibold">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-envelope-circle-check text-primary opacity-75"></i>
                                <a href="mailto:<?= e($sub['email']) ?>" class="text-dark text-decoration-none">
                                    <?= e($sub['email']) ?>
                                </a>
                            </div>
                        </td>
                        <td>
                            <?php if ($sub['status'] === 'active'): ?>
                                <span class="badge-status-published"><i class="fas fa-circle" style="font-size: 6px;"></i> Active</span>
                            <?php else: ?>
                                <span class="badge-status-draft"><i class="fas fa-circle" style="font-size: 6px;"></i> Unsubscribed</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <div><i class="fas fa-calendar-check me-1 text-muted"></i> <?= date('M j, Y', strtotime($sub['created_at'])) ?></div>
                            <div style="font-size: 0.72rem;"><?= date('g:i A', strtotime($sub['created_at'])) ?></div>
                        </td>
                        <td class="text-end px-3">
                            <div class="d-inline-flex gap-1 align-items-center">
                                <form method="POST" action="toggle-status.php" class="d-inline m-0">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $sub['status'] === 'active' ? 'unsubscribed' : 'active' ?>">
                                    <button type="submit" class="btn-action <?= $sub['status'] === 'active' ? 'btn-action-edit' : 'btn-action-view' ?>" title="<?= $sub['status'] === 'active' ? 'Unsubscribe Email' : 'Reactivate Subscription' ?>">
                                        <i class="fas fa-<?= $sub['status'] === 'active' ? 'bell-slash' : 'bell' ?>"></i>
                                    </button>
                                </form>
                                
                                <form method="POST" action="delete.php" class="d-inline m-0" onsubmit="return confirm('Permanently remove this subscriber from the mailing list?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                    <button type="submit" class="btn-action btn-action-delete" title="Delete Subscriber">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-4" aria-label="Subscribers navigation">
        <ul class="pagination pagination-sm justify-content-center">
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
