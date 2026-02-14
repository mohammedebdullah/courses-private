# 🚀 Deployment Checklist

## Issue Fixed: Audio Playback Error After Deployment

### Problem
After deploying to the server, audio files show error: **خەلەتی د لێدانا دەنگی دا** (Error playing audio)

### Root Cause
Audio file paths were stored as absolute Windows paths (e.g., `C:\xampp\htdocs\audio-course\uploads\audio\file.mp3`) which don't work on Linux servers.

### Solution Applied ✅
Modified `AudioStream.php` to store relative paths (`uploads/audio/file.mp3`) instead of absolute paths.

---

## Step-by-Step Deployment Guide

### 1. Pre-Deployment Checklist

- [ ] All code is committed to repository
- [ ] Database credentials in `database/config.php` are correct for production
- [ ] GitHub secret `FTP_PASSWORD` is configured
- [ ] Deployment workflow `.github/workflows/deploy.yml` is configured

### 2. Deploy to Server

```bash
cd c:\xampp\htdocs\audio-course
git add .
git commit -m "Fix audio paths for production deployment"
git push origin main
```

**Automatic deployment will start via GitHub Actions**

Monitor progress at: `https://github.com/YOUR_USERNAME/YOUR_REPO/actions`

### 3. Post-Deployment: Fix Existing Audio Paths

#### Option A: Use Web Migration Script (Recommended)

1. **Access the migration page:**
   ```
   https://private.bryarahmad.com/migrate_fix_audio_paths.php?authorize
   ```

2. **Review the status:**
   - See how many audio file paths need fixing
   - View examples of problematic paths

3. **Run the migration:**
   - Click "Run Migration Now"
   - Wait for completion confirmation
   - Verify all paths are fixed

4. **Security: Delete the script**
   ```
   Delete: migrate_fix_audio_paths.php
   ```

### 4. Post-Deployment: Add Performance Indexes (IMPORTANT!)

**This step significantly improves page loading speed:**

1. **Access the performance migration:**
   ```
   https://private.bryarahmad.com/migrate_add_indexes.php?authorize
   ```

2. **Review the indexes to be created:**
   - 7 database indexes for faster queries
   - No data changes - only performance improvements

3. **Click "Create Indexes Now"**
   - Wait for migration to complete
   - Verify all indexes were created successfully

4. **Delete the script after success:**
   ```
   Delete: migrate_add_indexes.php
   ```

**Performance improvements:**
- Lessons page loads 3-5x faster
- Going back to courses page is instant
- Authentication and progress tracking are quicker

### 5. Verification

Test audio playback and performance:

1. Log in to the platform
2. Navigate to courses page - should load quickly
3. Open a course with audio lessons
4. Try playing an audio file - should work without errors
5. Go back to courses page - should be instant (not slow)
6. Open another lesson - should load quickly

### 6. Clean Up

- [ ] Delete `migrate_fix_audio_paths.php` from server
- [ ] Delete `migrate_add_indexes.php` from server
- [ ] Verify `uploads/audio/` directory has correct permissions (755)
- [ ] Test uploading a new audio file to ensure it works

---

## Deployment Configuration

### GitHub Actions Workflow
- **File:** `.github/workflows/deploy.yml`
- **Server:** 157.173.209.174
- **Protocol:** FTPS (port 21)
- **Target:** `/public_html/private/`
- **Subdomain:** private.bryarahmad.com

### Database Configuration
- **Host:** localhost
- **Database:** u314367906_private
- **User:** u314367906_private

### Required Permissions
```
/public_html/private/       - 755
uploads/                    - 755
uploads/audio/              - 755
uploads/thumbnails/         - 755
```

---

## Troubleshooting

### Pages Loading Slowly (Lessons, Courses)

**Symptom:** Pages take 3-5+ seconds to load, especially when going back to courses page

**Solution:** Run the performance indexes migration
```
https://private.bryarahmad.com/migrate_add_indexes.php?authorize
```

This adds database indexes that speed up queries by 3-5x.

**Verify indexes were created:**
```sql
SHOW INDEX FROM lessons;
SHOW INDEX FROM user_progress;
SHOW INDEX FROM courses;
```

### Audio Still Not Playing After Migration

**Check 1: Verify paths were updated**
```sql
SELECT id, file_path FROM audio_files LIMIT 5;
```
All paths should start with `uploads/audio/`

**Check 2: Verify files exist on server**
- SSH/FTP into server
- Navigate to `public_html/private/uploads/audio/`
- Confirm audio files are present

**Check 3: Check file permissions**
```bash
chmod 755 uploads/audio/
chmod 644 uploads/audio/*.mp3
```

### Database Connection Errors

1. Verify credentials in `database/config.php`
2. Check if database exists in cPanel
3. Ensure database user has all privileges
4. Test connection: `mysql -h localhost -u u314367906_private -p`

### File Upload Errors

1. Check directory permissions (755 for folders, 644 for files)
2. Verify PHP upload limits in hosting control panel
3. Check `.htaccess` file isn't blocking uploads

### Deployment Not Triggering

1. Check GitHub Actions is enabled in repository settings
2. Verify workflow file syntax: `.github/workflows/deploy.yml`
3. Check GitHub secrets are configured correctly
4. Look for errors in Actions tab

---

## Future Uploads

**Good news!** All new audio files uploaded after this fix will automatically use relative paths. No additional migration needed.

---

## Security Reminders

- [ ] Change default admin password
- [ ] Use HTTPS (configure SSL in Hostinger)
- [ ] Monitor activity logs regularly
- [ ] Keep regular database backups
- [ ] Delete migration scripts after use

---

## Support

If issues persist:
1. Check server error logs in cPanel
2. Enable PHP error reporting temporarily
3. Use browser DevTools to check console errors
4. Verify database connection and queries

---

**Last Updated:** February 14, 2026
