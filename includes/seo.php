<?php
// Global SEO variables
$seo_title = '';
$seo_description = '';
$seo_keywords = '';
$seo_image = '';
$seo_url = '';

/**
 * Set page SEO meta data
 */
function setPageSEO($title, $description, $keywords = '', $image = '', $url = '') {
    global $seo_title, $seo_description, $seo_keywords, $seo_image, $seo_url;
    
    $seo_title = $title;
    $seo_description = $description;
    $seo_keywords = $keywords;
    $seo_image = $image ?: (SITE_URL . '/assets/images/og-default.jpg');
    $seo_url = $url ?: getCurrentUrl();
}

/**
 * Set track-specific SEO data
 */
function setTrackSEO($track) {
    $title = $track['seo_title'] ?: ($track['title'] . ' - ' . $track['artist'] . ' | Free MP3 Download - Media Crack Zone');
    $description = $track['seo_description'] ?: ("Listen to and download '{$track['title']}' by {$track['artist']} for free on Media Crack Zone. High quality MP3 music streaming.");
    $keywords = $track['seo_keywords'] ?: (strtolower($track['title']) . ', ' . strtolower($track['artist']) . ', free mp3 download, music streaming');
    $image = UPLOAD_URL . 'covers/' . $track['cover_image'];
    $url = SITE_URL . '/track/' . ($track['seo_slug'] ?: $track['id']);
    
    setPageSEO($title, $description, $keywords, $image, $url);
}

/**
 * Get current URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}
/**
 * SEO Helper Functions for Music Platform
 * Automatically generates SEO meta tags and structured data for tracks, artists, and pages
 */

class MusicSEO {
    
    /**
     * Generate SEO data for a music track
     */
    public static function generateTrackSEO($track) {
        $seo = [
            'title' => self::cleanText($track['title'] . ' by ' . $track['artist']),
            'description' => self::generateTrackDescription($track),
            'keywords' => self::generateTrackKeywords($track),
            'image' => self::getTrackImage($track),
            'type' => 'music.song',
            'url' => SITE_URL . '/track/' . $track['id']
        ];
        
        return $seo;
    }
    
    /**
     * Generate SEO data for an artist page
     */
    public static function generateArtistSEO($artist, $trackCount = 0) {
        $seo = [
            'title' => 'Songs by ' . self::cleanText($artist) . ' - Stream & Download',
            'description' => "Discover all songs by {$artist}. Stream and download high-quality music tracks" . ($trackCount > 0 ? " ({$trackCount} songs available)" : "") . " on Media Crack Zone.",
            'keywords' => self::cleanText($artist) . " songs, " . self::cleanText($artist) . " music, " . self::cleanText($artist) . " albums, download " . self::cleanText($artist) . " songs, stream " . self::cleanText($artist),
            'image' => SITE_URL . '/assets/artist-default.jpg',
            'type' => 'profile',
            'url' => SITE_URL . '/artist/' . urlencode($artist)
        ];
        
        return $seo;
    }
    
    /**
     * Generate SEO data for browse/category pages
     */
    public static function generateBrowseSEO($filters = []) {
        $title = "Browse Music";
        $description = "Discover and stream unlimited music tracks";
        $keywords = "browse music, discover songs, music catalog, streaming music";
        
        if (!empty($filters['language'])) {
            $title = $filters['language'] . " Music - Stream & Download";
            $description = "Stream the best {$filters['language']} music. High-quality songs and albums available now.";
            $keywords = $filters['language'] . " music, " . $filters['language'] . " songs, download " . $filters['language'] . " music, stream " . $filters['language'] . " songs";
        }
        
        if (!empty($filters['mood'])) {
            $moodTitle = ucfirst($filters['mood']) . " Music";
            $title = empty($filters['language']) ? $moodTitle : $filters['language'] . " " . $moodTitle;
            $description = "Perfect {$filters['mood']} music for every moment. Stream and discover the best {$filters['mood']} tracks.";
            $keywords .= ", " . $filters['mood'] . " music, " . $filters['mood'] . " songs, " . $filters['mood'] . " playlist";
        }
        
        if (!empty($filters['search'])) {
            $title = "Search Results for '" . self::cleanText($filters['search']) . "'";
            $description = "Search results for '{$filters['search']}'. Find songs, artists, and albums instantly.";
            $keywords = self::cleanText($filters['search']) . ", search music, find songs, music search results";
        }
        
        return [
            'title' => $title,
            'description' => self::cleanText($description, 155),
            'keywords' => $keywords,
            'image' => SITE_URL . '/assets/browse-music.jpg',
            'type' => 'website'
        ];
    }
    
    /**
     * Generate track description
     */
    private static function generateTrackDescription($track) {
        // Start with core message (keep it concise)
        $description = "Listen to '{$track['title']}' by {$track['artist']} on Media Crack Zone.";
        
        // Add album info if available (but keep it short)
        if (!empty($track['album'])) {
            $description .= " From '{$track['album']}'.";
        }
        
        // Add streaming call-to-action
        $description .= " Stream, download and discover more music.";
        
        // Ensure description is between 25-155 characters for optimal SEO
        return self::cleanText($description, 155);
    }
    
