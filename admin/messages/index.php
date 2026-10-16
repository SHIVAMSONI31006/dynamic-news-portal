<?php
$pageTitle = 'Contact Messages';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isAdminOrEditor()) {
    setFlashMessage("Access Denied.", "error");
    redirect('admin/index.php');
}

$pdo = connectDB();

$status = get('status', '');

$whereClauses = [];
$params = [];
if ($status !== '' && in_array($status, ['unread', 'read'])) {
    $whereClauses[] = "status = :status";
    $params[':status'] = $status;
}

$whereSQL = count($whereClauses) > 0 ? "WHERE " . implode(' AND ', $whereClauses) : '';

$page = (int)get('page', 1);
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages $whereSQL");
    $countStmt->execute($params);
    $totalMsgs = $countStmt->fetchColumn();
    $totalPages = ceil($totalMsgs / $perPage);
    if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }
    
    $sql = "SELECT id, name, email, subject, status, created_at FROM contact_messages $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage("Failed to retrieve messages.", "error");
    $messages = [];
    $totalPages = 0;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="h4 mb-1 text-dark fw-bold">Public Contact Inquiries</h2>
        <p class="text-muted small mb-0">Review communications, feedback, and reader inquiries submitted via the portal.</p>
    </div>
    <div class="btn-group btn-group-sm shadow-sm" role="group" aria-label="Message Filter">
        <a href="index.php" class="btn <?= $status === '' ? 'btn-primary' : 'btn-outline-secondary' ?>">All Messages</a>
        <a href="index.php?status=unread" class="btn <?= $status === 'unread' ? 'btn-danger text-white' : 'btn-outline-secondary' ?>">
            <i class="fas fa-envelope me-1"></i> Unread
        </a>
        <a href="index.php?status=read" class="btn <?= $status === 'read' ? 'btn-secondary text-white' : 'btn-outline-secondary' ?>">
            <i class="fas fa-envelope-open me-1"></i> Read
        </a>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-admin align-middle mb-0">
            <thead>
                <tr>
                    <th class="px-3" style="min-width: 200px;">Sender</th>
                    <th style="min-width: 260px;">Subject Line</th>
                    <th>Status</th>
                    <th>Received Date</th>
                    <th class="text-end px-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)): ?>
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon"><i class="fas fa-inbox"></i></div>
                            <div class="admin-empty-title">Inbox is clear</div>
                            <div class="admin-empty-desc">No messages found matching your selected filter.</div>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">View All Inquiries</a>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                    <tr class="<?= $msg['status'] === 'unread' ? 'bg-danger bg-opacity-10' : '' ?>">
                        <td class="px-3">
                            <div class="fw-semibold text-dark mb-1">
                                <?php if ($msg['status'] === 'unread'): ?>
                                    <span class="badge bg-danger rounded-pill p-1 me-1"><i class="fas fa-circle" style="font-size: 6px;"></i></span>
                                <?php endif; ?>
                                <?= e($msg['name']) ?>
                            </div>
                            <div class="small">
                                <a href="mailto:<?= e($msg['email']) ?>" class="text-decoration-none text-muted">
                                    <i class="fas fa-envelope me-1 small"></i><?= e($msg['email']) ?>
                                </a>
                            </div>
                        </td>
                        <td>
                            <div class="fw-medium text-dark text-truncate" style="max-width: 320px;">
                                <a href="view.php?id=<?= $msg['id'] ?>" class="text-dark text-decoration-none">
                                    <?= e($msg['subject']) ?>
                                </a>
                            </div>
                        </td>
                        <td>
                            <?php if ($msg['status'] === 'unread'): ?>
                                <span class="badge-status-draft text-danger"><i class="fas fa-circle" style="font-size: 6px;"></i> Unread</span>
                            <?php else: ?>
                                <span class="badge-status-published"><i class="fas fa-circle" style="font-size: 6px;"></i> Read</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <div><i class="fas fa-calendar-days me-1 text-muted"></i> <?= date('M j, Y', strtotime($msg['created_at'])) ?></div>
                            <div style="font-size: 0.72rem;"><?= date('g:i A', strtotime($msg['created_at'])) ?></div>
                        </td>
                        <td class="text-end px-3">
                            <div class="d-inline-flex gap-1 align-items-center">
                                <a href="view.php?id=<?= $msg['id'] ?>" class="btn-action btn-action-view" title="Read Message">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <form method="POST" action="mark-read.php" class="d-inline m-0">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $msg['status'] === 'read' ? 'unread' : 'read' ?>">
                                    <button type="submit" class="btn-action btn-action-edit" title="<?= $msg['status'] === 'read' ? 'Mark as Unread' : 'Mark as Read' ?>">
                                        <i class="fas fa-<?= $msg['status'] === 'read' ? 'envelope' : 'envelope-open' ?>"></i>
                                    </button>
                                </form>
                                
                                <form method="POST" action="delete.php" class="d-inline m-0" onsubmit="return confirm('Permanently delete this inquiry?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                                    <button type="submit" class="btn-action btn-action-delete" title="Delete Inquiry">
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
    <nav class="mt-4" aria-label="Messages navigation">
        <ul class="pagination pagination-sm justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
