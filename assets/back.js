jQuery(document).ready(function() {
    var input = document.getElementById('wpugmapsabox-content');
    if (!input || !google) return;
    var autocomplete = new google.maps.places.Autocomplete(input);
    var $lat = jQuery('#wpugmapsabox-lat');
    var $lng = jQuery('#wpugmapsabox-lng');

    // Prevent ENTER
    jQuery(input).keypress(function(e) {
        if (e.which == 13) e.preventDefault();
    });
    // Trigger an event on autocomplete
    google.maps.event.addListener(autocomplete, 'place_changed', function() {
        var place = autocomplete.getPlace();
        if (place && place.geometry && place.geometry.location) {
            var lat = place.geometry.location.lat();
            var lng = place.geometry.location.lng();
            jQuery(window).trigger('wpugmapsautocompletebox_newcoords', [lat, lng]);
            if ($lat) {
                $lat.val(lat);
            }
            if ($lng) {
                $lng.val(lng);
            }
        }
    });
});