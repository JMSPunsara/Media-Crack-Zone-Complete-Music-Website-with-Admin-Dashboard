<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

$error = '';
$success = '';

// Handle language management
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_language'])) {
        $name = sanitize($_POST['language_name']);
        $code = sanitize($_POST['language_code']);
        
        if (empty($name) || empty($code)) {
            $error = 'Language name and code are required.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO languages (name, code) VALUES (?, ?)");
            if ($stmt->execute([$name, $code])) {
                $success = 'Language added successfully!';
            } else {
                $error = 'Failed to add language.';
            }
        }
    }
    
    if (isset($_POST['add_mood'])) {
        $name = sanitize($_POST['mood_name']);
        $description = sanitize($_POST['mood_description']);
        $color = sanitize($_POST['mood_color']);
        
        if (empty($name)) {
            $error = 'Mood name is required.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO moods (name, description, color) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $description, $color])) {
                $success = 'Mood added successfully!';
            } else {
                $error = 'Failed to add mood.';
            }
        }
    }
    
    if (isset($_POST['delete_language'])) {
        $id = (int)$_POST['language_id'];
        $stmt = $pdo->prepare("DELETE FROM languages WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = 'Language deleted successfully!';
        } else {
            $error = 'Failed to delete language.';
        }
    }
    
    if (isset($_POST['delete_mood'])) {
        $id = (int)$_POST['mood_id'];
        $stmt = $pdo->prepare("DELETE FROM moods WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = 'Mood deleted successfully!';
        } else {
            $error = 'Failed to delete mood.';
        }
    }
}

// Get all languages
$stmt = $pdo->prepare("
    SELECT l.*, COUNT(t.id) as track_count 
    FROM languages l 
    LEFT JOIN tracks t ON l.id = t.language_id 
    GROUP BY l.id 
    ORDER BY l.name
");
$stmt->execute();
$languages = $stmt->fetchAll();

// Get all moods
$stmt = $pdo->prepare("
    SELECT m.*, COUNT(t.id) as track_count 
    FROM moods m 
    LEFT JOIN tracks t ON m.id = t.mood_id 
    GROUP BY m.id 
    ORDER BY m.name
");
$stmt->execute();
$moods = $stmt->fetchAll();

$pageTitle = 'Manage Categories';
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold">
        <i class="fas fa-tags me-2 text-warning"></i>Manage Categories
    </h1>
    <a href="index.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Languages Section -->
    <div class="col-md-6 mb-4">
        <div class="card-custom p-4">
            <h4 class="mb-4">
                <i class="fas fa-language me-2 text-success"></i>Languages
            </h4>
            
            <!-- Add Language Form -->
            <form method="POST" action="" class="mb-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <input type="text" class="form-control" name="language_name" placeholder="Language name" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <input type="text" class="form-control" name="language_code" placeholder="Code (e.g., en)" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <button type="submit" name="add_language" class="btn btn-success w-100">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Languages List -->
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Tracks</th>
                            <th width="50">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($languages as $language): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($language['name']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($language['code']); ?></span></td>
                            <td><?php echo number_format($language['track_count']); ?></td>
                            <td>
                                <?php if ($language['track_count'] == 0): ?>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete this language?')">
                                    <input type="hidden" name="language_id" value="<?php echo $language['id']; ?>">
                                    <button type="submit" name="delete_language" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted" title="Cannot delete - has tracks">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($languages)): ?>
            <div class="text-center py-3 text-muted">
                <i class="fas fa-language mb-2" style="font-size: 2rem;"></i>
                <p>No languages added yet</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Moods Section -->
    <div class="col-md-6 mb-4">
        <div class="card-custom p-4">
            <h4 class="mb-4">
                <i class="fas fa-palette me-2 text-info"></i>Moods
            </h4>
            
            <!-- Add Mood Form -->
            <form method="POST" action="" class="mb-4">
                <div class="mb-3">
                    <input type="text" class="form-control" name="mood_name" placeholder="Mood name" required>
                </div>
                <div class="mb-3">
                    <textarea class="form-control" name="mood_description" placeholder="Description (optional)" rows="2"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small">Color</label>
                        <input type="color" class="form-control form-control-color" name="mood_color" value="#3498db">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small">&nbsp;</label>
                        <button type="submit" name="add_mood" class="btn btn-info w-100">
                            <i class="fas fa-plus me-2"></i>Add Mood
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Moods List -->
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th width="50">Color</th>
                            <th>Name</th>
                            <th>Tracks</th>
                            <th width="50">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($moods as $mood): ?>
                        <tr>
                            <td>
                                <div class="rounded-circle" style="width: 30px; height: 30px; background-color: <?php echo $mood['color']; ?>"></div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($mood['name']); ?></div>
                                <?php if ($mood['description']): ?>
                                <small class="text-muted"><?php echo htmlspecialchars($mood['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($mood['track_count']); ?></td>
                            <td>
                                <?php if ($mood['track_count'] == 0): ?>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete this mood?')">
                                    <input type="hidden" name="mood_id" value="<?php echo $mood['id']; ?>">
                                    <button type="submit" name="delete_mood" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted" title="Cannot delete - has tracks">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($moods)): ?>
            <div class="text-center py-3 text-muted">
                <i class="fas fa-palette mb-2" style="font-size: 2rem;"></i>
                <p>No moods added yet</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Popular Categories Overview -->
<div class="row">
    <div class="col-12">
        <div class="card-custom p-4">
            <h4 class="mb-4">
                <i class="fas fa-chart-bar me-2 text-primary"></i>Category Statistics
            </h4>
            
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-success mb-3">Most Popular Languages</h6>
                    <?php 
                    $popular_languages = array_slice(array_filter($languages, function($l) { return $l['track_count'] > 0; }), 0, 5);
                    usort($popular_languages, function($a, $b) { return $b['track_count'] - $a['track_count']; });
                    ?>
                    
                    <?php if (empty($popular_languages)): ?>
                    <p class="text-muted">No tracks uploaded yet</p>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($popular_languages as $lang): ?>
                        <div class="list-group-item bg-transparent border-0 px-0 py-2">
                            <div class="d-flex justify-content-between">
                                <span><?php echo htmlspecialchars($lang['name']); ?></span>
                                <span class="badge bg-success"><?php echo $lang['track_count']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <h6 class="text-info mb-3">Most Popular Moods</h6>
                    <?php 
                    $popular_moods = array_slice(array_filter($moods, function($m) { return $m['track_count'] > 0; }), 0, 5);
                    usort($popular_moods, function($a, $b) { return $b['track_count'] - $a['track_count']; });
                    ?>
                    
                    <?php if (empty($popular_moods)): ?>
                    <p class="text-muted">No tracks uploaded yet</p>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($popular_moods as $mood): ?>
                        <div class="list-group-item bg-transparent border-0 px-0 py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle me-2" style="width: 20px; height: 20px; background-color: <?php echo $mood['color']; ?>"></div>
                                    <span><?php echo htmlspecialchars($mood['name']); ?></span>
                                </div>
                                <span class="badge bg-info"><?php echo $mood['track_count']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
