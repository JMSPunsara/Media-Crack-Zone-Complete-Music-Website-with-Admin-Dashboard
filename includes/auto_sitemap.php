<?php
/**
 * Automatic Sitemap Generator for Media Crack Zone
 * Automatically generates and updates sitemap.xml when content changes
 */

require_once dirname(__DIR__) . '/config.php';

class AutoSitemap {
    
    private $pdo;
    private $sitemapPath;
    private $sitemapUrl;
    
    public function __construct() {
        // Use XAMPP default credentials for local development
        try {
            $this->pdo = new PDO("mysql:host=localhost;dbname=media_crack_zone_music", 'root', '');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Fallback to global PDO if available
            global $pdo;
            if ($pdo) {
                $this->pdo = $pdo;
            } else {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        
        $this->sitemapPath = dirname(__DIR__) . '/sitemap.xml';
        $this->sitemapUrl = SITE_URL . '/sitemap.xml';
    }
    
    /**
     * Generate complete sitemap.xml
     */
    public function generateSitemap() {
        try {
            $xml = new DOMDocument('1.0', 'UTF-8');
            $xml->formatOutput = true;
            
            // Create urlset element with namespace
            $urlset = $xml->createElement('urlset');
            $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            $urlset->setAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
            $xml->appendChild($urlset);
            
            // Add static pages
            $this->addStaticPages($xml, $urlset);
            
            // Add track pages
            $this->addTrackPages($xml, $urlset);
            
            // Add artist pages
            $this->addArtistPages($xml, $urlset);
            
            // Add browse pages
            $this->addBrowsePages($xml, $urlset);
            
            // Save sitemap
            $xml->save($this->sitemapPath);
            
            // Update sitemap index file
            $this->updateSitemapIndex();
            
            return [
                'success' => true,
                'message' => 'Sitemap generated successfully',
                'path' => $this->sitemapPath,
                'url' => $this->sitemapUrl,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Add static pages to sitemap
     */
    private function addStaticPages($xml, $urlset) {
        $staticPages = [
            ['url' => SITE_URL, 'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => SITE_URL . '/browse', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => SITE_URL . '/browse.php', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => SITE_URL . '/about', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => SITE_URL . '/contact', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ];
        
        foreach ($staticPages as $page) {
            $url = $xml->createElement('url');
            
            $loc = $xml->createElement('loc', htmlspecialchars($page['url']));
            $url->appendChild($loc);
            
            $lastmod = $xml->createElement('lastmod', date('Y-m-d'));
            $url->appendChild($lastmod);
            
            $changefreq = $xml->createElement('changefreq', $page['changefreq']);
            $url->appendChild($changefreq);
            
            $priority = $xml->createElement('priority', $page['priority']);
            $url->appendChild($priority);
            
            $urlset->appendChild($url);
        }
    }
    
    /**
     * Add track pages to sitemap
     */
    private function addTrackPages($xml, $urlset) {
        $stmt = $this->pdo->query("
            SELECT t.*, 
                   COALESCE(t.updated_at, t.created_at) as last_modified,
                   COALESCE(t.cover_image, 'default-cover.jpg') as cover_image
            FROM tracks t 
            ORDER BY t.id
        ");
        
        while ($track = $stmt->fetch()) {
            $url = $xml->createElement('url');
            
            // Track URL
            $trackUrl = SITE_URL . '/track/' . $track['id'];
            if (!empty($track['seo_slug'])) {
                $trackUrl = SITE_URL . '/track/' . urlencode($track['seo_slug']);
            }
            
            $loc = $xml->createElement('loc', htmlspecialchars($trackUrl));
            $url->appendChild($loc);
            
            $lastmod = $xml->createElement('lastmod', date('Y-m-d', strtotime($track['last_modified'])));
            $url->appendChild($lastmod);
            
            $changefreq = $xml->createElement('changefreq', 'weekly');
            $url->appendChild($changefreq);
            
            $priority = $xml->createElement('priority', '0.8');
            $url->appendChild($priority);
            
            // Add image data if cover exists
            if ($track['cover_image'] && $track['cover_image'] !== 'default-cover.jpg') {
                $image = $xml->createElement('image:image');
                
                $imageLoc = $xml->createElement('image:loc', htmlspecialchars(UPLOAD_URL . 'covers/' . $track['cover_image']));
                $image->appendChild($imageLoc);
                
                $imageTitle = $xml->createElement('image:title', htmlspecialchars($track['title'] . ' by ' . $track['artist']));
                $image->appendChild($imageTitle);
                
                $imageCaption = $xml->createElement('image:caption', htmlspecialchars('Album cover for ' . $track['title']));
                $image->appendChild($imageCaption);
                
                $url->appendChild($image);
            }
            
            $urlset->appendChild($url);
        }
    }
    
    /**
     * Add artist pages to sitemap
     */
    private function addArtistPages($xml, $urlset) {
        $stmt = $this->pdo->query("
            SELECT artist, 
                   COUNT(*) as track_count,
                   MAX(COALESCE(updated_at, created_at)) as last_modified
            FROM tracks 
            GROUP BY artist 
            ORDER BY track_count DESC
        ");
        
        while ($artist = $stmt->fetch()) {
            $url = $xml->createElement('url');
            
            $artistUrl = SITE_URL . '/artist/' . urlencode($artist['artist']);
            $loc = $xml->createElement('loc', htmlspecialchars($artistUrl));
            $url->appendChild($loc);
            
            $lastmod = $xml->createElement('lastmod', date('Y-m-d', strtotime($artist['last_modified'])));
            $url->appendChild($lastmod);
            
            $changefreq = $xml->createElement('changefreq', 'weekly');
            $url->appendChild($changefreq);
            
            $priority = $xml->createElement('priority', '0.7');
            $url->appendChild($priority);
            
            $urlset->appendChild($url);
        }
    }
    
    /**
     * Add browse/category pages to sitemap
     */
    private function addBrowsePages($xml, $urlset) {
        // Language pages
        $stmt = $this->pdo->query("
            SELECT l.*, COUNT(t.id) as track_count 
            FROM languages l 
            LEFT JOIN tracks t ON l.id = t.language_id 
            GROUP BY l.id 
            HAVING track_count > 0
        ");
        
        while ($language = $stmt->fetch()) {
            $url = $xml->createElement('url');
            
            $langUrl = SITE_URL . '/browse?language=' . urlencode($language['code']);
            $loc = $xml->createElement('loc', htmlspecialchars($langUrl));
            $url->appendChild($loc);
            
            $lastmod = $xml->createElement('lastmod', date('Y-m-d'));
            $url->appendChild($lastmod);
            
            $changefreq = $xml->createElement('changefreq', 'daily');
            $url->appendChild($changefreq);
            
            $priority = $xml->createElement('priority', '0.7');
            $url->appendChild($priority);
            
            $urlset->appendChild($url);
        }
        
        // Mood pages
        $stmt = $this->pdo->query("
            SELECT m.*, COUNT(t.id) as track_count 
            FROM moods m 
            LEFT JOIN tracks t ON m.id = t.mood_id 
            GROUP BY m.id 
            HAVING track_count > 0
        ");
        
        while ($mood = $stmt->fetch()) {
            $url = $xml->createElement('url');
            
            $moodUrl = SITE_URL . '/browse?mood=' . urlencode($mood['name']);
            $loc = $xml->createElement('loc', htmlspecialchars($moodUrl));
            $url->appendChild($loc);
            
            $lastmod = $xml->createElement('lastmod', date('Y-m-d'));
            $url->appendChild($lastmod);
            
            $changefreq = $xml->createElement('changefreq', 'daily');
            $url->appendChild($changefreq);
            
            $priority = $xml->createElement('priority', '0.6');
            $url->appendChild($priority);
            
            $urlset->appendChild($url);
        }
    }
    
    /**
     * Update sitemap index file
     */
    private function updateSitemapIndex() {
        $indexPath = dirname(__DIR__) . '/sitemap-index.xml';
        
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $sitemapindex = $xml->createElement('sitemapindex');
        $sitemapindex->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->appendChild($sitemapindex);
        
        $sitemap = $xml->createElement('sitemap');
        
        $loc = $xml->createElement('loc', htmlspecialchars($this->sitemapUrl));
        $sitemap->appendChild($loc);
        
        $lastmod = $xml->createElement('lastmod', date('Y-m-d\TH:i:s+00:00'));
        $sitemap->appendChild($lastmod);
        
        $sitemapindex->appendChild($sitemap);
        
        $xml->save($indexPath);
    }
    
    /**
     * Auto-trigger sitemap generation
     */
    public static function autoGenerate() {
        $sitemap = new self();
        return $sitemap->generateSitemap();
    }
    
    /**
     * Check if sitemap needs update
     */
    public function needsUpdate() {
        if (!file_exists($this->sitemapPath)) {
            return true;
        }
        
        $sitemapTime = filemtime($this->sitemapPath);
        
        // Check if any tracks were modified after sitemap
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM tracks 
            WHERE UNIX_TIMESTAMP(COALESCE(updated_at, created_at)) > ?
        ");
        $stmt->execute([$sitemapTime]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    /**
     * Get sitemap statistics
     */
    public function getStats() {
        $stats = [
            'sitemap_exists' => file_exists($this->sitemapPath),
            'sitemap_size' => file_exists($this->sitemapPath) ? filesize($this->sitemapPath) : 0,
            'last_generated' => file_exists($this->sitemapPath) ? date('Y-m-d H:i:s', filemtime($this->sitemapPath)) : 'Never',
            'needs_update' => $this->needsUpdate()
        ];
        
        // Count URLs in sitemap
        if ($stats['sitemap_exists']) {
            $content = file_get_contents($this->sitemapPath);
            $stats['url_count'] = substr_count($content, '<url>');
        } else {
            $stats['url_count'] = 0;
        }
        
        return $stats;
    }
}

// Auto-generation hooks
class SitemapHooks {
    
    /**
     * Hook for after track insert/update/delete
     */
    public static function onTrackChange() {
        // Delay generation to avoid multiple rapid calls
        $lockFile = dirname(__DIR__) . '/sitemap.lock';
        
        if (!file_exists($lockFile)) {
            touch($lockFile);
            
            // Generate sitemap in background
            register_shutdown_function(function() use ($lockFile) {
                AutoSitemap::autoGenerate();
                if (file_exists($lockFile)) {
                    unlink($lockFile);
                }
            });
        }
    }
    
    /**
     * Scheduled sitemap generation (call this from cron)
     */
    public static function scheduledGeneration() {
        $sitemap = new AutoSitemap();
        
        if ($sitemap->needsUpdate()) {
            return $sitemap->generateSitemap();
        }
        
        return [
            'success' => true,
            'message' => 'Sitemap is up to date',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// If called directly, generate sitemap
if (basename($_SERVER['PHP_SELF']) === 'auto_sitemap.php') {
    header('Content-Type: application/json');
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'generate':
                echo json_encode(AutoSitemap::autoGenerate());
                break;
            case 'stats':
                $sitemap = new AutoSitemap();
                echo json_encode($sitemap->getStats());
                break;
            case 'check':
                $sitemap = new AutoSitemap();
                echo json_encode(['needs_update' => $sitemap->needsUpdate()]);
                break;
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    } else {
        echo json_encode(AutoSitemap::autoGenerate());
    }
}
?>
