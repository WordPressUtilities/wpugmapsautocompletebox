<?php

/*
Plugin Name: WPU Google Maps Autocomplete Box
Plugin URI: https://github.com/WordPressUtilities/wpugmapsautocompletebox
Description: Add a Google Maps Autocomplete box on edit post pages.
Version: 0.3
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
        add_action('wp_loaded', array(&$this,
            'wp_loaded'
        ));
    }

    function wp_loaded() {
        load_plugin_textdomain('wpugmapsabox', false, dirname(plugin_basename(__FILE__)) . '/lang/');

        $locale = explode('_', get_locale());
        $this->mainlang = apply_filters('wpugmapsautocompletebox_apilang', $locale[0]);
        $this->frontapi_key = apply_filters('wpugmapsautocompletebox_apikey', '');
        $this->addlatlng = apply_filters('wpugmapsautocompletebox_addlatlng', false);
        $this->post_types = apply_filters('wpugmapsautocompletebox_posttypes', array(
            'post'
        ));
        $this->base_previewurl = 'https://maps.googleapis.com/maps/api/staticmap?center={{coordinates}}&zoom=14&size=300x100&maptype=roadmap&markers={{coordinates}}&key=' . $this->frontapi_key;
        $this->dim = array(
            'lat' => __('Latitude', 'wpugmapsabox') ,
            'lng' => __('Longitude', 'wpugmapsabox') ,
        );

        add_action('add_meta_boxes', array(&$this,
            'add_custom_meta_boxes'
        ));
        add_action('admin_enqueue_scripts', array(&$this,
            'enqueue_scripts'
        ));
        add_action('save_post', array(&$this,
            'save_meta_box_data'
        ));
    }

    function add_custom_meta_boxes($post) {
        foreach ($this->post_types as $post_type_box) {
            add_meta_box('geocoding-metabox', __('Geocoding') , array(&$this,
                'render_box_geocoding'
            ) , $post_type_box);
        }
    }

    function enqueue_scripts() {
        wp_enqueue_script('wpugmapsabox-maps', 'https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&key=' . $this->frontapi_key . '&language=' . $this->mainlang . '&sensor=false', false, '3');
        wp_enqueue_style('wpugmapsabox-backcss', plugins_url('/assets/back.css', __FILE__));
        wp_enqueue_script('wpugmapsabox-back', plugins_url('/assets/back.js', __FILE__) , array(
            'jquery'
        ));
    }

    function render_box_geocoding() {
        global $post;
        $post_id = 0;
        if (is_object($post) && isset($post->ID)) {
            $post_id = $post->ID;
        }

        wp_nonce_field('wpugmapsabox_save_meta_box_data', 'wpugmapsabox_meta_box_nonce');

        $base_dim = array(
            'lat' => 0,
            'lng' => 0
        );
        echo '<div class="wpugmapsabox-grid">';
        if ($this->addlatlng) {
            echo '<div class="map-latlng">';
            foreach ($this->dim as $id => $name) {
                $base_dim[$id] = get_post_meta($post_id, 'wpugmapsabox_' . $id, 1);
                echo '<p><label for="wpugmapsabox-' . $id . '">' . $name . '</label><br />';
                echo '<input id="wpugmapsabox-' . $id . '" type="text" name="wpugmapsabox_' . $id . '" value="' . get_post_meta($post_id, 'wpugmapsabox_' . $id, 1) . '" /></p>';
            }
            echo '</div>';
        }
        $base_img = '';
        if ($base_dim['lat'] || $base_dim['lng']) {
            $base_img = str_replace('{{coordinates}}', $base_dim['lat'].','.$base_dim['lng'], $this->base_previewurl);
            $base_img = '<img src="' . $base_img . '" alt="" />';
        }
        echo '<div data-model="' . $this->base_previewurl . '" class="map-preview" id="wpugmapsabox-preview">' . $base_img . '</div>';

        echo '</div>';

        echo '<p><label for="wpugmapsabox-content">' . __('Address', 'wpugmapsabox') . '</label><br />';
        echo '<input id="wpugmapsabox-content" type="text" name="wpugmapsabox_geocoding" class="widefat" value="" /><br />';
        echo '<small>' . __('Please write an address below and click on a suggested result to update GPS coordinates', 'wpugmapsabox') . '</small></p>';
    }

    function save_meta_box_data($post_id) {

        if (!isset($_POST['wpugmapsabox_meta_box_nonce']) || !wp_verify_nonce($_POST['wpugmapsabox_meta_box_nonce'], 'wpugmapsabox_save_meta_box_data')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        foreach ($this->dim as $id => $name) {
            if (isset($_POST['wpugmapsabox_' . $id])) {
                update_post_meta($post_id, 'wpugmapsabox_' . $id, sanitize_text_field($_POST['wpugmapsabox_' . $id]));
            }
        }
    }
}

$WPUGMapsAutocompleteBox = new WPUGMapsAutocompleteBox();
