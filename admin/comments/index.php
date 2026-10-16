<?php
$pageTitle = 'Comments Management';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$pdo = connectDB();

$status = get('status', '');

$whereClauses = [];
$params = [];
if ($status !== '' && in_array($status, ['pending', 'approved', 'rejected'])) {
    $whereClauses[] = "c.status = :status";
    $params[':status'] = $status;
}

$whereSQL = count($whereClauses) > 0 ? "WHERE " . implode(' AND ', $whereClauses) : '';

$page = (int)get('page', 1);
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM comments c $whereSQL");
    $countStmt->execute($params);
    $totalComments = $countStmt->fetchColumn();
    $totalPages = ceil($totalComments / $perPage);
    if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }
    
    $sql = "
        SELECT c.*, n.title as news_title, u.name as user_name 
        FROM comments c
        JOIN news n ON c.news_id = n.id
        JOIN users u ON c.user_id = u.id
        $whereSQL
        ORDER BY c.created_at DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $comments = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage("Failed to retrieve comments.", "error");
    $comments = [];
    $totalPages = 0;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="h4 mb-1 text-dark fw-bold">Community Comments</h2>
        <p class="text-muted small mb-0">Review, approve, or reject reader discussions submitted on news articles.</p>
    </div>
    <div class="btn-group btn-group-sm shadow-sm" role="group" aria-label="Comment Status Filter">
        <a href="index.php" class="btn <?= $status === '' ? 'btn-primary' : 'btn-outline-secondary' ?>">All Comments</a>
        <a href="index.php?status=pending" class="btn <?= $status === 'pending' ? 'btn-warning text-dark' : 'btn-outline-secondary' ?>">
            <i class="fas fa-hourglass-half me-1"></i> Pending
        </a>
        <a href="index.php?status=approved" class="btn <?= $status === 'approved' ? 'btn-success text-white' : 'btn-outline-secondary' ?>">
            <i class="fas fa-check me-1"></i> Approved
        </a>
        <a href="index.php?status=rejected" class="btn <?= $status === 'rejected' ? 'btn-danger text-white' : 'btn-outline-secondary' ?>">
            <i class="fas fa-ban me-1"></i> Rejected
        </a>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-admin align-middle mb-0">
            <thead>
                <tr>
                    <th class="px-3" style="width: 25%;">Article & Reader</th>
                    <th style="width: 42%;">Comment Message</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th class="text-end px-3">Moderation</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($comments)): ?>
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon"><i class="fas fa-comments"></i></div>
                            <div class="admin-empty-title">No comments found</div>
                            <div class="admin-empty-desc">No comments currently match the selected moderation status.</div>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">View All Comments</a>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td class="px-3">
                            <div class="fw-semibold text-truncate mb-1" style="max-width: 220px;" title="<?= e($comment['news_title']) ?>">
                                <a href="<?= buildUrl('admin/news/edit.php?id=' . $comment['news_id']) ?>" class="text-dark text-decoration-none">
                                    <i class="fas fa-newspaper me-1 text-muted"></i><?= e($comment['news_title']) ?>
                                </a>
                            </div>
                            <div class="small text-muted d-flex align-items-center gap-1">
                                <i class="fas fa-user-circle text-secondary"></i>
                                <span class="fw-medium text-dark"><?= e($comment['user_name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="p-2 bg-light rounded border small" style="max-height: 80px; overflow-y: auto; line-height: 1.45;">
                                <?= nl2br(e($comment['comment'])) ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($comment['status'] === 'approved'): ?>
                                <span class="badge-status-published"><i class="fas fa-circle" style="font-size: 6px;"></i> Approved</span>
                            <?php elseif ($comment['status'] === 'rejected'): ?>
                                <span class="badge-status-archived"><i class="fas fa-circle" style="font-size: 6px;"></i> Rejected</span>
                            <?php else: ?>
                                <span class="badge-status-draft"><i class="fas fa-circle" style="font-size: 6px;"></i> Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <div><i class="fas fa-clock me-1 text-muted"></i> <?= date('M j, Y', strtotime($comment['created_at'])) ?></div>
                            <div class="text-muted" style="font-size: 0.72rem;"><?= date('g:i A', strtotime($comment['created_at'])) ?></div>
                        </td>
                        <td class="text-end px-3">
                            <div class="d-inline-flex gap-1 align-items-center">
                                <form method="POST" action="moderate.php" class="d-inline m-0">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                                    
                                    <?php if ($comment['status'] !== 'approved'): ?>
                                    <button type="submit" name="action" value="approve" class="btn-action btn-action-view" title="Approve Comment">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($comment['status'] !== 'rejected'): ?>
                                    <button type="submit" name="action" value="reject" class="btn-action btn-action-edit" title="Reject Comment">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    <?php endif; ?>
                                </form>
                                
                                <form method="POST" action="delete.php" class="d-inline m-0" onsubmit="return confirm('Permanently delete this comment?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                                    <button type="submit" class="btn-action btn-action-delete" title="Delete Comment">
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
    <nav class="mt-4" aria-label="Comments navigation">
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
