-- Performance Optimization: Add Database Indexes
-- This improves query speed for lessons page and authentication

-- Index for user_progress lookups (used in lessons.php)
CREATE INDEX IF NOT EXISTS idx_user_progress_user_lesson 
ON user_progress(user_id, lesson_id);

-- Index for lesson queries by course
CREATE INDEX IF NOT EXISTS idx_lessons_course_status 
ON lessons(course_id, status, sort_order);

-- Index for audio_files by lesson
CREATE INDEX IF NOT EXISTS idx_audio_files_lesson 
ON audio_files(lesson_id);

-- Index for access_codes authentication
CREATE INDEX IF NOT EXISTS idx_access_codes_code_status 
ON access_codes(code, status);

-- Index for sessions
CREATE INDEX IF NOT EXISTS idx_sessions_user_active 
ON sessions(user_id, is_active, expires_at);

-- Index for activity_logs by user and type
CREATE INDEX IF NOT EXISTS idx_activity_logs_user_type_time 
ON activity_logs(user_id, action_type, created_at);

-- Index for courses status
CREATE INDEX IF NOT EXISTS idx_courses_status 
ON courses(status, created_at);

-- Verify indexes were created
SHOW INDEX FROM user_progress;
SHOW INDEX FROM lessons;
SHOW INDEX FROM audio_files;
SHOW INDEX FROM access_codes;
SHOW INDEX FROM sessions;
