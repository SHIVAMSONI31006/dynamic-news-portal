<?php
$pageTitle = 'View Message';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

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

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="h4 mb-1 text-dark fw-bold">Inquiry Details</h2>
        <p class="text-muted small mb-0">Message ID #<?= $msg['id'] ?> received on <?= date('F j, Y \a\t g:i A', strtotime($msg['created_at'])) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="mailto:<?= e($msg['email']) ?>?subject=Re:%20<?= urlencode($msg['subject']) ?>" class="btn btn-primary btn-sm px-3 shadow-sm">
            <i class="fas fa-reply me-1"></i> Reply via Email
        </a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm px-3">
            <i class="fas fa-arrow-left me-1"></i> Back to Inbox
        </a>
    </div>
</div>

<div class="admin-card mb-5">
    <div class="admin-card-header py-3 d-flex justify-content-between align-items-center">
        <h5 class="admin-card-title mb-0">
            <i class="fas fa-envelope-open-text text-danger me-2"></i> <?= e($msg['subject']) ?>
        </h5>
        <span class="badge-status-published"><i class="fas fa-circle" style="font-size: 6px;"></i> Read</span>
    </div>
    <div class="admin-card-body p-4">
        
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 mb-4 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 46px; height: 46px; border-radius: 50%; background: #0B132B; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem;">
                    <?= strtoupper(substr(e($msg['name']), 0, 1)) ?>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark"><?= e($msg['name']) ?></h6>
                    <a href="mailto:<?= e($msg['email']) ?>" class="text-primary small text-decoration-none">
                        <?= e($msg['email']) ?>
                    </a>
                </div>
            </div>
            <div class="text-md-end text-muted small">
                <div><i class="fas fa-clock me-1"></i> Received <?= date('F j, Y, g:i A', strtotime($msg['created_at'])) ?></div>
            </div>
        </div>
        
        <div class="mb-4">
            <label class="form-label small fw-bold text-uppercase text-muted" style="letter-spacing: 0.5px;">Message Body</label>
            <div class="p-4 bg-light rounded border" style="white-space: pre-wrap; font-size: 1rem; line-height: 1.6; color: #1E293B;">
                <?= e($msg['message']) ?>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
            <a href="mailto:<?= e($msg['email']) ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-paper-plane me-1"></i> Direct Email Reply
            </a>
            
            <form method="POST" action="delete.php" class="d-inline m-0" onsubmit="return confirm('Permanently delete this message?');">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-trash-can me-1"></i> Delete Message
                </button>
            </form>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
