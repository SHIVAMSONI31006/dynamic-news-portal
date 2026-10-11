<?php
$pageTitle = 'Contact Messages';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Contact Messages</h1>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="index.php" class="d-flex align-items-center">
            <label class="fw-bold me-3 mb-0">Filter Status:</label>
            <select name="status" class="form-select form-select-sm w-auto d-inline-block me-3" onchange="this.form.submit()">
                <option value="">All Messages</option>
                <option value="unread" <?= $status === 'unread' ? 'selected' : '' ?>>Unread</option>
                <option value="read" <?= $status === 'read' ? 'selected' : '' ?>>Read</option>
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
                        <th class="px-3">Sender</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Received Date</th>
                        <th class="text-end px-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No messages found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                        <tr class="<?= $msg['status'] === 'unread' ? 'table-warning bg-opacity-25' : '' ?>">
                            <td class="px-3">
                                <div class="fw-bold"><?= e($msg['name']) ?></div>
                                <div class="small text-muted"><a href="mailto:<?= e($msg['email']) ?>" class="text-decoration-none text-muted"><?= e($msg['email']) ?></a></div>
                            </td>
                            <td><?= e($msg['subject']) ?></td>
                            <td>
                                <?php if ($msg['status'] === 'unread'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-envelope me-1"></i>Unread</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border"><i class="fas fa-envelope-open me-1"></i>Read</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= date('M j, Y H:i', strtotime($msg['created_at'])) ?></td>
                            <td class="text-end px-3">
                                <a href="view.php?id=<?= $msg['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="View Message">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <form method="POST" action="mark-read.php" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $msg['status'] === 'read' ? 'unread' : 'read' ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-<?= $msg['status'] === 'read' ? 'secondary' : 'success' ?> me-1" title="<?= $msg['status'] === 'read' ? 'Mark as Unread' : 'Mark as Read' ?>">
                                        <i class="fas fa-check-double"></i>
                                    </button>
                                </form>
                                
                                <form method="POST" action="delete.php" class="d-inline" onsubmit="return confirm('Delete this message permanently?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $msg['id'] ?>">
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
