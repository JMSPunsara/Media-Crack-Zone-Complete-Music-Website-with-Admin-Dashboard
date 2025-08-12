# 🚀 INSTALL GUIDE - Media Crack Zone

## Simple Installation Guide

### 📋 Requirements

1. **XAMPP** - Apache, MySQL, PHP all-in-one
2. **Web Browser** - Chrome, Firefox, Edge
3. **Text Editor** (optional) - VS Code, Notepad++

### ⚡ 5 Minutes Setup

#### Step 1: Install XAMPP
```
1. Download XAMPP from https://www.apachefriends.org
2. Install it (recommended: C:\xampp)
3. Open XAMPP Control Panel
```

#### Step 2: Start Services
```
From XAMPP Control Panel:
✅ Apache - Click Start button
✅ MySQL - Click Start button

OR

From PowerShell:
Start-Service -Name "Apache2.4"
Start-Service -Name "mysql"
```

#### Step 3: Copy Files
```
Copy your music website folder to:
C:\xampp\htdocs\music\

Copy all files to this folder
```

#### Step 4: Database Setup
```
1. Go to http://localhost/phpmyadmin in your browser
2. Click "New" button
3. Database name: mcz_music
4. Click "Create" button
5. Then go to "Import" tab
6. Select the database.sql file
7. Click "Go" button
```

#### Step 5: Test Website
```
In your browser:
🌐 http://localhost/music

Admin Panel:
🔧 http://localhost/music/admin
Username: admin
Password: admin
```

### ✅ Success! 

Your music website is now ready! 🎵

### 🔧 Troubleshooting

**Apache Won't Start:**
```
Is port 80 or 443 being used by another program?
Disable Skype or IIS
```

**Database Connection Error:**
```
Check database details in config.php file:
- DB_HOST: localhost
- DB_NAME: mcz_music  
- DB_USER: mcz_music
- DB_PASS: 7bfF86THrZ4zRxHL
```

**Files Won't Upload:**
```
Check permissions on uploads/ folder
Increase upload limits in PHP.ini
```

### 📱 Features List

✅ Music Player - Advanced audio player  
✅ Upload Songs - Through admin panel  
✅ User Registration - Free registration  
✅ Playlists - Create personal playlists  
✅ Favorites - Mark favorite songs  
✅ Categories - Organize songs  
✅ Search - Search for songs  
✅ Mobile Support - Phone/tablet compatible  
✅ SEO Optimized - Show up in Google  

### 🎯 Quick Start

1. **Upload Songs** - Go to admin panel → Upload
2. **Create Categories** - Organize your music
3. **Test Player** - Play some music
4. **Create User Account** - Test registration
5. **Create Playlists** - Make your first playlist

### 🛠️ Admin Tasks

**First Time Setup:**
1. Change admin password
2. Upload some test songs
3. Create music categories
4. Test all features

**Regular Maintenance:**
1. Backup database regularly
2. Check error logs
3. Update songs metadata
4. Monitor user activity

### 🌐 Going Live

**For Production Deployment:**
1. Update config.php with production URLs
2. Set up proper SSL certificate
3. Configure proper database credentials
4. Set up automatic backups
5. Configure proper file permissions

### 🎵 Ready to Rock! 

Your music website is now live! Start uploading songs and enjoy! 🎶

---

**📚 Documentation:**
- `README.md` - Complete documentation in Sinhala
- `README_EN.md` - Complete documentation in English
- `INSTALL.md` - Installation guide in Sinhala
- `INSTALL_EN.md` - Installation guide in English

*Easy setup for everyone! 💪*
