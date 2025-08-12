<?php
/**
 * Database Hooks for Automatic Sitemap Updates
 * Add this to your track management operations
 */

require_once 'auto_sitemap.php';

/**
 * Enhanced PDO wrapper with sitemap auto-update
 */
class SitemapAwarePDO {
    private $pdo;
    private $autoUpdateSitemap;
    
    public function __construct($pdo, $autoUpdateSitemap = true) {
        $this->pdo = $pdo;
        $this->autoUpdateSitemap = $autoUpdateSitemap;
    }
    
    /**
     * Execute query with automatic sitemap update for track operations
     */
    public function execute($query, $params = []) {
        $result = $this->pdo->prepare($query)->execute($params);
        
        // Check if this was a track-related operation
        if ($this->autoUpdateSitemap && $this->isTrackOperation($query)) {
            SitemapHooks::onTrackChange();
        }
        
        return $result;
    }
    
    /**
     * Check if query affects tracks table
     */
    private function isTrackOperation($query) {
        $query = strtolower(trim($query));
        return (
            (strpos($query, 'insert') !== false || 
             strpos($query, 'update') !== false || 
             strpos($query, 'delete') !== false) &&
            strpos($query, 'tracks') !== false
        );
    }
    
    /**
     * Delegate all other calls to the original PDO
     */
    public function __call($method, $args) {
        return call_user_func_array([$this->pdo, $method], $args);
    }
}

/**
 * Track operation functions with automatic sitemap updates
 */
class TrackManager {
    
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Add new track with automatic sitemap update
     */
    public function addTrack($trackData) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO tracks (title, artist, album, file_path, cover_image, language_id, mood_id, duration, uploaded_by, seo_title, seo_description, seo_keywords, seo_slug) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Generate SEO data
            $seoData = $this->generateSEOData($trackData);
            
            $result = $stmt->execute([
                $trackData['title'],
                $trackData['artist'],
                $trackData['album'] ?? null,
                $trackData['file_path'],
                $trackData['cover_image'] ?? 'default-cover.jpg',
                $trackData['language_id'] ?? null,
                $trackData['mood_id'] ?? null,
                $trackData['duration'] ?? 0,
                $trackData['uploaded_by'] ?? null,
                $seoData['title'],
                $seoData['description'],
                $seoData['keywords'],
                $seoData['slug']
            ]);
            
            if ($result) {
                $trackId = $this->pdo->lastInsertId();
                
                // Trigger sitemap update
                SitemapHooks::onTrackChange();
                
                return [
                    'success' => true,
                    'track_id' => $trackId,
                    'message' => 'Track added successfully and sitemap updated'
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to add track'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update track with automatic sitemap update
     */
    public function updateTrack($trackId, $trackData) {
        try {
            $setParts = [];
            $params = [];
            
            foreach ($trackData as $field => $value) {
                if ($field !== 'id') {
                    $setParts[] = "$field = ?";
                    $params[] = $value;
                }
            }
            
            $params[] = $trackId;
            
            $stmt = $this->pdo->prepare("
                UPDATE tracks SET " . implode(', ', $setParts) . ", updated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute($params);
            
            if ($result) {
                // Trigger sitemap update
                SitemapHooks::onTrackChange();
                
                return [
                    'success' => true,
                    'message' => 'Track updated successfully and sitemap updated'
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to update track'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Delete track with automatic sitemap update
     */
    public function deleteTrack($trackId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM tracks WHERE id = ?");
            $result = $stmt->execute([$trackId]);
            
            if ($result) {
                // Trigger sitemap update
                SitemapHooks::onTrackChange();
                
                return [
                    'success' => true,
                    'message' => 'Track deleted successfully and sitemap updated'
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to delete track'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Generate SEO data for track
     */
    private function generateSEOData($trackData) {
        $title = $trackData['title'] . ' - ' . $trackData['artist'] . ' | Free MP3 Download - Media Crack Zone';
        $description = "Listen to and download '{$trackData['title']}' by {$trackData['artist']} for free on Media Crack Zone. High quality MP3 music streaming.";
        $keywords = strtolower($trackData['title']) . ', ' . strtolower($trackData['artist']) . ', free mp3 download, music streaming, high quality music';
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $trackData['title'] . '-' . $trackData['artist']), '-'));
        
        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'slug' => $slug
        ];
    }
}
?>
