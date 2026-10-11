<?php
$pageTitle = 'View Message';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

if (!isAdminOrEditor()) {
    setFlashMessage("Access Denied.", "error");
    redirect('admin/index.php');
}

$id = (int)get('id', 0);
if ($id <= 0) {
    setFlashMessage("Invalid message ID.", "error");
    redirect('admin/messages/index.php');
}

$pdo = connectDB();

try {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $msg = $stmt->fetch();
    
    if (!$msg) {
        setFlashMessage("Message not found.", "error");
        redirect('admin/messages/index.php');
    }
    
    // Automatically mark as read if it was unread
    if ($msg['status'] === 'unread') {
        $updateStmt = $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE id = :id");
        $updateStmt->execute([':id' => $id]);
        $msg['status'] = 'read';
    }
    
} catch (PDOException $e) {
    setFlashMessage("Database error.", "error");
    redirect('admin/messages/index.php');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">View Message</h1>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to List</a>
</div>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><?= e($msg['subject']) ?></h5>
        <span class="text-muted small"><?= date('F j, Y, g:i a', strtotime($msg['created_at'])) ?></span>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <p class="mb-1 text-muted small fw-bold">From</p>
                <p class="mb-0 fs-5"><?= e($msg['name']) ?></p>
                <a href="mailto:<?= e($msg['email']) ?>" class="text-decoration-none"><?= e($msg['email']) ?></a>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <p class="mb-1 text-muted small fw-bold">Status</p>
                <?php if ($msg['status'] === 'unread'): ?>
                    <span class="badge bg-warning text-dark"><i class="fas fa-envelope me-1"></i>Unread</span>
                <?php else: ?>
                    <span class="badge bg-light text-dark border"><i class="fas fa-envelope-open me-1"></i>Read</span>
                <?php endif; ?>
            </div>
        </div>
        
        <hr>
        
        <div class="mt-4">
            <p class="text-muted small fw-bold mb-2">Message</p>
            <div class="p-4 bg-light rounded" style="white-space: pre-wrap; font-size: 1.05rem;"><?= e($msg['message']) ?></div>
        </div>
    </div>
    <div class="card-footer bg-white py-3">
        <form method="POST" action="delete.php" class="d-inline" onsubmit="return confirm('Delete this message permanently?');">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $msg['id'] ?>">
            <button type="submit" class="btn btn-outline-danger">
                <i class="fas fa-trash-alt me-2"></i>Delete Message
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
