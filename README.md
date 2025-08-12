# 🎵 Media Crack Zone - Music Website

## Overview

**Media Crack Zone** is a complete music streaming and management web application built with PHP, MySQL, and JavaScript.

## ✨ Features

### 🎵 Music Features
- **Audio Player**: Advanced enhanced player with continuous playback
- **Music Upload**: Upload songs through admin panel
- **Playlists**: Create and manage personal playlists
- **Favorites**: Add favorite songs to your favorites list
- **Categories**: Organize songs by categories
- **Search**: Search songs by title, artist, album

### 👤 User Features
- **User Registration & Login**: Secure user authentication
- **User Profiles**: Profile pictures and settings
- **Play History**: Track your listening history
- **Download Counter**: Track song download counts

### 🔧 Admin Features
- **Track Management**: Add, edit, delete songs
- **User Management**: Manage users
- **Category Management**: Manage music categories
- **SEO Management**: Optimize website SEO
- **Sitemap Generation**: Automatic XML sitemaps

### 🌐 SEO & Performance
- **SEO Optimized**: Optimized for search engines
- **Responsive Design**: Mobile, tablet, desktop compatible
- **Fast Loading**: Optimized performance
- **XML Sitemaps**: Auto-generated sitemaps

## 🚀 Installation & Setup

### Requirements
- **XAMPP** (Apache + MySQL + PHP)
- **PHP 7.4+**
- **MySQL 5.7+**
- **Web Browser**

### Installation

1. **Install XAMPP**
   ```
   Download from: https://www.apachefriends.org/download.html
   ```

2. **Copy Files**
   ```
   Copy project files to C:\xampp\htdocs\music\ folder
   ```

3. **Start XAMPP Services**
   ```powershell
   Start-Service -Name "Apache2.4"
   Start-Service -Name "mysql"
   ```

4. **Database Setup**
   - Go to phpMyAdmin: http://localhost/phpmyadmin
   - Create database named `mcz_music`
   - Import the `database.sql` file

5. **Configuration**
   - Check `config.php` file
   - Verify database credentials are correct

## 🌐 Website Access

### Local Development
- **Main URL**: http://localhost/music
- **Admin Panel**: http://localhost/music/admin
- **phpMyAdmin**: http://localhost/phpmyadmin

### Admin Login
- **Username**: admin
- **Password**: admin (change on first login)

## 📁 File Structure

```
music/
├── admin/              # Admin panel files
├── ajax/               # AJAX request handlers  
├── api/                # API endpoints
├── assets/             # Static files (images, icons)
├── includes/           # Common PHP includes
├── logs/               # System logs
├── uploads/            # Uploaded files
│   ├── music/          # Audio files
│   ├── covers/         # Album covers
│   └── avatars/        # User profile pictures
├── config.php          # Database & site configuration
├── index.php           # Homepage
├── browse.php          # Browse music page
├── login.php           # User login
├── register.php        # User registration
├── profile.php         # User profile
├── playlist.php        # Playlist management
├── favorites.php       # Favorites page
└── track.php           # Individual track page
```

## 🔧 Configuration

### Database Configuration (`config.php`)
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mcz_music');
define('DB_USER', 'mcz_music');
define('DB_PASS', '7bfF86THrZ4zRxHL');
```

### Site Configuration
```php
define('SITE_NAME', 'Media Crack Zone');
define('SITE_URL', 'http://localhost/music');
```

## 🎵 Audio Support

### Supported Formats
- **MP3** - Most common format
- **WAV** - High quality
- **FLAC** - Lossless quality
- **AAC** - Good compression
- **OGG** - Open source format

### Upload Limits
- Maximum file size: 50MB per file
- Supported file types: .mp3, .wav, .flac, .aac, .ogg

## 🔐 Security Features

- **SQL Injection Protection**: PDO prepared statements
- **XSS Protection**: Input sanitization
- **CSRF Protection**: Form tokens
- **File Upload Security**: File type validation
- **User Authentication**: Secure password hashing

## 📱 Mobile Compatibility

The website is fully compatible with mobile devices:
- **Responsive Design**
- **Touch Controls**
- **Mobile Player Interface**
- **Optimized Performance**

## 🚀 Performance Optimization

- **Gzip Compression** enabled
- **Browser Caching** configured
- **Optimized Database Queries**
- **Lazy Loading** for images
- **Minified CSS/JS** files

## 🔄 Updates & Maintenance

### Database Updates
Use migration files for database schema changes.

### Backup
Create regular backups:
- Database backup via phpMyAdmin
- Files backup via file system

## 🐛 Troubleshooting

### Common Issues

**1. Apache Service Won't Start:**
```powershell
Stop-Service -Name "Apache2.4" -Force
Start-Service -Name "Apache2.4"
```

**2. MySQL Connection Error:**
- Check if MySQL service is running
- Verify database credentials are correct

**3. File Upload Issues:**
- Check `uploads/` folder permissions
- Check PHP upload limits

**4. Audio Player Not Working:**
- Check browser audio support
- Verify file formats are supported

## 📞 Support

For technical issues:
1. Check browser console for errors
2. Check PHP error logs (`logs/` folder)
3. Check Apache error logs

## 🏷️ Version Info

- **Version**: 2.0
- **Last Updated**: August 2025
- **PHP Version**: 8.0+
- **MySQL Version**: 5.7+

## 📝 License

This project is open source and available under the MIT License.

---

**🎵 Media Crack Zone - Your Complete Music Experience! 🎵**

*Developed with ❤️ for music lovers*