    /**
     * Generate track keywords
     */
    private static function generateTrackKeywords($track) {
        $keywords = [
            self::cleanText($track['title']),
            self::cleanText($track['artist']),
            self::cleanText($track['title']) . " song",
            self::cleanText($track['artist']) . " songs",
            "download " . self::cleanText($track['title']),
            "stream " . self::cleanText($track['title']),
            self::cleanText($track['title']) . " mp3"
        ];
        
        if (!empty($track['album'])) {
            $keywords[] = self::cleanText($track['album']);
            $keywords[] = self::cleanText($track['album']) . " album";
        }
        
        if (!empty($track['language_name'])) {
            $keywords[] = self::cleanText($track['language_name']) . " music";
            $keywords[] = self::cleanText($track['language_name']) . " songs";
        }
        
        if (!empty($track['mood_name'])) {
            $keywords[] = self::cleanText($track['mood_name']) . " music";
            $keywords[] = self::cleanText($track['mood_name']) . " songs";
        }
        
        return implode(', ', array_unique($keywords));
    }
    
    /**
     * Get track image URL
     */
    private static function getTrackImage($track) {
        if (!empty($track['cover_image']) && $track['cover_image'] !== 'default-cover.jpg') {
            return UPLOAD_URL . 'covers/' . $track['cover_image'];
        }
        return SITE_URL . '/assets/default-track.jpg';
    }
    
    /**
     * Clean text for SEO (remove special characters, limit length)
     * Works without mbstring extension
     */
    private static function cleanText($text, $maxLength = 160) {
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Basic character cleaning for servers without mbstring
        $text = trim($text);
        
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove control characters and other problematic characters
        $text = preg_replace('/[\x00-\x1F\x7F]/', '', $text);
        
        // Simple length truncation
        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength - 3) . '...';
            
            // Make sure we don't cut in the middle of a word
            $lastSpace = strrpos($text, ' ');
            if ($lastSpace !== false && $lastSpace > ($maxLength - 20)) {
                $text = substr($text, 0, $lastSpace) . '...';
            }
        }
        
        return $text;
    }
    
    /**
     * Generate JSON-LD structured data for a track
     */
    public static function generateTrackJsonLd($track) {
        $jsonLd = [
            "@context" => "https://schema.org",
            "@type" => "MusicRecording",
            "name" => self::cleanText($track['title']),
            "byArtist" => [
                "@type" => "Person",
                "name" => self::cleanText($track['artist'])
            ],
            "genre" => !empty($track['mood_name']) ? self::cleanText($track['mood_name']) : 'Music',
            "inLanguage" => !empty($track['language_name']) ? self::cleanText($track['language_name']) : 'en',
            "url" => SITE_URL . '/track/' . $track['id'],
            "image" => self::getTrackImage($track),
            "datePublished" => isset($track['created_at']) ? date('Y-m-d', strtotime($track['created_at'])) : date('Y-m-d'),
            "publisher" => [
                "@type" => "Organization",
                "name" => SITE_NAME,
                "url" => SITE_URL
            ],
            "potentialAction" => [
                "@type" => "ListenAction",
                "target" => SITE_URL . '/track/' . $track['id']
            ]
        ];
        
        if (!empty($track['album'])) {
            $jsonLd["inAlbum"] = [
                "@type" => "MusicAlbum",
                "name" => self::cleanText($track['album'])
            ];
        }
        
        if (!empty($track['duration'])) {
            $jsonLd["duration"] = "PT" . $track['duration'] . "S";
        }
        
        return json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Generate breadcrumb JSON-LD
     */
    public static function generateBreadcrumbJsonLd($breadcrumbs) {
        $itemList = [];
        
        foreach ($breadcrumbs as $index => $breadcrumb) {
            $itemList[] = [
                "@type" => "ListItem",
                "position" => $index + 1,
                "name" => $breadcrumb['name'],
                "item" => $breadcrumb['url']
            ];
        }
        
        $jsonLd = [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => $itemList
        ];
        
        return json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Generate sitemap data for tracks
     */
    public static function generateSitemapData($tracks) {
        $sitemapData = [];
        
        foreach ($tracks as $track) {
            $sitemapData[] = [
                'url' => SITE_URL . '/track/' . $track['id'],
                'lastmod' => isset($track['updated_at']) ? date('Y-m-d', strtotime($track['updated_at'])) : date('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'title' => self::cleanText($track['title'] . ' by ' . $track['artist']),
                'description' => self::generateTrackDescription($track)
            ];
        }
        
        return $sitemapData;
    }
    
    /**
     * Auto-generate SEO for all tracks (admin function)
     */
    public static function bulkGenerateSEO($pdo) {
        try {
            // Get all tracks
            $stmt = $pdo->query("
                SELECT t.*, l.name as language_name, m.name as mood_name 
                FROM tracks t 
                LEFT JOIN languages l ON t.language_id = l.id 
                LEFT JOIN moods m ON t.mood_id = m.id
            ");
            $tracks = $stmt->fetchAll();
            
            $updatedCount = 0;
            
            foreach ($tracks as $track) {
                $seo = self::generateTrackSEO($track);
                
                // Update track with SEO data
                $updateStmt = $pdo->prepare("
                    UPDATE tracks SET 
                        seo_title = ?,
                        seo_description = ?,
                        seo_keywords = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $updateStmt->execute([
                    $seo['title'],
                    $seo['description'], 
                    $seo['keywords'],
                    $track['id']
                ]);
                
                $updatedCount++;
            }
            
            return [
                'success' => true,
                'updated' => $updatedCount,
                'message' => "Successfully updated SEO for {$updatedCount} tracks"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

/**
 * Helper function to set page SEO variables
 */
function setSEO($title = null, $description = null, $keywords = null, $image = null, $type = 'website') {
    global $pageTitle, $pageDescription, $pageKeywords, $pageImage, $pageType;
    
    if ($title) $pageTitle = $title;
    if ($description) $pageDescription = $description;
    if ($keywords) $pageKeywords = $keywords;
    if ($image) $pageImage = $image;
    if ($type) $pageType = $type;
}

?>
