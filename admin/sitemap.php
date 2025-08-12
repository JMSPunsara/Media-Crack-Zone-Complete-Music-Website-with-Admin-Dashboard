<?php
require_once '../config.php';
require_once '../includes/auto_sitemap.php';

// Check admin authentication
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$messageType = '';

// Handle actions
if ($_POST) {
    if (isset($_POST['generate_sitemap'])) {
        $result = AutoSitemap::autoGenerate();
        $message = $result['success'] ? $result['message'] : $result['error'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if (isset($_POST['setup_cron'])) {
        // Create .htaccess for sitemap access
        $htaccessContent = "# Sitemap access rules\n";
        $htaccessContent .= "<Files \"sitemap.xml\">\n";
        $htaccessContent .= "    Order allow,deny\n";
        $htaccessContent .= "    Allow from all\n";
        $htaccessContent .= "</Files>\n\n";
        $htaccessContent .= "<Files \"sitemap-index.xml\">\n";
        $htaccessContent .= "    Order allow,deny\n";
        $htaccessContent .= "    Allow from all\n";
        $htaccessContent .= "</Files>\n";
        
        file_put_contents('../.htaccess', $htaccessContent, FILE_APPEND);
        
        $message = "Sitemap access configured. Add this cron job to your server:\n0 */6 * * * /usr/bin/php " . dirname(__DIR__) . "/cron_sitemap.php";
        $messageType = 'info';
    }
}

// Get sitemap statistics
$sitemap = new AutoSitemap();
$stats = $sitemap->getStats();

include '../includes/admin_header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1><i class="fas fa-sitemap me-2"></i>Automatic Sitemap Management</h1>
        <p>Manage your website's XML sitemap for better search engine optimization</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle'); ?> me-2"></i>
        <?php echo nl2br(htmlspecialchars($message)); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Sitemap Statistics -->
        <div class="col-lg-6 mb-4">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h5><i class="fas fa-chart-bar me-2"></i>Sitemap Statistics</h5>
                </div>
                <div class="admin-card-body">
                    <div class="stat-grid">
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php echo $stats['sitemap_exists'] ? 'Yes' : 'No'; ?>
                                <i class="fas fa-<?php echo $stats['sitemap_exists'] ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i>
                            </div>
                            <div class="stat-label">Sitemap Exists</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?php echo number_format($stats['url_count']); ?></div>
                            <div class="stat-label">Total URLs</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?php echo number_format($stats['sitemap_size'] / 1024, 1); ?> KB</div>
                            <div class="stat-label">File Size</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php echo $stats['needs_update'] ? 'Yes' : 'No'; ?>
                                <i class="fas fa-<?php echo $stats['needs_update'] ? 'exclamation-triangle text-warning' : 'check-circle text-success'; ?>"></i>
                            </div>
                            <div class="stat-label">Needs Update</div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <strong>Last Generated:</strong> <?php echo $stats['last_generated']; ?>
                    </div>
                    
                    <?php if ($stats['sitemap_exists']): ?>
                    <div class="mt-3">
                        <a href="../sitemap.xml" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-external-link-alt me-1"></i>View Sitemap
                        </a>
                        <a href="../sitemap-index.xml" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-external-link-alt me-1"></i>View Index
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-6 mb-4">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="admin-card-body">
                    <form method="post" class="d-grid gap-3">
                        <button type="submit" name="generate_sitemap" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-2"></i>Generate Sitemap Now
                        </button>
                        
                        <button type="submit" name="setup_cron" class="btn btn-info">
                            <i class="fas fa-clock me-2"></i>Setup Automatic Updates
                        </button>
                        
                        <a href="../includes/auto_sitemap.php?action=stats" target="_blank" class="btn btn-outline-secondary">
                            <i class="fas fa-code me-2"></i>API Status Check
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- URL Breakdown -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h5><i class="fas fa-list me-2"></i>Sitemap Content Breakdown</h5>
        </div>
        <div class="admin-card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="content-stat">
                        <div class="content-stat-number">
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM tracks");
                            echo number_format($stmt->fetch()['count']);
                            ?>
                        </div>
                        <div class="content-stat-label">Track Pages</div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="content-stat">
                        <div class="content-stat-number">
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(DISTINCT artist) as count FROM tracks");
                            echo number_format($stmt->fetch()['count']);
                            ?>
                        </div>
                        <div class="content-stat-label">Artist Pages</div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="content-stat">
                        <div class="content-stat-number">
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM languages");
                            echo number_format($stmt->fetch()['count']);
                            ?>
                        </div>
                        <div class="content-stat-label">Language Pages</div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="content-stat">
                        <div class="content-stat-number">
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM moods");
                            echo number_format($stmt->fetch()['count']);
                            ?>
                        </div>
                        <div class="content-stat-label">Mood Pages</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Setup Instructions -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h5><i class="fas fa-info-circle me-2"></i>Automatic Sitemap Setup Instructions</h5>
        </div>
        <div class="admin-card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-server me-2"></i>Server Setup (Recommended)</h6>
                    <ol>
                        <li>Add this cron job to your server:</li>
                        <div class="code-block">
                            <code># Update sitemap every 6 hours<br>
                            0 */6 * * * /usr/bin/php <?php echo dirname(__DIR__); ?>/cron_sitemap.php</code>
                        </div>
                        <li>The sitemap will automatically update when tracks are added/modified</li>
                        <li>Search engines will be notified of updates</li>
                    </ol>
                </div>
                
                <div class="col-md-6">
                    <h6><i class="fas fa-browser me-2"></i>Manual Setup</h6>
                    <ol>
                        <li>Use the "Generate Sitemap Now" button above</li>
                        <li>Manually regenerate after adding new content</li>
                        <li>Submit sitemap URL to search engines:</li>
                    </ol>
                    <div class="mt-2">
                        <strong>Sitemap URL:</strong><br>
                        <code><?php echo SITE_URL; ?>/sitemap.xml</code>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h6><i class="fas fa-search me-2"></i>Submit to Search Engines</h6>
                <div class="row">
                    <div class="col-md-6">
                        <a href="https://search.google.com/search-console" target="_blank" class="btn btn-outline-primary btn-sm me-2">
                            <i class="fab fa-google me-1"></i>Google Search Console
                        </a>
                        <a href="https://www.bing.com/webmasters" target="_blank" class="btn btn-outline-info btn-sm">
                            <i class="fab fa-microsoft me-1"></i>Bing Webmaster Tools
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--text-white);
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-light);
}

.content-stat {
    text-align: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 12px;
    color: white;
}

.content-stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.content-stat-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

.code-block {
    background: #1a1a1a;
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid #333;
    margin: 0.5rem 0;
}

.code-block code {
    color: #00ff00;
    font-family: 'Courier New', monospace;
}
</style>

<script>
// Auto-refresh stats every 30 seconds
setInterval(() => {
    fetch('../includes/auto_sitemap.php?action=stats')
        .then(response => response.json())
        .then(data => {
            if (data.needs_update) {
                location.reload();
            }
        })
        .catch(error => console.log('Stats check failed:', error));
}, 30000);

// Show loading state during generation
document.querySelector('form').addEventListener('submit', function(e) {
    if (e.submitter && e.submitter.name === 'generate_sitemap') {
        e.submitter.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
        e.submitter.disabled = true;
    }
});
</script>

<?php include '../includes/admin_footer.php'; ?>
