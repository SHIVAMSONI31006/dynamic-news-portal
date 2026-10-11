<?php
$pageTitle = 'Comments Management';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';



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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Comments Management</h1>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="index.php" class="d-flex align-items-center">
            <label class="fw-bold me-3 mb-0">Filter Status:</label>
            <select name="status" class="form-select form-select-sm w-auto d-inline-block me-3" onchange="this.form.submit()">
                <option value="">All Comments</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-3" style="width: 25%;">Article / User</th>
                        <th style="width: 40%;">Comment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end px-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($comments)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No comments found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td class="px-3">
                                <div class="fw-bold text-truncate" style="max-width: 200px;" title="<?= e($comment['news_title']) ?>">
                                    <?= e($comment['news_title']) ?>
                                </div>
                                <div class="small text-muted"><i class="fas fa-user me-1"></i><?= e($comment['user_name']) ?></div>
                            </td>
                            <td>
                                <div class="small" style="max-height: 60px; overflow-y: auto;">
                                    <?= e($comment['comment']) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($comment['status'] === 'approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php elseif ($comment['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= date('M j, Y H:i', strtotime($comment['created_at'])) ?></td>
                            <td class="text-end px-3">
                                <form method="POST" action="moderate.php" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                                    
                                    <?php if ($comment['status'] !== 'approved'): ?>
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-outline-success me-1" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($comment['status'] !== 'rejected'): ?>
                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-warning me-1" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                </form>
                                
                                <form method="POST" action="delete.php" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this comment?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $comment['id'] ?>">
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
                    <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
