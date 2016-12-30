<?php

/*
Plugin Name: WPU Google Maps Autocomplete Box
Plugin URI: https://github.com/WordPressUtilities/wpugmapsautocompletebox
Description: Add a Google Maps Autocomplete box on edit post pages.
Version: 0.4.2
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUGMapsAutocompleteBox {

    public $version = '0.4.2';

    public function __construct() {
        if (!is_admin()) {
            return;
        }
        add_action('wp_loaded', array(&$this,
            'wp_loaded'
        ));
    }

    public function wp_loaded() {

        $this->post_types = apply_filters('wpugmapsautocompletebox_posttypes', array(
            'post'
        ));
        $this->taxonomies = apply_filters('wpugmapsautocompletebox_taxonomies', array(
            'category'
        ));

        load_plugin_textdomain('wpugmapsabox', false, dirname(plugin_basename(__FILE__)) . '/lang/');

        $locale = explode('_', get_locale());
        $this->mainlang = apply_filters('wpugmapsautocompletebox_apilang', $locale[0]);
        $this->frontapi_key = apply_filters('wpugmapsautocompletebox_apikey', '');
        $this->addlatlng = apply_filters('wpugmapsautocompletebox_addlatlng', false);

        $this->base_previewurl = 'https://maps.googleapis.com/maps/api/staticmap?center={{coordinates}}&zoom=14&size=300x100&maptype=roadmap&markers={{coordinates}}&key=' . $this->frontapi_key;
        $this->dim = array(
            'lat' => __('Latitude', 'wpugmapsabox'),
            'lng' => __('Longitude', 'wpugmapsabox')
        );

        add_action('add_meta_boxes', array(&$this,
            'add_custom_meta_boxes'
        ));

        foreach ($this->taxonomies as $taxonomy_box) {
            add_action($taxonomy_box . '_edit_form_fields', array(&$this,
                'add_taxo_meta_box'
            ), 10, 2);
            add_action('edited_' . $taxonomy_box, array(&$this,
                'save_taxo_values'
            ), 10, 2);

        }

        add_action('admin_enqueue_scripts', array(&$this,
            'enqueue_scripts'
        ));
        add_action('save_post', array(&$this,
            'save_post_values'
        ));
    }

    public function add_custom_meta_boxes($post) {
        foreach ($this->post_types as $post_type_box) {
            add_meta_box('geocoding-metabox', __('Geocoding'), array(&$this,
                'add_posttype_meta_box'
            ), $post_type_box);
        }
    }

    public function enqueue_scripts() {
        $currentScreen = get_current_screen();

        if ($currentScreen->base != "post" && $currentScreen->base != "term") {
            return;
        }
        if ($currentScreen->base == "post" && !in_array($currentScreen->post_type, $this->post_types)) {
            return;
        }
        if ($currentScreen->base == "term" && !in_array($currentScreen->taxonomy, $this->taxonomies)) {
            return;
        }

        wp_enqueue_script('wpugmapsabox-maps', 'https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&key=' . $this->frontapi_key . '&language=' . $this->mainlang . '&sensor=false', false, '3.exp', true);
        wp_enqueue_style('wpugmapsabox-backcss', plugins_url('/assets/back.css', __FILE__), array(), $this->version);
        wp_enqueue_script('wpugmapsabox-back', plugins_url('/assets/back.js', __FILE__), array(
            'jquery'
        ), $this->version, true);
    }

    /* ----------------------------------------------------------
      Render boxes
    ---------------------------------------------------------- */

    /* TAXO */

    public function add_taxo_meta_box($term, $taxonomy) {

        if (!$this->renderbox_apikeytest('taxonomy')) {
            return;
        }

        $term_id = 0;
        if (is_object($term) && isset($term->term_id)) {
            $term_id = $term->term_id;
        }
        $address_value = get_term_meta($term_id, 'wpugmapsabox_address', 1);
        $base_dim = array(
            'lat' => get_term_meta($term_id, 'wpugmapsabox_lat', 1),
            'lng' => get_term_meta($term_id, 'wpugmapsabox_lng', 1)
        );

        echo $this->renderbox_content($base_dim, $address_value, 'taxonomy');

    }

    /* POST */

    public function add_posttype_meta_box() {

        if (!$this->renderbox_apikeytest('post')) {
            return;
        }

        global $post;
        $post_id = 0;
        if (is_object($post) && isset($post->ID)) {
            $post_id = $post->ID;
        }
        $address_value = get_post_meta($post_id, 'wpugmapsabox_address', 1);
        $base_dim = array(
            'lat' => get_post_meta($post_id, 'wpugmapsabox_lat', 1),
            'lng' => get_post_meta($post_id, 'wpugmapsabox_lng', 1)
        );

        echo $this->renderbox_content($base_dim, $address_value, 'post');

    }

    /* Helpers */

    public function renderbox_content($base_dim = array(), $address_value = '', $type = 'post') {
        wp_nonce_field('wpugmapsabox_save_post_values', 'wpugmapsabox_meta_box_nonce');

        $html = '';
        if ($type == 'post') {
            $html .= '<div class="wpugmapsabox-grid">';
            $html .= '<div class="map-latlng ' . ($this->addlatlng ? '' : 'map-latlng--noform') . '">';
        } else {
            $html .= '</table><h2>' . __('Geocoding', 'wpugmapsabox') . '</h2><table class="form-table">';
        }

        foreach ($this->dim as $id => $name) {
            if ($this->addlatlng) {

                $label = '<label for="wpugmapsabox-' . $id . '">' . $name . '</label>';
                $input = '<input id="wpugmapsabox-' . $id . '" type="text" name="wpugmapsabox_' . $id . '" value="' . $base_dim[$id] . '" />';

                if ($type == 'post') {
                    $html .= '<p>' . $label . '<br />';
                    $html .= $input;
                    $html .= '</p>';
                } else {
                    $html .= '<tr class="form-field term-group-wrap">';
                    $html .= '<th scope="row">' . $label . '</th>';
                    $html .= '<td>' . $input . '</td>';
                    $html .= '</tr>';
                }
            } else {
                $input = '<input id="wpugmapsabox-' . $id . '" type="hidden" name="wpugmapsabox_' . $id . '" value="' . $base_dim[$id] . '" />';
                if ($type == 'post') {
                    $html .= $input;
                } else {
                    $html .= '<tr class="screen-reader-text"><td colspan="2">' . $input . '</td></tr>';
                }
            }
        }

        if ($type == 'post') {
            $html .= '</div>';
            $html .= $this->render_baseimg($base_dim);
            $html .= '</div>';
        } else {
            $html .= '<tr class="form-field term-group-wrap"><th></th><td>';
            $html .= $this->render_baseimg($base_dim);
            $html .= '</td></tr>';
        }

        $label = '<label for="wpugmapsabox-content">' . __('Address', 'wpugmapsabox') . '</label>';
        $input = '<input id="wpugmapsabox-content" type="text" name="wpugmapsabox_address" class="widefat" value="' . esc_attr($address_value) . '" />';
        $help = __('Please write an address below and click on a suggested result to update GPS coordinates', 'wpugmapsabox');

        if ($type == 'taxonomy') {
            $html .= '<tr class="form-field term-group-wrap">';
            $html .= '<th scope="row">' . $label . '</th>';
            $html .= '<td>' . $input . '<p class="description">' . $help . '</p></td>';
            $html .= '</tr>';

        } else {
            $html .= '<p>';
            $html .= $label . '<br />';
            $html .= $input . '<br />';
            $html .= '<small>' . $help . '</small>';
            $html .= '</p>';
        }

        return $html;
    }

    public function renderbox_apikeytest($type) {
        $text = '<p>' . sprintf(__('Please add an <a href="%s" target="_blank">API Key</a> with Google Places API Web Service & Google Static Maps API.', 'wpugmapsabox'), 'https://console.developers.google.com/apis/library?project=_') . '</p>';
        if (!$this->frontapi_key) {
            if ($type == 'taxonomy') {
                echo '<tr><th></th><td colspan="2">' . $text . '</td></tr>';
            } else {
                echo $text;
            }
            return false;
        }
        return true;
    }

    public function render_baseimg($base_dim = array()) {
        $base_img = '';
        $coords = '';
        if ($base_dim['lat'] || $base_dim['lng']) {
            $coords = $base_dim['lat'] . ',' . $base_dim['lng'];
            $base_img = str_replace('{{coordinates}}', $coords, $this->base_previewurl);
            $base_img = '<a target="_blank" href="https://maps.google.com/?q=' . $coords . '"><img src="' . $base_img . '" alt="" /></a>';
        }
        return '<div data-model="' . $this->base_previewurl . '" class="map-preview" id="wpugmapsabox-preview">' . $base_img . '</div>';
    }

    /* ----------------------------------------------------------
      Save values
    ---------------------------------------------------------- */

    /* TAXO */

    public function save_taxo_values($term_id, $tt_id) {

        // Check nonce
        if (!isset($_POST['wpugmapsabox_meta_box_nonce']) || !wp_verify_nonce($_POST['wpugmapsabox_meta_box_nonce'], 'wpugmapsabox_save_post_values')) {
            return;
        }

        if (!current_user_can('manage_categories')) {
            return;
        }

        foreach ($this->dim as $id => $name) {
            if (isset($_POST['wpugmapsabox_' . $id])) {
                update_term_meta($term_id, 'wpugmapsabox_' . $id, sanitize_text_field($_POST['wpugmapsabox_' . $id]));
            }
        }
        if (isset($_POST['wpugmapsabox_address'])) {
            update_term_meta($term_id, 'wpugmapsabox_address', sanitize_text_field($_POST['wpugmapsabox_address']));
        }
    }

    /* POST */

    public function save_post_values($post_id) {

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check nonce
        if (!isset($_POST['wpugmapsabox_meta_box_nonce']) || !wp_verify_nonce($_POST['wpugmapsabox_meta_box_nonce'], 'wpugmapsabox_save_post_values')) {
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
        if (isset($_POST['wpugmapsabox_address'])) {
            update_post_meta($post_id, 'wpugmapsabox_address', sanitize_text_field($_POST['wpugmapsabox_address']));
        }
    }
}

$WPUGMapsAutocompleteBox = new WPUGMapsAutocompleteBox();
