/*global jQuery:false,google:false */

'use strict';

jQuery(document).ready(function() {

    var input = document.getElementById('wpugmapsabox-content');
    if (!input) {
        return;
    }
    var $input = jQuery(input);

    // Google cant be loaded
    if (typeof google == 'undefined') {
        // Hide box
        $input.parent().remove();
        return;
    }

    var autocomplete = new google.maps.places.Autocomplete(input);
    var $lat = jQuery('#wpugmapsabox-lat');
    var $lng = jQuery('#wpugmapsabox-lng');
    var $preview = jQuery('#wpugmapsabox-preview');

    // Prevent ENTER
    $input.keypress(function(e) {
        if (e.which == 13) e.preventDefault();
    });
    // Trigger an event on autocomplete
    google.maps.event.addListener(autocomplete, 'place_changed', function() {
        var place = autocomplete.getPlace();
        if (place && place.geometry && place.geometry.location) {
            var lat = place.geometry.location.lat();
            var lng = place.geometry.location.lng();
            jQuery(window).trigger('wpugmapsautocompletebox_newcoords', [lat, lng, place]);
            if ($lat) {
                $lat.val(lat);
            }
            if ($lng) {
                $lng.val(lng);
            }
            if ($preview && lat && lng) {
                // Set preview img
                var preview_img = $preview.attr('data-model').replace(/\{\{coordinates\}\}/g, lat + ',' + lng);
                $preview.html('<img src="' + preview_img + '" alt="" />');
            }
        }
    });
});
