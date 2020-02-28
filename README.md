# simple-php/assets
Small asset manager for PHP

## Usage:

Use add static method to include assets you need
```php
use SimplePHP\Assets;
...
Assets::('bootstrap');    // include asset library, defined in Assets::$libs
Assets::add('css/main.css'); // include single css file
Assets::add('js/script.js'); // include single js file
// or all at once:
Assets::add([
 'bootstrap',
 'css/main.js',
 'js/script.js'
]);
```

Output js and css tags in template 
```php
Assets::getCss(); // put this inside <head> ... </head> to output styles tags
Assets::getJs(); // put this in the end of page before </body> to output js script tags
```
