<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Google Site Verification -->
    <meta name="google-site-verification" content="4vkxtPRloz30mzHMSmxs5LvG8kt7aDTdEsP3VI1RNjI" />
    
    <!-- Enhanced SEO Meta Tags -->
    <meta name="description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Stream and download music for free. High-quality audio with unlimited discovery and personalized playlists on Media Crack Zone.'; ?>">
    <meta name="keywords" content="<?php echo isset($pageKeywords) ? htmlspecialchars($pageKeywords) : 'music streaming, download music, online music, songs, albums, artists, playlists, music platform, audio streaming, music discovery, free music'; ?>">
    <meta name="author" content="Media Crack Zone">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="language" content="en">
    <meta name="revisit-after" content="3 days">
    <meta name="rating" content="general">
    <meta name="distribution" content="global">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
    
    <!-- Music-specific Schema.org structured data -->
    <?php if (isset($trackData) && is_array($trackData)): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "MusicRecording",
        "name": "<?php echo htmlspecialchars($trackData['title']); ?>",
        "byArtist": {
            "@type": "Person",
            "name": "<?php echo htmlspecialchars($trackData['artist']); ?>"
        },
        <?php if (!empty($trackData['album'])): ?>
        "inAlbum": {
            "@type": "MusicAlbum",
            "name": "<?php echo htmlspecialchars($trackData['album']); ?>"
        },
        <?php endif; ?>
        "duration": "<?php echo isset($trackData['duration']) ? 'PT' . $trackData['duration'] . 'S' : ''; ?>",
        "genre": "<?php echo isset($trackData['mood_name']) ? htmlspecialchars($trackData['mood_name']) : 'Music'; ?>",
        "inLanguage": "<?php echo isset($trackData['language_name']) ? htmlspecialchars($trackData['language_name']) : 'en'; ?>",
        "url": "<?php echo SITE_URL . '/track/' . $trackData['id']; ?>",
        "image": "<?php echo UPLOAD_URL . 'covers/' . ($trackData['cover_image'] ?: 'default-cover.jpg'); ?>",
        "datePublished": "<?php echo isset($trackData['created_at']) ? date('Y-m-d', strtotime($trackData['created_at'])) : date('Y-m-d'); ?>",
        "publisher": {
            "@type": "Organization",
            "name": "<?php echo SITE_NAME; ?>",
            "url": "<?php echo SITE_URL; ?>"
        },
        "potentialAction": {
            "@type": "ListenAction",
            "target": "<?php echo SITE_URL . '/track/' . $trackData['id']; ?>"
        }
    }
    </script>
    <?php endif; ?>
    
    <!-- Website Schema.org structured data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "<?php echo SITE_NAME; ?>",
        "alternateName": "MCZ Music",
        "url": "<?php echo SITE_URL; ?>",
        "description": "Premium music streaming platform with millions of songs, personalized playlists, and high-quality audio streaming.",
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "<?php echo SITE_URL; ?>/browse?search={search_term_string}"
            },
            "query-input": "required name=search_term_string"
        },
        "publisher": {
            "@type": "Organization",
            "name": "<?php echo SITE_NAME; ?>",
            "url": "<?php echo SITE_URL; ?>",
            "logo": {
                "@type": "ImageObject",
                "url": "<?php echo SITE_URL; ?>/assets/logo.png",
                "width": 512,
                "height": 512
            },
            "sameAs": [
                "https://www.facebook.com/mediacrackzone",
                "https://www.twitter.com/mediacrackzone",
                "https://www.instagram.com/mediacrackzone"
            ]
        }
    }
    </script>
    
    <!-- Favicon and App Icons -->
    <link rel="icon" type="image/svg+xml" href="<?php echo SITE_URL; ?>/assets/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo SITE_URL; ?>/assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo SITE_URL; ?>/assets/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo SITE_URL; ?>/assets/apple-touch-icon.png">
    <link rel="manifest" href="<?php echo SITE_URL; ?>/assets/site.webmanifest">
    <meta name="theme-color" content="#1db954">
    
    <!-- SEO and Social Media Meta Tags -->
    <meta name="keywords" content="music, streaming, songs, audio, playlist, media crack zone, music player">
    <meta name="author" content="Media Crack Zone">
    <meta name="robots" content="index, follow">
    
    <!-- Enhanced Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle . ' - ' . SITE_NAME) : htmlspecialchars(SITE_NAME . ' - Premium Music Streaming Platform'); ?>">
    <meta property="og:description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Discover and stream millions of songs from your favorite artists. High-quality music streaming with personalized playlists and recommendations.'; ?>">
    <meta property="og:image" content="<?php echo isset($pageImage) ? $pageImage : SITE_URL . '/assets/og-image.jpg'; ?>">
    <meta property="og:image:alt" content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Media Crack Zone Music Platform'; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:type" content="<?php echo isset($pageType) ? $pageType : 'website'; ?>">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
    <meta property="og:locale" content="en_US">
    <meta property="fb:app_id" content="YOUR_FACEBOOK_APP_ID">
    
    <!-- Enhanced Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@MediaCrackZone">
    <meta name="twitter:creator" content="@MediaCrackZone">
    <meta name="twitter:title" content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle . ' - ' . SITE_NAME) : htmlspecialchars(SITE_NAME); ?>">
    <meta name="twitter:description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Premium music streaming platform with high-quality audio and personalized recommendations.'; ?>">
    <meta name="twitter:image" content="<?php echo isset($pageImage) ? $pageImage : SITE_URL . '/assets/twitter-image.jpg'; ?>">
    <meta name="twitter:image:alt" content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Media Crack Zone Music Platform'; ?>">
    
    <!-- Additional SEO Meta Tags -->
    <meta name="application-name" content="<?php echo SITE_NAME; ?>">
    <meta name="msapplication-TileColor" content="#1db954">
    <meta name="msapplication-config" content="<?php echo SITE_URL; ?>/assets/browserconfig.xml">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?php echo SITE_NAME; ?>">
    
    <!-- CSS Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/e716e4ec39.js" crossorigin="anonymous"></script>
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #1db954;
            --secondary-color: #191414;
            --accent-color: #1ed760;
            --text-light: #b3b3b3;
            --text-white: #ffffff;
            --background-dark: #121212;
            --background-card: #181818;
            --background-hover: #282828;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--background-dark) 0%, #1a1a1a 100%);
            color: var(--text-white);
            min-height: 100vh;
        }

        /* Navigation */
        .navbar {
            background: rgba(25, 20, 20, 0.95) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
            text-decoration: none;
        }

        .navbar-nav .nav-link {
            color: var(--text-light) !important;
            transition: color 0.3s ease;
            margin: 0 0.5rem;
        }

        .navbar-nav .nav-link:hover {
            color: var(--text-white) !important;
        }

        .navbar-nav .nav-link.active {
            color: var(--primary-color) !important;
        }

        /* Dropdown Menu Styles */
        .dropdown-menu {
            background: var(--background-card) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4) !important;
            border-radius: 8px !important;
            padding: 0.5rem 0 !important;
            margin-top: 0.5rem !important;
            min-width: 200px !important;
            z-index: 1050 !important;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
        }

        .dropdown-menu.show {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) !important;
        }

        .dropdown-item {
            color: var(--text-white) !important;
            padding: 0.75rem 1.5rem !important;
            font-size: 0.9rem !important;
            transition: all 0.2s ease !important;
            border: none !important;
            background: transparent !important;
            text-decoration: none !important;
            display: block !important;
            width: 100% !important;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            background: var(--background-hover) !important;
            color: var(--primary-color) !important;
            text-decoration: none !important;
        }

        .dropdown-item i {
            width: 20px !important;
            text-align: center !important;
        }

        .dropdown-divider {
            border-color: rgba(255, 255, 255, 0.1) !important;
            margin: 0.5rem 0 !important;
        }

        .dropdown-toggle::after {
            margin-left: 0.5rem !important;
        }

        /* Ensure dropdown is clickable */
        .nav-item.dropdown {
            position: relative !important;
        }

        .dropdown-toggle {
            cursor: pointer !important;
            user-select: none !important;
        }

        .dropdown-toggle:hover {
            color: var(--primary-color) !important;
        }

        /* Search Bar */
        .search-container {
            position: relative;
            max-width: 400px;
        }

        .search-input {
            background: var(--background-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 0.75rem 1rem 0.75rem 3rem;
            color: var(--text-white);
            width: 100%;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(29, 185, 84, 0.2);
            background: var(--background-hover);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        /* Cards */
        .card-custom {
            background: var(--background-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card-custom:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            border-color: var(--primary-color);
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(29, 185, 84, 0.4);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: var(--text-white);
            transform: translateY(-2px);
        }

        /* Music Player */
        .music-player {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--background-card);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
            z-index: 1000;
            display: none;
            transition: all 0.3s ease;
        }

        .music-player.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .player-close-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 32px;
            height: 32px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 0.9rem;
            z-index: 1001;
        }

        .player-close-btn:hover {
            background: rgba(255, 59, 48, 0.2);
            color: #ff3b30;
            transform: scale(1.1);
        }

        .player-close-btn:active {
            transform: scale(0.95);
        }

        .player-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .play-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-color);
            border: none;
            color: var(--text-white);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .play-btn:hover {
            transform: scale(1.05);
            background: var(--accent-color);
        }

        .progress-container {
            flex: 1;
            margin: 0 2rem;
        }

        .progress-bar {
            background: rgba(255, 255, 255, 0.2);
            height: 4px;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-fill {
            background: var(--primary-color);
            height: 100%;
            width: 0%;
            transition: width 0.1s ease;
        }

        /* Track Items */
        .track-item {
            background: var(--background-card);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .track-item:hover {
            background: var(--background-hover);
            transform: translateX(5px);
        }

        .track-cover {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 1rem;
        }

        .track-info {
            flex: 1;
        }

        .track-title {
            font-weight: 600;
            color: var(--text-white);
            margin-bottom: 0.25rem;
        }

        .track-artist {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .track-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            background: none;
            border: none;
            color: var(--text-light);
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            color: var(--primary-color);
            background: rgba(29, 185, 84, 0.1);
        }

        /* Favorite and Download Button Styling */
        #favoriteBtn {
            transition: all 0.3s ease;
        }

        #favoriteBtn:hover {
            color: #ff4757 !important;
            background: rgba(255, 71, 87, 0.1);
            transform: scale(1.1);
        }

        #favoriteBtn.active {
            color: #ff4757 !important;
        }

        #downloadBtn {
            transition: all 0.3s ease;
        }

        #downloadBtn:hover {
            color: #3742fa !important;
            background: rgba(55, 66, 250, 0.1);
            transform: scale(1.1);
        }

        /* Heart animation */
        @keyframes heartBeat {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        #favoriteIcon.fas {
            animation: heartBeat 0.6s ease-in-out;
        }

        /* Toast Notifications */
        .toast-notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background: var(--background-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1rem 1.5rem;
            z-index: 9999;
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        .toast-notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-white);
            font-weight: 500;
        }

        .toast-success {
            border-left: 4px solid #27ae60;
        }

        .toast-error {
            border-left: 4px solid #e74c3c;
        }

        .toast-warning {
            border-left: 4px solid #f39c12;
        }

        .toast-info {
            border-left: 4px solid var(--primary-color);
        }

        .toast-success .fa-check-circle {
            color: #27ae60;
        }

        .toast-error .fa-exclamation-circle {
            color: #e74c3c;
        }

        .toast-warning .fa-exclamation-triangle {
            color: #f39c12;
        }

        .toast-info .fa-info-circle {
            color: var(--primary-color);
        }

        /* Share Modal Styles */
        .share-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 10000;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .share-modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .share-modal {
            background: linear-gradient(135deg, var(--background-card) 0%, rgba(255, 255, 255, 0.05) 100%);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 480px;
            transform: scale(0.9) translateY(20px);
            transition: all 0.3s ease;
        }

        .share-modal-overlay.show .share-modal {
            transform: scale(1) translateY(0);
        }

        .share-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .share-modal-header h5 {
            color: var(--text-white);
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .close-share-modal {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-share-modal:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-white);
        }

        .share-modal-body {
            padding: 1.5rem 2rem 2rem;
        }

        .share-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .share-option {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.03) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1rem;
            color: var(--text-white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            cursor: pointer;
            backdrop-filter: blur(10px);
        }

        .share-option:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0.08) 100%);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            color: var(--text-white);
        }

        .share-option i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .share-option:nth-child(1) i { color: #4267B2; } /* Facebook */
        .share-option:nth-child(2) i { color: #1DA1F2; } /* Twitter */
        .share-option:nth-child(3) i { color: #25D366; } /* WhatsApp */
        .share-option:nth-child(4) i { color: #0088cc; } /* Telegram */

        .share-link-section {
            margin-top: 1.5rem;
        }

        .share-link-section label {
            display: block;
            color: var(--text-light);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .share-link-input {
            display: flex;
            gap: 0.5rem;
        }

        .share-link-input input {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            color: var(--text-white);
            font-size: 0.9rem;
        }

        .share-link-input input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.08);
        }

        .copy-link-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border: none;
            border-radius: 8px;
            color: white;
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            min-width: 80px;
        }

        .copy-link-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(29, 185, 84, 0.4);
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .share-modal {
                width: 95%;
                margin: 1rem;
            }
            
            .share-modal-header,
            .share-modal-body {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }
            
            .share-options {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
        }

        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .section-title-wrapper {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .section-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            color: var(--text-white);
        }

        .section-subtitle {
            color: var(--text-light);
            margin: 0;
            font-size: 0.95rem;
        }

        .btn-outline-primary-enhanced {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-outline-primary-enhanced:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(29, 185, 84, 0.3);
        }

        /* Enhanced Track Cards */
        .tracks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .track-card-enhanced {
            background: var(--background-card);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .track-card-enhanced:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            border-color: var(--primary-color);
        }

        .track-cover-wrapper {
            position: relative;
            overflow: hidden;
        }

        .track-cover-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .track-card-enhanced:hover .track-cover-image {
            transform: scale(1.05);
        }

        .track-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .track-card-enhanced:hover .track-overlay {
            opacity: 1;
        }

        .play-btn-enhanced {
            width: 60px;
            height: 60px;
            background: var(--primary-color);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(29, 185, 84, 0.4);
        }

        .play-btn-enhanced:hover {
            transform: scale(1.1);
            background: var(--accent-color);
        }

        .track-actions-quick {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 0.5rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .track-card-enhanced:hover .track-actions-quick {
            opacity: 1;
        }

        .action-btn-quick {
            width: 35px;
            height: 35px;
            background: rgba(0, 0, 0, 0.7);
            border: none;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .action-btn-quick:hover {
            background: var(--primary-color);
            transform: scale(1.1);
        }

        .popularity-indicator {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .track-info-enhanced {
            padding: 1.5rem;
        }

        .track-title-enhanced {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-white);
        }

        .track-artist-enhanced {
            color: var(--text-light);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .track-tags {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .track-tag {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .mood-tag {
            background: rgba(29, 185, 84, 0.2);
            color: var(--primary-color);
            border: 1px solid rgba(29, 185, 84, 0.3);
        }

        .lang-tag {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Mood Grid */
        .mood-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .mood-card-link {
            text-decoration: none;
            color: inherit;
        }

        .mood-card {
            position: relative;
            background: var(--mood-color);
            border-radius: 15px;
            padding: 2rem;
            min-height: 120px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .mood-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .mood-background {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                var(--mood-color) 0%, 
                rgba(0, 0, 0, 0.3) 100%);
        }

        .mood-content {
            position: relative;
            z-index: 2;
        }

        .mood-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .mood-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: white;
        }

        .mood-stats .track-count {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .mood-wave {
            position: absolute;
            bottom: -20px;
            right: -20px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        /* Language Grid */
        .language-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .language-card-link {
            text-decoration: none;
            color: inherit;
        }

        .language-card {
            background: var(--background-card);
            border-radius: 15px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .language-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border-color: var(--primary-color);
        }

        .language-flag {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .language-content {
            flex: 1;
        }

        .language-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-white);
        }

        .language-stats .track-count {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .language-arrow {
            color: var(--text-light);
            transition: all 0.3s ease;
        }

        .language-card:hover .language-arrow {
            color: var(--primary-color);
            transform: translateX(5px);
        }

        /* Recent Tracks List */
        .recent-tracks-list {
            background: var(--background-card);
            border-radius: 15px;
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .recent-track-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .recent-track-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .track-number {
            width: 30px;
            text-align: center;
            color: var(--text-light);
            font-weight: 600;
        }

        .track-cover-mini {
            position: relative;
            width: 50px;
            height: 50px;
        }

        .track-cover-mini img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .mini-play-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .recent-track-item:hover .mini-play-overlay {
            opacity: 1;
        }

        .mini-play-btn {
            width: 30px;
            height: 30px;
            background: var(--primary-color);
            border: none;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .track-details {
            flex: 1;
        }

        .track-primary-info {
            margin-bottom: 0.25rem;
        }

        .track-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-white);
        }

        .artist-name {
            color: var(--text-light);
            font-size: 0.9rem;
            margin: 0;
        }

        .track-meta {
            display: flex;
            gap: 0.5rem;
        }

        .meta-tag {
            padding: 0.125rem 0.5rem;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .meta-tag.lang {
            background: rgba(29, 185, 84, 0.2);
            color: var(--primary-color);
        }

        .meta-tag.mood {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
        }

        .track-duration {
            color: var(--text-light);
            font-size: 0.9rem;
            min-width: 50px;
            text-align: center;
        }

        .track-actions-list {
            display: flex;
            gap: 0.5rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .recent-track-item:hover .track-actions-list {
            opacity: 1;
        }

        .action-btn-list {
            width: 32px;
            height: 32px;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }

        .action-btn-list:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* Enhanced Stats Section */
        .stats-section-enhanced {
            background: linear-gradient(135deg, 
                rgba(29, 185, 84, 0.05) 0%, 
                rgba(24, 24, 24, 0.95) 50%,
                rgba(29, 185, 84, 0.05) 100%);
            border-radius: 20px;
            padding: 4rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .stats-section-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%23ffffff" fill-opacity="0.05"><circle cx="30" cy="30" r="4"/></g></svg>') repeat;
            opacity: 0.5;
        }

        .stats-header {
            position: relative;
            z-index: 2;
        }

        .stats-main-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stats-subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            position: relative;
            z-index: 2;
        }

        .stat-card {
            position: relative;
            background: rgba(24, 24, 24, 0.8);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary-color);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .stat-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
            position: relative;
            z-index: 2;
        }

        .stat-icon.tracks {
            background: linear-gradient(135deg, #FF6B6B, #FF8E53);
        }

        .stat-icon.users {
            background: linear-gradient(135deg, #4ECDC4, #44A08D);
        }

        .stat-icon.languages {
            background: linear-gradient(135deg, #45B7D1, #96C93D);
        }

        .stat-icon.moods {
            background: linear-gradient(135deg, #F093FB, #F5576C);
        }

        .stat-content {
            position: relative;
            z-index: 2;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--text-white);
            margin-bottom: 0.5rem;
            counter-reset: num;
        }

        .stat-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-description {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .stat-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .stat-card:hover .stat-glow {
            opacity: 0.1;
        }

        .tracks-glow {
            background: radial-gradient(circle, #FF6B6B, transparent);
        }

        .users-glow {
            background: radial-gradient(circle, #4ECDC4, transparent);
        }

        .languages-glow {
            background: radial-gradient(circle, #45B7D1, transparent);
        }

        .moods-glow {
            background: radial-gradient(circle, #F093FB, transparent);
        }

        .stats-footer {
            position: relative;
            z-index: 2;
        }

        .achievement-badges {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .badge-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem 1.5rem;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .badge-item:hover {
            background: rgba(29, 185, 84, 0.1);
            border-color: var(--primary-color);
        }

        .badge-item i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .badge-item span {
            color: var(--text-white);
            font-weight: 500;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* Counter animation for stats */
        @keyframes countUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Music Player Notifications */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .music-notification {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        /* Enhanced Player Controls */
        .music-player .action-btn:hover {
            transform: scale(1.1);
            color: var(--primary-color);
        }
        
        .music-player .play-btn:hover {
            transform: scale(1.05);
            background: var(--accent-color);
        }
        
        /* Progress bar enhancements */
        .progress-container:hover .progress-bar {
            transform: scaleY(1.2);
        }
        
        .progress-bar {
            transition: transform 0.2s ease;
            cursor: pointer;
        }
        
        /* Volume slider styling */
        .form-range::-webkit-slider-thumb {
            background: var(--primary-color);
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(29, 185, 84, 0.4);
        }
        
        .form-range::-webkit-slider-track {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
        }
        
        /* Continuous play indicator */
        .continuous-play-indicator {
            position: fixed;
            bottom: 120px;
            right: 20px;
            background: rgba(29, 185, 84, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            z-index: 1000;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 0.8; }
            50% { opacity: 1; }
        }

        /* Player Status Indicator */
        .player-status {
            animation: fadeInSync 0.5s ease-in-out;
        }

        .player-status i {
            animation: syncRotate 2s linear infinite;
        }

        @keyframes fadeInSync {
            0% {
                opacity: 0;
                transform: translateY(5px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes syncRotate {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .tracks-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .mood-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .language-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.5rem;
            }
            
            .achievement-badges {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
            
            .recent-track-item {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .track-actions-list {
                opacity: 1;
            }
        }

        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Enhanced Hero Section */
        .hero-section-enhanced {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
            background: linear-gradient(135deg, 
                #0a0a0a 0%,
                #1a1a1a 25%,
                #0f1419 50%,
                #1a1a1a 75%,
                #0a0a0a 100%
            );
        }

        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .hero-gradient {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(ellipse at center, 
                rgba(29, 185, 84, 0.15) 0%,
                rgba(29, 185, 84, 0.08) 25%,
                transparent 50%
            );
            animation: gradientPulse 8s ease-in-out infinite;
        }

        @keyframes gradientPulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }

        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .floating-note {
            position: absolute;
            color: rgba(29, 185, 84, 0.3);
            font-size: 1.5rem;
            animation: float 6s ease-in-out infinite;
            pointer-events: none;
        }

        .note-1 {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
            animation-duration: 8s;
        }

        .note-2 {
            top: 60%;
            left: 85%;
            animation-delay: 2s;
            animation-duration: 10s;
        }

        .note-3 {
            top: 80%;
            left: 15%;
            animation-delay: 4s;
            animation-duration: 7s;
        }

        .note-4 {
            top: 15%;
            left: 80%;
            animation-delay: 1s;
            animation-duration: 9s;
        }

        .note-5 {
            top: 45%;
            left: 5%;
            animation-delay: 3s;
            animation-duration: 6s;
        }

        .note-6 {
            top: 25%;
            left: 75%;
            animation-delay: 0.5s;
            animation-duration: 8.5s;
        }

        .note-7 {
            top: 70%;
            left: 25%;
            animation-delay: 1.5s;
            animation-duration: 7.5s;
        }

        .note-8 {
            top: 55%;
            left: 60%;
            animation-delay: 2.5s;
            animation-duration: 9s;
        }

        .hero-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 50%;
            animation: particleFloat 8s linear infinite;
            opacity: 0.6;
        }

        .particle:nth-child(1) {
            left: 20%;
            animation-delay: 0s;
            animation-duration: 8s;
        }

        .particle:nth-child(2) {
            left: 40%;
            animation-delay: 2s;
            animation-duration: 10s;
        }

        .particle:nth-child(3) {
            left: 60%;
            animation-delay: 4s;
            animation-duration: 12s;
        }

        .particle:nth-child(4) {
            left: 80%;
            animation-delay: 6s;
            animation-duration: 9s;
        }

        .particle:nth-child(5) {
            left: 10%;
            animation-delay: 1s;
            animation-duration: 11s;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 0.6;
            }
            90% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100px) translateX(50px);
                opacity: 0;
            }
        }

        .hero-features {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-bottom: 2rem;
            animation: slideInFromLeft 1s ease-out 0.5s both;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .feature-item i {
            color: var(--primary-color);
            font-size: 1rem;
        }

        .hero-social {
            animation: slideInFromLeft 1s ease-out 1s both;
        }

        .social-text {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .social-link:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(29, 185, 84, 0.4);
        }

        .volume-control {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .volume-control i {
            color: var(--text-light);
            font-size: 1rem;
        }

        .volume-bar {
            flex: 1;
            background: rgba(255, 255, 255, 0.1);
            height: 3px;
            border-radius: 2px;
            position: relative;
        }

        .volume-fill {
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            height: 100%;
            width: 70%;
            border-radius: 2px;
        }

        .player-equalizer {
            display: flex;
            gap: 3px;
            justify-content: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .eq-bar {
            width: 4px;
            background: linear-gradient(to top, var(--primary-color), var(--accent-color));
            border-radius: 2px;
            animation: equalizer 1.5s ease-in-out infinite;
        }

        .eq-bar:nth-child(1) {
            height: 20px;
            animation-delay: 0s;
        }

        .eq-bar:nth-child(2) {
            height: 30px;
            animation-delay: 0.1s;
        }

        .eq-bar:nth-child(3) {
            height: 25px;
            animation-delay: 0.2s;
        }

        .eq-bar:nth-child(4) {
            height: 35px;
            animation-delay: 0.3s;
        }

        .eq-bar:nth-child(5) {
            height: 15px;
            animation-delay: 0.4s;
        }

        @keyframes equalizer {
            0%, 100% {
                transform: scaleY(1);
            }
            50% {
                transform: scaleY(0.3);
            }
        }

        .card-4 {
            top: 10%;
            left: -30%;
            animation-delay: 6s;
            transform: rotate(-15deg);
        }

        .hero-audio-waves {
            position: absolute;
            bottom: -50px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 3px;
            opacity: 0.3;
        }

        .wave {
            width: 3px;
            background: linear-gradient(to top, var(--primary-color), var(--accent-color));
            border-radius: 2px;
            animation: audioWave 2s ease-in-out infinite;
        }

        .wave-1 {
            height: 30px;
            animation-delay: 0s;
        }

        .wave-2 {
            height: 50px;
            animation-delay: 0.2s;
        }

        .wave-3 {
            height: 40px;
            animation-delay: 0.4s;
        }

        .wave-4 {
            height: 60px;
            animation-delay: 0.6s;
        }

        .wave-5 {
            height: 35px;
            animation-delay: 0.8s;
        }

        @keyframes audioWave {
            0%, 100% {
                transform: scaleY(1);
                opacity: 0.6;
            }
            50% {
                transform: scaleY(0.3);
                opacity: 1;
            }
        }

        /* Counter Animation for Stats */
        .stat-number[data-target] {
            counter-reset: num;
        }

        /* Responsive enhancements */
        @media (max-width: 768px) {
            .hero-features {
                flex-direction: column;
                gap: 1rem;
                align-items: center;
            }

            .social-links {
                justify-content: center;
            }

            .floating-cards .music-card {
                display: none;
            }

            .floating-cards .card-1,
            .floating-cards .card-2 {
                display: flex;
            }

            .hero-audio-waves {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .hero-features {
                gap: 0.75rem;
            }

            .feature-item {
                font-size: 0.8rem;
            }

            .social-link {
                width: 35px;
                height: 35px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-music me-2"></i>Media Crack Zone
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/browse.php">
                            <i class="fas fa-compass me-1"></i>Browse
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/enhanced_player.php">
                            <i class="fas fa-play-circle me-1"></i>Player
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/favorites.php">
                            <i class="fas fa-heart me-1"></i>Favorites
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/playlists.php">
                            <i class="fa-solid fa-music"></i>Playlists
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/">
                            <i class="fas fa-cog me-1"></i>Admin
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <!-- Search Bar -->
                <div class="search-container me-3">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search music, artists..." id="searchInput">
                </div>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i><?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/register.php">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    

    

    
    <!-- JavaScript Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Debug dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Header JavaScript initialized');
            
            // Debug dropdown
            const dropdownToggle = document.querySelector('.dropdown-toggle');
            if (dropdownToggle) {
                console.log('Dropdown toggle found');
                
                // Remove any existing event listeners
                dropdownToggle.removeAttribute('data-bs-toggle');
                
                // Add manual click handler
                dropdownToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Dropdown clicked');
                    
                    const dropdownMenu = this.nextElementSibling;
                    if (dropdownMenu) {
                        const isShown = dropdownMenu.classList.contains('show');
                        
                        // Hide any other open dropdowns
                        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                            menu.classList.remove('show');
                        });
                        
                        // Toggle current dropdown
                        if (!isShown) {
                            dropdownMenu.classList.add('show');
                            this.setAttribute('aria-expanded', 'true');
                        } else {
                            dropdownMenu.classList.remove('show');
                            this.setAttribute('aria-expanded', 'false');
                        }
                    }
                });
                
                // Initialize Bootstrap dropdown as fallback
                try {
                    // Re-add Bootstrap attribute
                    dropdownToggle.setAttribute('data-bs-toggle', 'dropdown');
                    const dropdown = new bootstrap.Dropdown(dropdownToggle);
                    console.log('Bootstrap dropdown initialized');
                } catch (error) {
                    console.error('Bootstrap dropdown failed:', error);
                }
            } else {
                console.log('No dropdown toggle found - user might not be logged in');
            }
            
            // Test navigation links
            const navLinks = document.querySelectorAll('.dropdown-item');
            navLinks.forEach((link, index) => {
                console.log(`Nav link ${index}:`, link.href);
                link.addEventListener('click', function(e) {
                    console.log('Navigation link clicked:', this.href);
                    // Allow default navigation
                });
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                    });
                    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                        toggle.setAttribute('aria-expanded', 'false');
                    });
                }
            });
        });
        
        // Your custom JavaScript code can go here
    </script>
</body>
</html>
