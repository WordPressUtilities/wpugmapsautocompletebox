<?php

/*
Plugin Name: WPU Google Maps Autocomplete Box
Plugin URI: https://github.com/WordPressUtilities/wpugmapsautocompletebox
Description: Add a Google Maps Autocomplete box on edit post pages.
Version: 0.7.1.1
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUGMapsAutocompleteBox {

    public $version = '0.7.1.1';
    public $base_previewurl = '';
    public $dim = array();
    public $options = array();

    public function __construct() {
        if (!is_admin()) {
            return;
        }
        add_action('wp_loaded', array(&$this,
            'wp_loaded'
        ));

        $this->options = array(
            'plugin_publicname' => 'Maps Autocomplete',
            'plugin_name' => 'Maps Autocomplete',
            'plugin_id' => 'wpugmapsabox',
            'plugin_parent' => 'options-general.php',
            'plugin_pageslug' => 'wpugmapsabox'
        );

        $this->options['admin_url'] = admin_url($this->options['plugin_parent'] . '?page=' . $this->options['plugin_pageslug']);
    }

    public function wp_loaded() {
        load_plugin_textdomain('wpugmapsabox', false, dirname(plugin_basename(__FILE__)) . '/lang/');
        $locale = explode('_', get_locale());

        // Messages
        include 'inc/WPUBaseMessages.php';
        $this->messages = new \wpugmapsabox\WPUBaseMessages($this->options['plugin_id']);

        // Settings
        $this->apikey_message = sprintf(__('Please add an <a href="%s" target="_blank">API Key</a> with Google Places API Web Service, Google Maps JavaScript API & Google Static Maps API.', 'wpugmapsabox'), 'https://console.developers.google.com/apis/library?project=_');
        $this->settings_details = array(
            'create_page' => true,
            'plugin_id' => 'wpugmapsabox',
            'plugin_name' => 'Maps Autocomplete',
            'option_id' => 'wpugmapsabox_options',
            'sections' => array(
                'base' => array(
                    'name' => __('Settings')
                )
            )
        );
        $this->settings = array(
            'addlatlng' => array(
                'label' => __('Add Lat Lng', 'wpugmapsabox'),
                'label_check' => __('Add editable boxes with latitude & longitude.', 'wpugmapsabox'),
                'type' => 'checkbox'
            ),
            'addaddressfields' => array(
                'label' => __('Add Address Fields', 'wpugmapsabox'),
                'label_check' => __('Add editable boxes with address fields.', 'wpugmapsabox'),
                'type' => 'checkbox'
            ),
            'apikey' => array(
                'label' => __('Maps API Key', 'wpugmapsabox'),
                'help' => $this->apikey_message
            )
        );
        include 'inc/WPUBaseSettings.php';
        new \wpugmapsabox\WPUBaseSettings($this->settings_details, $this->settings);

        $this->settings_values = get_option($this->settings_details['option_id']);

        add_filter("plugin_action_links_" . plugin_basename(__FILE__), array(&$this,
            'add_settings_link'
        ));

        /* Base settings */
        $this->post_types = apply_filters('wpugmapsautocompletebox_posttypes', array(
            'post'
        ));
        $this->taxonomies = apply_filters('wpugmapsautocompletebox_taxonomies', array(
            'category'
        ));
        $apikey = '';
        $addlatlng = false;
        $addaddressfields = false;
        if (is_array($this->settings_values)) {
            $apikey = isset($this->settings_values['apikey']) ? trim($this->settings_values['apikey']) : '';
            $addlatlng = isset($this->settings_values['addlatlng']) ? (bool) $this->settings_values['addlatlng'] : false;
            $addaddressfields = isset($this->settings_values['addaddressfields']) ? (bool) $this->settings_values['addaddressfields'] : false;
        }
        $this->mainlang = apply_filters('wpugmapsautocompletebox_apilang', $locale[0]);
        $this->frontapi_key = apply_filters('wpugmapsautocompletebox_apikey', $apikey);
        $this->addlatlng = apply_filters('wpugmapsautocompletebox_addlatlng', $addlatlng);
        $this->addaddressfields = apply_filters('wpugmapsautocompletebox_addaddressfields', $addaddressfields);

        /* Init vars */
        $this->base_previewurl = 'https://maps.googleapis.com/maps/api/staticmap?center={{coordinates}}&zoom={{zoom}}&size={{dimensions}}&maptype=roadmap&markers={{coordinates}}&key=' . $this->frontapi_key;
        $this->dim = array(
            'lat' => array(
                'name' => __('Latitude', 'wpugmapsabox'),
                'type' => 'latlng'
            ),
            'lng' => array(
                'name' => __('Longitude', 'wpugmapsabox'),
                'type' => 'latlng'
            ),
            'street_number' => array(
                'name' => __('Street number', 'wpugmapsabox'),
                'type' => 'addressfields'
            ),
            'route' => array(
                'name' => __('Street name', 'wpugmapsabox'),
                'type' => 'addressfields'
            ),
            'postal_code' => array(
                'name' => __('Postal code', 'wpugmapsabox'),
                'type' => 'addressfields'
            ),
            'locality' => array(
                'name' => __('Locality', 'wpugmapsabox'),
                'type' => 'addressfields'
            ),
            'country' => array(
                'name' => __('Country', 'wpugmapsabox'),
                'type' => 'addressfields'
            )
        );

        /* API */
        if (!$this->frontapi_key) {
            add_action('admin_notices', array(&$this,
                'set_error_missing_apikey'
            ));
        }

        /* Setup boxes */
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

    /* Settings link */

    public function add_settings_link($links) {
        $settings_link = '<a href="' . $this->options['admin_url'] . '">' . __('Settings') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
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

    /* Settings */
    public function set_error_missing_apikey() {
        echo '<div class="error"><p>' . sprintf(__('You need an API Key to use <b>%s</b>. Please fill it in the <a href="%s">options page</a>.', 'wpugmapsabox'), $this->options['plugin_publicname'], $this->options['admin_url']) . '</p></div>';
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
        $base_dim = array();
        $this->dim = apply_filters('wpugmapsautocompletebox_dim', $this->dim);
        foreach ($this->dim as $id => $name) {
            $base_dim[$id] = get_term_meta($term_id, 'wpugmapsabox_' . $id, 1);
        }

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
        $base_dim = array();
        $this->dim = apply_filters('wpugmapsautocompletebox_dim', $this->dim);
        foreach ($this->dim as $id => $dim) {
            $_dim_id = (isset($dim['id'])) ? $dim['id'] : 'wpugmapsabox_' . $id;
            $base_dim[$id] = get_post_meta($post_id, $_dim_id, 1);
        }
        echo $this->renderbox_content($base_dim, $address_value, 'post');

    }

    /* Helpers */

    public function renderbox_content($base_dim = array(), $address_value = '', $type = 'post') {
        wp_nonce_field('wpugmapsabox_save_post_values', 'wpugmapsabox_meta_box_nonce');

        $html = '';

        if ($type == 'taxonomy') {
            $html .= '</table><h2>' . __('Geocoding', 'wpugmapsabox') . '</h2><table class="form-table">';
        }

        /* Address preview */
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

        /* Preview image for taxonomy */
        if ($type == 'taxonomy') {
            $html .= '<tr class="form-field term-group-wrap"><th></th><td>';
            $html .= $this->render_baseimg($base_dim, 'taxonomy');
            $html .= '</td></tr>';
        }

        /* Grid start for post */
        if ($type == 'post') {
            $html .= '<div class="wpugmapsabox-grid">';
            $html .= '<div class="map-latlng ' . ($this->addlatlng || $this->addaddressfields ? '' : 'map-latlng--noform') . '">';
        }

        /* Fields */
        $this->dim = apply_filters('wpugmapsautocompletebox_dim', $this->dim);
        foreach ($this->dim as $id => $field) {
            if ($this->addlatlng && $field['type'] == 'latlng' || $this->addaddressfields && $field['type'] == 'addressfields') {

                $label = '<label for="wpugmapsabox-' . $id . '">' . $field['name'] . '</label>';
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

        /* Grid end + Preview image for post */
        if ($type == 'post') {
            $html .= '</div>';
            $html .= $this->render_baseimg($base_dim);
            $html .= '</div>';
        }

        return $html;
    }

    public function renderbox_apikeytest($type) {
        $text = '<p>' . $this->apikey_message . '</p>';
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

    public function render_baseimg($base_dim = array(), $type = 'post') {
        $base_img = '';
        $coords = '';
        $zoom = 15;
        $dimensions = $this->addaddressfields ? '350x300' : '350x100';
        if ($this->addaddressfields && $this->addlatlng) {
            $dimensions = '350x400';
        }
        if ($type == 'taxonomy') {
            $dimensions = '600x250';
        }
        if ($base_dim['lat'] || $base_dim['lng']) {
            $coords = $base_dim['lat'] . ',' . $base_dim['lng'];
            $base_img = str_replace('{{coordinates}}', $coords, $this->base_previewurl);
            $base_img = str_replace('{{zoom}}', $zoom, $base_img);
            $base_img = str_replace('{{dimensions}}', $dimensions, $base_img);
            $base_img = '<a target="_blank" href="https://maps.google.com/?q=' . $coords . '"><img src="' . $base_img . '" alt="" /></a>';
        }
        return '<div data-dimensions="' . $dimensions . '" data-zoom="' . $zoom . '" data-model="' . $this->base_previewurl . '" class="map-preview" id="wpugmapsabox-preview">' . $base_img . '</div>';
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

        $this->dim = apply_filters('wpugmapsautocompletebox_dim', $this->dim);
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

        $this->dim = apply_filters('wpugmapsautocompletebox_dim', $this->dim);
        foreach ($this->dim as $id => $dim) {
            $_dim_id = (isset($dim['id'])) ? $dim['id'] : 'wpugmapsabox_' . $id;
            if (isset($_POST[$_dim_id])) {
                update_post_meta($post_id, $_dim_id, sanitize_text_field($_POST[$_dim_id]));
            }
        }
        if (isset($_POST['wpugmapsabox_address'])) {
            update_post_meta($post_id, 'wpugmapsabox_address', sanitize_text_field($_POST['wpugmapsabox_address']));
        }
    }

    /* ----------------------------------------------------------
      Install
    ---------------------------------------------------------- */

    public function uninstall() {
        delete_post_meta_by_key('wpugmapsabox_address');
        foreach ($this->dim as $id => $name) {
            delete_post_meta_by_key('wpugmapsabox_' . $id);
        }
    }

}

$WPUGMapsAutocompleteBox = new WPUGMapsAutocompleteBox();
