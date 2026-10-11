<?php
$pageTitle = 'Edit News';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = connectDB();

$id = (int)get('id', 0);
if ($id <= 0) {
    setFlashMessage("Invalid news ID.", "error");
    redirect('admin/news/index.php');
}

// Fetch active categories
$stmt = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken(post('csrf_token'));
    
    $title = trim(post('title'));
    $category_id = (int)post('category_id');
    $short_description = trim(post('short_description'));
    $raw_content = post('content');
    $content = strip_tags($raw_content, '<p><br><strong><em><b><i><h2><h3><h4><ul><ol><li><blockquote><a>');
    $status = post('status');
    $is_featured = post('is_featured') ? 1 : 0;
    $is_breaking = post('is_breaking') ? 1 : 0;
    
    $errors = [];
    
    if (empty($title)) $errors[] = "Title is required.";
    if ($category_id <= 0) $errors[] = "Valid category is required.";
    if (empty($short_description)) $errors[] = "Short description is required.";
    if (empty($content)) $errors[] = "Content is required.";
    if (!in_array($status, ['draft', 'published', 'archived'])) $errors[] = "Invalid status.";
    if (preg_match('/\?{2,}/', $title) || preg_match('/\?{2,}/', $short_description) || preg_match('/\?{2,}/', $content)) {
        $errors[] = "Editorial Quality Warning: Suspicious repeated question marks ('??') detected. Please replace with proper punctuation (em-dash '—' or currency '₹').";
    }
    
    // Fetch old record for image deletion/retention
    $stmt = $pdo->prepare("SELECT featured_image, slug, status, published_at FROM news WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $oldNews = $stmt->fetch();
    
    if (!$oldNews) {
        $errors[] = "Article not found.";
    }

    $imagePath = $oldNews['featured_image'];
    $imageReplaced = false;

    // Image Upload Handling
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['featured_image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Image upload error.";
        } else {
            $maxSize = 5 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                $errors[] = "Image size exceeds 5MB limit.";
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($mime, $allowedMimes) || !in_array($ext, $allowedExts)) {
                    $errors[] = "Invalid image format.";
                } elseif (!getimagesize($file['tmp_name'])) {
                    $errors[] = "File is not a valid image.";
                } else {
                    $uploadDir = __DIR__ . '/../../uploads/news/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    
                    $filename = uniqid('news_') . '.' . $ext;
                    $destination = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $imagePath = 'uploads/news/' . $filename;
                        $imageReplaced = true;
                    } else {
                        $errors[] = "Failed to save uploaded image.";
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            // Slug management
            $slug = $oldNews['slug']; // By default, keep the same slug
            // If they really want to change the slug (e.g. checkbox "Regenerate slug" wasn't requested, but we can do it if title drastically changed. For safety, we keep existing).
            
            // Handle published_at logic
            $published_at = $oldNews['published_at'];
            if ($oldNews['status'] !== 'published' && $status === 'published' && !$published_at) {
                // Moving from draft to published for the first time
                $published_at = date('Y-m-d H:i:s');
            }

            $sql = "UPDATE news SET 
                        category_id = :category_id, 
                        title = :title, 
                        short_description = :short_description, 
                        content = :content, 
                        featured_image = :featured_image, 
                        status = :status, 
                        is_featured = :is_featured, 
                        is_breaking = :is_breaking, 
                        published_at = :published_at 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':category_id' => $category_id,
                ':title' => $title,
                ':short_description' => $short_description,
                ':content' => $content,
                ':featured_image' => $imagePath,
                ':status' => $status,
                ':is_featured' => $is_featured,
                ':is_breaking' => $is_breaking,
                ':published_at' => $published_at,
                ':id' => $id
            ]);
            
            // Cleanup old image if replaced
            if ($imageReplaced && $oldNews['featured_image']) {
                $oldPath = __DIR__ . '/../../' . $oldNews['featured_image'];
                // Safety check to ensure it's in our uploads folder before unlinking
                if (strpos($oldNews['featured_image'], 'uploads/news/') === 0 && file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            setFlashMessage("News article updated successfully.", "success");
            redirect('admin/news/index.php');
            
        } catch (PDOException $e) {
            $errors[] = "Database error: Could not update news article.";
            // Delete newly uploaded image if db insert failed
            if ($imageReplaced && file_exists(__DIR__ . '/../../' . $imagePath)) {
                unlink(__DIR__ . '/../../' . $imagePath);
            }
        }
    }
    
    // Show errors if any
    if (!empty($errors)) {
        foreach ($errors as $err) {
            echo "<div class='alert alert-danger mx-3 mt-3'>" . e($err) . "</div>";
        }
    }
}

// Fetch current data
try {
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $news = $stmt->fetch();
    
    if (!$news) {
        setFlashMessage("News article not found.", "error");
        redirect('admin/news/index.php');
    }
    
    $title = $_POST['title'] ?? $news['title'];
    $category_id = $_POST['category_id'] ?? $news['category_id'];
    $short_description = $_POST['short_description'] ?? $news['short_description'];
    $content = $_POST['content'] ?? $news['content'];
    $status = $_POST['status'] ?? $news['status'];
    $is_featured = isset($_POST['is_featured']) ? 1 : (empty($_POST) ? $news['is_featured'] : 0);
    $is_breaking = isset($_POST['is_breaking']) ? 1 : (empty($_POST) ? $news['is_breaking'] : 0);
    
} catch (PDOException $e) {
    setFlashMessage("Database error.", "error");
    redirect('admin/news/index.php');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit News</h1>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to List</a>
</div>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <?= csrfField() ?>
            
            <div class="row">
                <div class="col-lg-8">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label fw-bold">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= e($title) ?>" required maxlength="255">
                    </div>
                    
                    <div class="mb-3">
                        <label for="short_description" class="form-label fw-bold">Short Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="short_description" name="short_description" rows="2" required maxlength="500"><?= e($short_description) ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label fw-bold">Content <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="content" name="content" rows="15" required><?= e($content) ?></textarea>
                        <div class="form-text">Basic HTML tags allowed (p, b, i, strong, em, h2, h3, h4, ul, ol, li, blockquote, a).</div>
                    </div>
                    
                </div>
                
                <div class="col-lg-4">
                    
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="fas fa-cogs me-2"></i>Publishing Settings</h6>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                                    <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category...</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" <?= $is_featured ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_featured">Mark as Featured</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_breaking" name="is_breaking" value="1" <?= $is_breaking ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_breaking">Mark as Breaking News</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="fas fa-image me-2"></i>Featured Image</h6>
                            
                            <?php if ($news['featured_image']): ?>
                                <div class="mb-3">
                                    <label class="form-label d-block text-muted small">Current Image</label>
                                    <img src="<?= buildUrl($news['featured_image']) ?>" alt="Current Image" class="img-fluid rounded border mb-2" style="max-height: 150px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="featured_image" class="form-label text-muted small">Replace Image (Leave blank to keep current)</label>
                                <input class="form-control form-control-sm" id="featured_image" name="featured_image" type="file" accept=".jpg,.jpeg,.png,.webp">
                                <div class="form-text">Max 5MB. Formats: JPG, PNG, WEBP.</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mt-2"><i class="fas fa-save me-2"></i>Update Article</button>
                        </div>
                    </div>
                    
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
