# WPU Google Maps Autocomplete Box

Add a Google Maps Autocomplete box on edit post pages.

## How to manually add set-up the plugin :

```php
// API key : Google Places API Web Service & Google Static Maps API
add_filter( 'wpugmapsautocompletebox_apikey', 'set_wpugmapsautocompletebox_apikey', 10, 3 );
function set_wpugmapsautocompletebox_apikey() {
    return 'mygoogleapikey';
}

// Box language (default to current locale)
add_filter( 'wpugmapsautocompletebox_apilang', 'set_wpugmapsautocompletebox_apilang', 10, 3 );
function set_wpugmapsautocompletebox_apilang() {
    return 'fr';
}

// Add visible lat / lng post metas
add_filter('wpugmapsautocompletebox_addlatlng', '__return_true', 10, 1);

// Post types
add_filter( 'wpugmapsautocompletebox_posttypes', 'set_wpugmapsautocompletebox_posttypes', 10, 3 );
function set_wpugmapsautocompletebox_posttypes($post_types) {
    $post_types[] = 'page';
    return $post_types;
}

// Taxonomies
add_filter( 'wpugmapsautocompletebox_taxonomies', 'set_wpugmapsautocompletebox_taxonomies', 10, 3 );
function set_wpugmapsautocompletebox_taxonomies($taxonomies) {
    $taxonomies[] = 'category';
    return $taxonomies;
}
```

## How to add a JS event :

Put the code below in your favorite JS file.

```js
jQuery(window).on('wpugmapsautocompletebox_newcoords',function(e,lat,lng,place){
    // Coordinates
    console.log(lat,lng);
    // Place object
    console.log(place);
});
```

## How to retrieve dimension values

```php
$lat = get_post_meta(get_the_ID(),'wpugmapsabox_lat',1);
$lng = get_post_meta(get_the_ID(),'wpugmapsabox_lng',1);
```

