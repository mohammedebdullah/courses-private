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

-- Index for user_sessions (correct table name)
CREATE INDEX IF NOT EXISTS idx_user_sessions_user_active 
ON user_sessions(user_id, is_active, expires_at);

-- Index for activity_logs by user and action (correct column name)
CREATE INDEX IF NOT EXISTS idx_activity_logs_user_action_time 
ON activity_logs(user_id, action, created_at);

-- Index for courses status
CREATE INDEX IF NOT EXISTS idx_courses_status 
ON courses(status, created_at);

-- Verify the changes
SELECT 'Indexes created successfully!' as status;
