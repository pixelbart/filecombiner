# FileCombiner
Combines files and adds them into a single file with PHP. Without performing any minification.

## Installation

Add the `class-filecombiner.php` to your project and include the file.

## Usage

```php
include 'class-filecombiner.php';

// CSS

$source = get_stylesheet_directory() . '/test/source';
$target = get_stylesheet_directory() . '/test/combined.css';

$combiner = new FileCombiner($source, $target, '.css');

$combiner->watch();

// JS
    
$source = get_stylesheet_directory() . '/test/source';
$target = get_stylesheet_directory() . '/test/combined.js';

$combiner = new FileCombiner($source, $target, '.js');

$combiner->watch();
```

If you use WordPress, the whole thing can also be enqueued directly. If no version is specified, a `uniqid()` is set, so that the browser cache for the file does not take effect and you do not have to clear a cache.

```php
// Usage
add_action('wp_enqueue_scripts', function() {

    include locate_template('class-filecombiner.php');

    // CSS

    $source = get_stylesheet_directory() . '/test/source';
    $target = get_stylesheet_directory() . '/test/combined.css';

    $combiner = new FileCombiner($source, $target, '.css');

    $combiner->watch();
    $combiner->enqueue();

    // JS
    
    $source = get_stylesheet_directory() . '/test/source';
    $target = get_stylesheet_directory() . '/test/combined.js';

    $combiner = new FileCombiner($source, $target, '.js');

    $combiner->watch();
    $combiner->enqueue();
    
    // name, supports, version, in foot (default is true, for js files)
    $combiner->enqueue('enqueue_name', ['jquery'], '1.0.0', true);
});
```

## Callback

You can also execute a callback on the result. In this way, for example, a minification can be set up.

```php
/**
 * Removes spaces and breaks.
 * 
 * @param string $content
 * @return string
 */
function removeUnnecessaryThings($content)
{
    return preg_replace('/\s*/m', '', $content);
}

$source = get_stylesheet_directory() . '/test/source';
$target = get_stylesheet_directory() . '/test/combined.css';

$combiner = new FileCombiner($source, $target, '.css');

$combiner->watch();

$combiner->setTargetCallback('removeUnnecessaryThings');
```
## Disclaimer

The class has been more of a side project that I needed for internal purposes. I didn't explicitly consider any security standard here and also didn't include any debug functionality. This all needs to be checked and added by you. The class works as it is and is easily modifiable.
