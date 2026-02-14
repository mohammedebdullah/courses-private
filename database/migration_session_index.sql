-- Add index on session_token for faster session validation
-- This query runs on EVERY protected page load and API call

ALTER TABLE user_sessions 
ADD INDEX idx_user_sessions_token (session_token);

-- Verify indexes on user_sessions table
SHOW INDEX FROM user_sessions;
