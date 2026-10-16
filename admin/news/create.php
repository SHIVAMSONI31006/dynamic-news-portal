<?php
$pageTitle = 'Create News';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdmin();

$pdo = connectDB();

// Fetch active categories
$stmt = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
$categories = $stmt->fetchAll();

$title = '';
$category_id = '';
$short_description = '';
$content = '';
$status = 'draft';
$is_featured = 0;
$is_breaking = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken(post('csrf_token'));
    
    $title = trim(post('title'));
    $category_id = (int)post('category_id');
    $short_description = trim(post('short_description'));
    
    // HTML Sanitization using same rules as public display
    $raw_content = post('content');
    $content = strip_tags($raw_content, '<p><br><strong><em><b><i><h2><h3><h4><ul><ol><li><blockquote><a>');
    
    $status = post('status');
    $is_featured = post('is_featured') ? 1 : 0;
    $is_breaking = post('is_breaking') ? 1 : 0;
    $author_id = currentUserId();
    
    $errors = [];
    
    if (empty($title)) $errors[] = "Title is required.";
    if ($category_id <= 0) $errors[] = "Valid category is required.";
    if (empty($short_description)) $errors[] = "Short description is required.";
    if (empty($content)) $errors[] = "Content is required.";
    if (!in_array($status, ['draft', 'published', 'archived'])) $errors[] = "Invalid status.";
    if (preg_match('/\?{2,}/', $title) || preg_match('/\?{2,}/', $short_description) || preg_match('/\?{2,}/', $content)) {
        $errors[] = "Editorial Quality Warning: Suspicious repeated question marks ('??') detected. Please replace with proper punctuation (em-dash '—' or currency '₹').";
    }
    
    // Validate category exists
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = :id AND status = 'active'");
    $stmt->execute([':id' => $category_id]);
    if (!$stmt->fetch()) {
        $errors[] = "Selected category is invalid or inactive.";
    }

    // Image Upload Handling
    $imagePath = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['featured_image'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Image upload error.";
        } else {
            $maxSize = 5 * 1024 * 1024; // 5MB
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
                    $errors[] = "Invalid image format. Only JPG, PNG, and WebP are allowed.";
                } elseif (!getimagesize($file['tmp_name'])) {
                    $errors[] = "File is not a valid image.";
                } else {
                    // Valid image, save it
                    $uploadDir = __DIR__ . '/../../uploads/news/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $filename = uniqid('news_') . '.' . $ext;
                    $destination = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $imagePath = 'uploads/news/' . $filename;
                    } else {
                        $errors[] = "Failed to save uploaded image.";
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            // Generate slug
            $slug = generateSlug($title);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM news WHERE slug = :slug");
            $stmt->execute([':slug' => $slug]);
            if ($stmt->fetchColumn() > 0) {
                $slug .= '-' . time();
            }
            
            $published_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;
            
            $sql = "INSERT INTO news (category_id, author_id, title, slug, short_description, content, featured_image, status, is_featured, is_breaking, published_at) 
                    VALUES (:category_id, :author_id, :title, :slug, :short_description, :content, :featured_image, :status, :is_featured, :is_breaking, :published_at)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':category_id' => $category_id,
                ':author_id' => $author_id,
                ':title' => $title,
                ':slug' => $slug,
                ':short_description' => $short_description,
                ':content' => $content,
                ':featured_image' => $imagePath,
                ':status' => $status,
                ':is_featured' => $is_featured,
                ':is_breaking' => $is_breaking,
                ':published_at' => $published_at
            ]);
            
            setFlashMessage("News article created successfully.", "success");
            redirect('admin/news/index.php');
            
        } catch (PDOException $e) {
            $errors[] = "Database error: Could not save news article.";
            // Delete image if db insert failed
            if ($imagePath && file_exists(__DIR__ . '/../../' . $imagePath)) {
                unlink(__DIR__ . '/../../' . $imagePath);
            }
        }
    }
    
}

require_once __DIR__ . '/../includes/header.php';

