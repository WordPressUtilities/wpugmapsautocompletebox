<?php

/*
Plugin Name: WPU Google Maps Autocomplete Box
Plugin URI: http://github.com/Darklg/WPUtilities
Description: Add a Google Maps Autocomplete box on edit post pages.
Version: 0.1
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUGMapsAutocompleteBox {
    function __construct() {
        if (!is_admin()) {
            return;
        }
        add_action('add_meta_boxes', array(&$this,
            'adding_custom_meta_boxes'
        ));
        add_action('admin_head', array(&$this,
            'head_autocomplete'
        ));
        add_action('admin_enqueue_scripts', array(&$this,
            'enqueue_scripts'
        ));
    }

    function adding_custom_meta_boxes($post) {

        $post_types = apply_filters('wpugmapsautocompletebox_posttypes', array('post'));
        add_meta_box('geocoding-metabox', __('Geocoding') , array(&$this,
            'render_box_geocoding'
        ) , 'post');
    }

    function enqueue_scripts() {
        $frontapi_key = apply_filters('wpugmapsautocompletebox_apikey', '');
        $mainlang = apply_filters('wpugmapsautocompletebox_apilang', 'en');
        wp_enqueue_script('wpugmapsabox-maps', 'https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&key=' . $frontapi_key . '&language=' . $mainlang . '&sensor=false', false, '3');
        wp_enqueue_script('wpugmapsabox-back', plugins_url('/assets/back.js', __FILE__) , array(
            'jquery'
        ));
    }

    function render_box_geocoding() {
        echo '<p><label for="wpugmapsabox-content">' . __('Please write an address below and click on a suggested result to update GPS Coordinates', 'wpugmapsabox') . '</label></p>';
        echo '<p><input id="wpugmapsabox-content" type="text" name="_geocoding" class="widefat" value="" /></p>';
    }

}

$WPUGMapsAutocompleteBox = new WPUGMapsAutocompleteBox();
