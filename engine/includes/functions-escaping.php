<?php
/**
 * OpenSim Engine Escaping and Filtering Functions
 * 
 * Framework-agnostic functions for secure output and input processing.
 * No WordPress dependencies - uses Laminas libraries for robust functionality.
 */

use Laminas\Escaper\Escaper;
use Laminas\Filter;

// Initialize escaper (lazy loading)
function opensim_get_escaper() {
    static $escaper = null;
    if ($escaper === null) {
        $escaper = new Escaper('utf-8');
    }
    return $escaper;
}

// OUTPUT ESCAPING FUNCTIONS (opensim_esc_* series)
function opensim_esc_html($text) {
    return opensim_get_escaper()->escapeHtml($text);
}

function opensim_esc_attr($text) {
    return opensim_get_escaper()->escapeHtmlAttr($text);
}

function opensim_esc_url($url) {
    return opensim_get_escaper()->escapeUrl($url);
}

function opensim_esc_js($text) {
    return opensim_get_escaper()->escapeJs($text);
}

function opensim_esc_css($text) {
    return opensim_get_escaper()->escapeCss($text);
}

// INPUT FILTERING FUNCTIONS (opensim_filter_* series)
function opensim_filter_text($str) {
    $filter = new Filter\StripTags();
    return trim($filter->filter($str));
}

function opensim_filter_email($email) {
    return filter_var($email, FILTER_SANITIZE_EMAIL);
}

function opensim_filter_url($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

function opensim_filter_textarea($str) {
    $filter = new Filter\StripTags();
    $filter->setAttributesAllowed([]);
    $filter->setTagsAllowed([]);
    return trim($filter->filter($str));
}

function opensim_filter_key($key) {
    return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
}

function opensim_filter_int($value) {
    return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

function opensim_filter_float($value) {
    return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}
