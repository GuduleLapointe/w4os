<?php
/**
 * OpenSimulator Flux Class - Framework Agnostic
 * 
 * Core flux/social messaging utilities without framework dependencies
 */

class OpenSim_Flux {
    
    /**
     * Generate title from content (first 10 words)
     */
    public static function generate_title_from_content($content) {
        $words = explode(' ', strip_tags($content));
        $title_words = array_slice($words, 0, 10);
        $title = implode(' ', $title_words);
        
        if (count($words) > 10) {
            $title .= '...';
        }
        
        return $title;
    }

    /**
     * Format post content for display - convert URLs to links
     */
    public static function format_post_content($content, $target_blank = false) {
        if (empty($content)) {
            return '';
        }

        // Convert URLs to links
        $content = self::auto_link($content);
        
        // Add target="_blank" if requested
        if ($target_blank) {
            $content = preg_replace('/<a (.*?)>/', '<a $1 target="_blank">', $content);
        }
        
        return $content;
    }

    /**
     * Convert URLs in text to clickable links
     */
    public static function auto_link($text) {
        $pattern = '/\b(?:(?:https?|ftp):\/\/|www\.)[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i';
        return preg_replace_callback($pattern, function($matches) {
            $url = $matches[0];
            $display_url = $url;
            
            // Add http:// if it starts with www.
            if (strpos($url, 'www.') === 0) {
                $url = 'http://' . $url;
            }
            
            return '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($display_url) . '</a>';
        }, $text);
    }

    /**
     * Get relative time (e.g., "2 hours ago")
     */
    public static function get_relative_time($timestamp) {
        if (empty($timestamp)) {
            return '';
        }
        
        $time_diff = time() - $timestamp;
        
        if ($time_diff < 60) {
            return 'just now';
        } elseif ($time_diff < 3600) {
            $minutes = floor($time_diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($time_diff < 86400) {
            $hours = floor($time_diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($time_diff < 604800) {
            $days = floor($time_diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $timestamp);
        }
    }

    /**
     * Validate post content
     */
    public static function validate_content($content) {
        if (empty($content)) {
            return 'Content cannot be empty';
        }
        
        $content = trim($content);
        if (strlen($content) < 1) {
            return 'Content is too short';
        }
        
        if (strlen($content) > 1000) {
            return 'Content is too long (maximum 1000 characters)';
        }
        
        return true;
    }

    /**
     * Clean and sanitize content
     */
    public static function sanitize_content($content) {
        return trim(strip_tags($content));
    }
}