if (!empty($errors)) {
    foreach ($errors as $err) {
        echo "<div class='alert alert-danger mx-3 mt-3'>" . e($err) . "</div>";
    }
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="h4 mb-1 text-dark fw-bold">Compose News Story</h2>
        <p class="text-muted small mb-0">Draft a new article, attach high-resolution media, and schedule publication.</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary btn-sm px-3">
        <i class="fas fa-arrow-left me-1"></i> Back to Articles
    </a>
</div>

<div class="admin-card mb-5">
    <div class="admin-card-header py-3">
        <h5 class="admin-card-title mb-0">
            <i class="fas fa-feather-pointed text-danger me-2"></i> Story Content & Configuration
        </h5>
    </div>
    <div class="admin-card-body p-4">
        <form method="POST" action="" enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>
            
            <div class="row g-4">
                <div class="col-lg-8">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label small fw-semibold text-dark mb-1">Headline Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= e($title) ?>" required maxlength="255" placeholder="Enter compelling, fact-checked headline...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="short_description" class="form-label small fw-semibold text-dark mb-1">Lead Excerpt / Summary <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="short_description" name="short_description" rows="2" required maxlength="500" placeholder="A concise 1-2 sentence lead paragraph shown in card previews..."><?= e($short_description) ?></textarea>
                        <div class="form-text">Brief summary displayed on homepage cards and search results.</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="content" class="form-label small fw-semibold text-dark mb-0">Full Story Content <span class="text-danger">*</span></label>
                            <div class="btn-group btn-group-sm" role="group" aria-label="Editor Tag Helpers">
                                <button type="button" class="btn btn-light border btn-sm py-0 px-2" onclick="insertTag('content', '<p>', '</p>')" title="Paragraph">&lt;p&gt;</button>
                                <button type="button" class="btn btn-light border btn-sm py-0 px-2" onclick="insertTag('content', '<strong>', '</strong>')" title="Bold"><i class="fas fa-bold"></i></button>
                                <button type="button" class="btn btn-light border btn-sm py-0 px-2" onclick="insertTag('content', '<em>', '</em>')" title="Italic"><i class="fas fa-italic"></i></button>
                                <button type="button" class="btn btn-light border btn-sm py-0 px-2" onclick="insertTag('content', '<h3>', '</h3>')" title="Heading 3">H3</button>
                                <button type="button" class="btn btn-light border btn-sm py-0 px-2" onclick="insertTag('content', '<blockquote>', '</blockquote>')" title="Blockquote"><i class="fas fa-quote-left"></i></button>
                                <button type="button" class="btn btn-light border btn-sm py-0 px-2" onclick="insertTag('content', '<ul>\n  <li>', '</li>\n</ul>')" title="Bullet List"><i class="fas fa-list-ul"></i></button>
                            </div>
                        </div>
                        <textarea class="form-control" id="content" name="content" rows="14" required placeholder="Write the full report here..."><?= e($content) ?></textarea>
                        <div class="form-text">Clean HTML formatting is supported: &lt;p&gt;, &lt;b&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;h2&gt;, &lt;h3&gt;, &lt;blockquote&gt;, &lt;ul&gt;, &lt;li&gt;.</div>
                    </div>
                    
                </div>
                
                <div class="col-lg-4">
                    
                    <div class="p-3 bg-light rounded border mb-4">
                        <h6 class="fw-bold mb-3 text-dark small text-uppercase" style="letter-spacing: 0.5px;">
                            <i class="fas fa-sliders me-2 text-danger"></i> Editorial Settings
                        </h6>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label small fw-semibold text-dark mb-1">Publication Status</label>
                            <select class="form-select form-select-sm" id="status" name="status">
                                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft (Internal review)</option>
                                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published (Live immediately)</option>
                                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived (Offline)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label small fw-semibold text-dark mb-1">Primary Category <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="category_id" name="category_id" required>
                                <option value="">Select Topic...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" <?= $is_featured ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-semibold" for="is_featured">
                                <i class="fas fa-star text-warning me-1"></i> Featured Story
                            </label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_breaking" name="is_breaking" value="1" <?= $is_breaking ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-semibold" for="is_breaking">
                                <i class="fas fa-bolt text-danger me-1"></i> Breaking Alert
                            </label>
                        </div>
                    </div>
                    
                    <div class="p-3 bg-light rounded border">
                        <h6 class="fw-bold mb-3 text-dark small text-uppercase" style="letter-spacing: 0.5px;">
                            <i class="fas fa-image me-2 text-primary"></i> Cover Image
                        </h6>
                        
                        <div class="mb-3">
                            <label for="featured_image" class="form-label small fw-semibold text-dark mb-1">Upload Photo</label>
                            <input class="form-control form-control-sm" id="featured_image" name="featured_image" type="file" accept=".jpg,.jpeg,.png,.webp">
                            <div id="imagePreviewContainer" class="mt-2 text-center" style="display:none;">
                                <img id="imagePreview" src="#" alt="Preview" class="img-fluid rounded border shadow-sm" style="max-height: 160px;">
                            </div>
                            <div class="form-text small">Max 5MB. Formats: JPG, PNG, WEBP.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-danger w-100 py-2 shadow-sm fw-semibold">
                            <i class="fas fa-check-circle me-1"></i> Save Publication
                        </button>
                    </div>
                    
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function insertTag(textareaId, openTag, closeTag) {
        const el = document.getElementById(textareaId);
        if (!el) return;
        const start = el.selectionStart;
        const end = el.selectionEnd;
        const text = el.value;
        const selected = text.substring(start, end) || 'text';
        const replacement = openTag + selected + closeTag;
        el.value = text.substring(0, start) + replacement + text.substring(end);
        el.focus();
        el.setSelectionRange(start + openTag.length, start + openTag.length + selected.length);
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
