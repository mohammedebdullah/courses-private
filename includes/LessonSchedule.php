<?php
/**
 * Lesson Scheduling Helper Class
 * Handles lesson availability based on schedule
 */

class LessonSchedule {
    
    /**
     * Check if a lesson is currently available based on schedule
     * Uses server time to prevent user manipulation
     * 
     * @param array $lesson Lesson data with start_datetime and end_datetime
     * @return array ['available' => bool, 'status' => string, 'message' => string]
     */
    public static function checkAvailability($lesson) {
        $now = new DateTime('now');
        
        $startTime = null;
        $endTime = null;
        
        // Parse start datetime
        if (!empty($lesson['start_datetime'])) {
            try {
                $startTime = new DateTime($lesson['start_datetime']);
            } catch (Exception $e) {
                $startTime = null;
            }
        }
        
        // Parse end datetime
        if (!empty($lesson['end_datetime'])) {
            try {
                $endTime = new DateTime($lesson['end_datetime']);
            } catch (Exception $e) {
                $endTime = null;
            }
        }
        
        // Check if lesson hasn't started yet
        if ($startTime && $now < $startTime) {
            return [
                'available' => false,
                'status' => 'scheduled',
                'message' => 'هێشتا دەمێ ڤێ وانێ نەهاتیە',
                'start_time' => $startTime,
                'end_time' => $endTime
            ];
        }
        
        // Check if lesson has expired
        if ($endTime && $now > $endTime) {
            return [
                'available' => false,
                'status' => 'expired',
                'message' => 'دەمێ ڤێ وانێ بسەرڤە چوویە',
                'start_time' => $startTime,
                'end_time' => $endTime
            ];
        }
        
        // Lesson is available
        return [
            'available' => true,
            'status' => 'active',
            'message' => '',
            'start_time' => $startTime,
            'end_time' => $endTime
        ];
    }
    
    /**
     * Get formatted time remaining until lesson starts or expires
     * 
     * @param DateTime $targetTime
     * @return string Formatted time remaining
     */
    public static function getTimeRemaining($targetTime) {
        if (!$targetTime) {
            return '';
        }
        
        $now = new DateTime('now');
        $interval = $now->diff($targetTime);
        
        if ($interval->days > 0) {
            return $interval->days . ' رۆژ';
        } elseif ($interval->h > 0) {
            return $interval->h . ' دەمژمێر';
        } elseif ($interval->i > 0) {
            return $interval->i . ' خولەک';
        } else {
            return 'چەند چرکەیەک';
        }
    }
    
    /**
     * Filter lessons array to only show available lessons (for frontend)
     * 
     * @param array $lessons Array of lessons
     * @return array Filtered array with availability info added
     */
    public static function filterAvailableLessons($lessons) {
        $filtered = [];
        
        foreach ($lessons as $lesson) {
            $availability = self::checkAvailability($lesson);
            $lesson['_availability'] = $availability;
            
            // Only show lessons that are active or will start soon (scheduled)
            // Hide lessons that haven't started yet
            if ($availability['status'] !== 'scheduled') {
                $filtered[] = $lesson;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Get all lessons for admin (no filtering, but with availability status)
     * 
     * @param array $lessons Array of lessons
     * @return array Lessons with availability info added
     */
    public static function addAvailabilityInfo($lessons) {
        foreach ($lessons as &$lesson) {
            $lesson['_availability'] = self::checkAvailability($lesson);
        }
        return $lessons;
    }
    
    /**
     * Validate schedule dates
     * 
     * @param string $startDatetime
     * @param string $endDatetime
     * @return array ['valid' => bool, 'error' => string]
     */
    public static function validateSchedule($startDatetime, $endDatetime) {
        // Both can be NULL (lesson always available)
        if (empty($startDatetime) && empty($endDatetime)) {
            return ['valid' => true, 'error' => ''];
        }
        
        // If only start is set, that's valid
        if (!empty($startDatetime) && empty($endDatetime)) {
            return ['valid' => true, 'error' => ''];
        }
        
        // If only end is set, that's also valid
        if (empty($startDatetime) && !empty($endDatetime)) {
            return ['valid' => true, 'error' => ''];
        }
        
        // Both are set - validate end is after start
        try {
            $start = new DateTime($startDatetime);
            $end = new DateTime($endDatetime);
            
            if ($end <= $start) {
                return ['valid' => false, 'error' => 'دەمێ دوماهیێ دڤێت پشتی دەمێ دەستپێکرنێ بیت'];
            }
            
            return ['valid' => true, 'error' => ''];
        } catch (Exception $e) {
            return ['valid' => false, 'error' => 'دەم شاشە'];
        }
    }
    
    /**
     * Format datetime for display
     * 
     * @param string $datetime
     * @param string $format
     * @return string
     */
    public static function formatDateTime($datetime, $format = 'Y-m-d H:i') {
        if (empty($datetime)) {
            return '-';
        }
        
        try {
            $dt = new DateTime($datetime);
            return $dt->format($format);
        } catch (Exception $e) {
            return '-';
        }
    }
}
