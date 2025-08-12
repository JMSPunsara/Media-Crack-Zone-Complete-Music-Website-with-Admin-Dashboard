<?php
/**
 * Cron Job Script for Automatic Sitemap Generation
 * Add this to your server's cron jobs to run every hour or daily
 * 
 * Cron example (runs every hour):
 * 0 * * * * /usr/bin/php /path/to/your/website/cron_sitemap.php
 * 
 * Or daily at 2 AM:
 * 0 2 * * * /usr/bin/php /path/to/your/website/cron_sitemap.php
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/includes/auto_sitemap.php';

// Log function
function logMessage($message) {
    $logFile = dirname(__FILE__) . '/logs/sitemap_cron.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    
    // Keep log files under 1MB
    if (file_exists($logFile) && filesize($logFile) > 1024 * 1024) {
        $lines = file($logFile);
        $lines = array_slice($lines, -500); // Keep last 500 lines
        file_put_contents($logFile, implode('', $lines));
    }
}

try {
    logMessage("Starting sitemap generation cron job");
    
    // Check if sitemap needs update
    $sitemap = new AutoSitemap();
    
    if ($sitemap->needsUpdate()) {
        logMessage("Sitemap needs update, generating...");
        
        $result = $sitemap->generateSitemap();
        
        if ($result['success']) {
            logMessage("Sitemap generated successfully: " . $result['message']);
            
            // Ping search engines about the update
            pingSearchEngines();
            
        } else {
            logMessage("ERROR: Failed to generate sitemap - " . $result['error']);
        }
    } else {
        logMessage("Sitemap is up to date, no generation needed");
    }
    
    // Clean up old log files
    cleanupLogs();
    
    logMessage("Cron job completed successfully");
    
} catch (Exception $e) {
    logMessage("FATAL ERROR: " . $e->getMessage());
    exit(1);
}

/**
 * Ping search engines about sitemap update
 */
function pingSearchEngines() {
    $sitemapUrl = urlencode(SITE_URL . '/sitemap.xml');
    
    $searchEngines = [
        'Google' => "https://www.google.com/ping?sitemap=$sitemapUrl",
        'Bing' => "https://www.bing.com/ping?sitemap=$sitemapUrl",
    ];
    
    foreach ($searchEngines as $engine => $pingUrl) {
        $response = @file_get_contents($pingUrl, false, stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; SitemapPinger/1.0)'
            ]
        ]));
        
        if ($response !== false) {
            logMessage("Successfully pinged $engine about sitemap update");
        } else {
            logMessage("WARNING: Failed to ping $engine about sitemap update");
        }
    }
}

/**
 * Clean up old log files
 */
function cleanupLogs() {
    $logsDir = dirname(__FILE__) . '/logs';
    
    if (is_dir($logsDir)) {
        $files = glob($logsDir . '/*.log');
        
        foreach ($files as $file) {
            // Delete log files older than 30 days
            if (filemtime($file) < time() - (30 * 24 * 60 * 60)) {
                unlink($file);
                logMessage("Cleaned up old log file: " . basename($file));
            }
        }
    }
}

// If running from command line, exit cleanly
if (php_sapi_name() === 'cli') {
    exit(0);
}
?>
