<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get all tracks for sitemap
    $stmt = $pdo->query("
        SELECT t.*, l.name as language_name, m.name as mood_name 
        FROM tracks t 
        LEFT JOIN languages l ON t.language_id = l.id 
        LEFT JOIN moods m ON t.mood_id = m.id 
        ORDER BY t.created_at DESC
    ");
    $tracks = $stmt->fetchAll();
    
    // Start XML sitemap
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;
    
    $urlset = $xml->createElement('urlset');
    $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $urlset->setAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
    $xml->appendChild($urlset);
    
    // Add main pages
    $mainPages = [
        ['url' => SITE_URL, 'priority' => '1.0', 'changefreq' => 'daily'],
        ['url' => SITE_URL . '/browse', 'priority' => '0.9', 'changefreq' => 'daily'],
        ['url' => SITE_URL . '/login', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['url' => SITE_URL . '/register', 'priority' => '0.5', 'changefreq' => 'monthly'],
    ];
    
    foreach ($mainPages as $page) {
        $url = $xml->createElement('url');
        $url->appendChild($xml->createElement('loc', htmlspecialchars($page['url'])));
        $url->appendChild($xml->createElement('lastmod', date('Y-m-d')));
        $url->appendChild($xml->createElement('changefreq', $page['changefreq']));
        $url->appendChild($xml->createElement('priority', $page['priority']));
        $urlset->appendChild($url);
    }
    
    // Add track pages
    foreach ($tracks as $track) {
        $url = $xml->createElement('url');
        
        // Track URL
        $trackUrl = SITE_URL . '/track/' . $track['id'];
        if (!empty($track['seo_slug'])) {
            $trackUrl = SITE_URL . '/track/' . $track['seo_slug'];
        }
        
        $url->appendChild($xml->createElement('loc', htmlspecialchars($trackUrl)));
        $url->appendChild($xml->createElement('lastmod', date('Y-m-d', strtotime($track['updated_at'] ?? $track['created_at']))));
        $url->appendChild($xml->createElement('changefreq', 'weekly'));
        $url->appendChild($xml->createElement('priority', '0.8'));
        
        // Add image for track cover
        if (!empty($track['cover_image']) && $track['cover_image'] !== 'default-cover.jpg') {
            $image = $xml->createElement('image:image');
            $image->appendChild($xml->createElement('image:loc', htmlspecialchars(UPLOAD_URL . 'covers/' . $track['cover_image'])));
            $image->appendChild($xml->createElement('image:title', htmlspecialchars($track['title'] . ' by ' . $track['artist'])));
            $image->appendChild($xml->createElement('image:caption', htmlspecialchars('Album cover for ' . $track['title'])));
            $url->appendChild($image);
        }
        
        $urlset->appendChild($url);
    }
    
    // Get unique artists for artist pages
    $artistStmt = $pdo->query("SELECT DISTINCT artist FROM tracks ORDER BY artist");
    $artists = $artistStmt->fetchAll();
    
    foreach ($artists as $artist) {
        $url = $xml->createElement('url');
        $artistUrl = SITE_URL . '/artist/' . urlencode($artist['artist']);
        
        $url->appendChild($xml->createElement('loc', htmlspecialchars($artistUrl)));
        $url->appendChild($xml->createElement('lastmod', date('Y-m-d')));
        $url->appendChild($xml->createElement('changefreq', 'weekly'));
        $url->appendChild($xml->createElement('priority', '0.7'));
        
        $urlset->appendChild($url);
    }
    
    // Save sitemap
    $sitemapPath = '../sitemap.xml';
    $xml->save($sitemapPath);
    
    // Also create a robots.txt if it doesn't exist
    $robotsPath = '../robots.txt';
    if (!file_exists($robotsPath)) {
        $robotsContent = "User-agent: *\n";
        $robotsContent .= "Allow: /\n";
        $robotsContent .= "Disallow: /admin/\n";
        $robotsContent .= "Disallow: /api/\n";
        $robotsContent .= "Disallow: /includes/\n";
        $robotsContent .= "Disallow: /uploads/\n\n";
        $robotsContent .= "Sitemap: " . SITE_URL . "/sitemap.xml\n";
        
        file_put_contents($robotsPath, $robotsContent);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Sitemap generated successfully',
        'urls_count' => count($tracks) + count($mainPages) + count($artists),
        'sitemap_url' => SITE_URL . '/sitemap.xml'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
