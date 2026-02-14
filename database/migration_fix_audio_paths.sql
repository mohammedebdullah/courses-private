-- Migration: Fix Audio File Paths
-- Converts absolute paths to relative paths for cross-platform compatibility
-- Run this after deployment to fix existing audio file paths

-- Update Windows absolute paths (C:\xampp\htdocs\audio-course\uploads\audio\filename.mp3)
UPDATE audio_files 
SET file_path = CONCAT('uploads/audio/', stored_filename)
WHERE file_path LIKE 'C:%' OR file_path LIKE 'c:%';

-- Update Linux absolute paths (/var/www/html/.../uploads/audio/filename.mp3) if any
UPDATE audio_files 
SET file_path = CONCAT('uploads/audio/', stored_filename)
WHERE file_path LIKE '/%uploads/audio/%';

-- Update any other absolute paths to relative
UPDATE audio_files 
SET file_path = CONCAT('uploads/audio/', stored_filename)
WHERE file_path LIKE '%/uploads/audio/%' AND file_path NOT LIKE 'uploads/audio/%';

-- Verify the changes
SELECT id, lesson_id, stored_filename, file_path, 
       CASE 
           WHEN file_path LIKE 'uploads/audio/%' THEN 'OK'
           ELSE 'NEEDS FIX'
       END as status
FROM audio_files;
