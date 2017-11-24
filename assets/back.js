/*global jQuery:false,google:false */

jQuery(document).ready(function() {
    'use strict';

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
    var address_fields = ['premise', 'route', 'postal_code', 'country', 'locality', 'street_number'];
    var $preview = jQuery('#wpugmapsabox-preview');

    // Prevent native ENTER press
    $input.keypress(function(e) {
        if (e.which == 13) e.preventDefault();
    });

    // Trigger an event on autocomplete
    google.maps.event.addListener(autocomplete, 'place_changed', function() {
        var i, len, tmp_field,
            place_details = {},
            place = autocomplete.getPlace();
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
            console.log(place);
            if (typeof place == 'object' && place.address_components.length) {
                for (i = 0, len = place.address_components.length; i < len; i++) {
                    place_details[place.address_components[i].types[0]] = place.address_components[i].long_name;
                }
                for (i = 0, len = address_fields.length; i < len; i++) {
                    tmp_field = jQuery('#wpugmapsabox-' + address_fields[i]);
                    if (place_details[address_fields[i]]) {
                        tmp_field.val(place_details[address_fields[i]]);
                    }
                    else {
                        tmp_field.val('');
                    }
                }
            }
            if ($preview && lat && lng) {
                var coords = lat + ',' + lng;
                // Set preview img
                var preview_img = $preview.attr('data-model').replace(/\{\{coordinates\}\}/g, coords);
                preview_img = preview_img.replace(/\{\{dimensions\}\}/g, $preview.attr('data-dimensions'), preview_img);
                preview_img = preview_img.replace(/\{\{zoom\}\}/g, $preview.attr('data-zoom'), preview_img);
                $preview.html('<a target="_blank" href="https://maps.google.com/?q=' + coords + '"><img src="' + preview_img + '" alt="" />');
            }
        }
    });
});
