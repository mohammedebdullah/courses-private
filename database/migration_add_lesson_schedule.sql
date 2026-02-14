-- Migration: Add scheduling fields to lessons table
-- Run this to add start_datetime and end_datetime columns

USE audio_course_db;

-- Add scheduling columns to lessons table
ALTER TABLE lessons 
ADD COLUMN start_datetime DATETIME NULL COMMENT 'Lesson becomes visible after this time',
ADD COLUMN end_datetime DATETIME NULL COMMENT 'Lesson becomes unavailable after this time',
ADD INDEX idx_schedule (start_datetime, end_datetime);

-- Update existing lessons to be available immediately (NULL means always available)
-- If you want all existing lessons to have start time = now, uncomment below:
-- UPDATE lessons SET start_datetime = NOW() WHERE start_datetime IS NULL;

-- Note: NULL values mean:
-- start_datetime NULL = available immediately  
-- end_datetime NULL = never expires
