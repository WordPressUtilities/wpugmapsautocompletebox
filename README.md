# WPU Google Maps Autocomplete Box

Add a Google Maps Autocomplete box on edit post pages.

## How to manually add set-up the plugin :

```php
// API key
add_filter( 'wpugmapsautocompletebox_apikey', 'set_wpugmapsautocompletebox_apikey', 10, 3 );
function set_wpugmapsautocompletebox_apikey() {
    return 'mygoogleapikey';
}

// Box language
add_filter( 'wpugmapsautocompletebox_apilang', 'set_wpugmapsautocompletebox_apilang', 10, 3 );
function set_wpugmapsautocompletebox_apilang() {
    return 'fr';
}

// Post types
add_filter( 'wpugmapsautocompletebox_posttypes', 'set_wpugmapsautocompletebox_posttypes', 10, 3 );
function set_wpugmapsautocompletebox_posttypes($post_types) {
    $post_types[] = 'page';
    return $post_types;
}
```

## How to add an event :

Put the code below in your preferred JS file.

```js
jQuery(window).on('wpugmapsautocompletebox_newcoords',function(e,lat,lng){
    console.log(lat,lng);
});
```

## TODO

* Translation.
* Optional boxes for lat & lng.
* WPU Option for API Key.