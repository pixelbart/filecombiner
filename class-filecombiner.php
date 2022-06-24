<?php
/**
 * FileCombiner Class for PhP
 *
 * Combines files and makes them into a single file.
 * Checks by means of modification date in source
 * directory whether a file has changed and renews
 * the target file.
 *
 * @version 1.0.0
 * @author  Pixelbart <me@pixelbart.de>
 * @link    https://github.com/pixelbart/filecombiner/
 * @license MIT License (https://opensource.org/licenses/MIT)
 */
class FileCombiner
{
    /**
     * @var string
     */
    private $_source;

    /**
     * @var string
     */
    private $_target;

    /**
     * @var string
     */
    private $_targetDir;

    /**
     * @var string
     */
    private $_extension = '.js';

    /**
     * @var array
     */
    public $extensions = ['.js', '.css', '.scss', '.less'];

    /**
     * @var string
     */
    private $_targetCallback;

    /**
     * @param string $source    The folder where all the files are.
     * @param string $target    The destination file where everything should be combined.
     * @param string $extension The file extension to be respected.
     */
    public function __construct(string $source, string $target, string $extension = '.js')
    {
        if (is_dir($source)) {
            $this->_source = rtrim($source, '/');
        }

        if (!file_exists($target)) {
            $this->createFiles($target, '');
        }

        $this->_target = $target;
        $this->_targetDir = rtrim(dirname($target), '/');

        if (in_array($extension, $this->extensions)) {
            $this->_extension = $extension;
        }
    }

    /**
     * A function, or method, to be applied to the contents of the target file at the end.
     * Is useful if you want to minify the file.
     *
     * @param string $callback The function/method to be executed.
     *
     * @return void
     */
    public function setTargetCallback(string $callback)
    {
        $this->_targetCallback = $callback;
    }

    /**
     * Checks the source directory and triggers processing if a file has changed.
     *
     * @return bool
     */
    public function watch()
    {
        if (!$this->_source || !$this->_target || !$this->_targetDir) {
            return false;
        }

        if (!is_dir($this->_source)) {
            return false;
        }

        $lastCheckFile = sprintf('%s/_timestamp.txt', $this->_targetDir);

        if (!file_exists($lastCheckFile)) {
            $this->createFiles($lastCheckFile, '');
        }

        $lastcheck = $this->readFiles($lastCheckFile, '');

        if (!is_numeric($lastcheck)) {
            $lastcheck = 0;
        }

        $source = sprintf('%s/*%s', $this->_source, $this->_extension);

        foreach (glob($source) as $filepath) {
            if (filemtime($filepath) > $lastcheck) {
                $this->createFiles($lastCheckFile, time());
                return $this->handleSource();
            }
        }

        return false;
    }

    /**
     * Performs an update of the files.
     *
     * @return bool
     */
    public function forceHandleSource()
    {
        $lastCheckFile = sprintf('%s/_timestamp.txt', $this->_targetDir);

        if (!file_exists($lastCheckFile)) {
            $this->createFiles($lastCheckFile, '');
        }

        $lastcheck = $this->readFiles($lastCheckFile, '');

        if (!is_numeric($lastcheck)) {
            $lastcheck = 0;
        }

        $this->createFiles($lastCheckFile, time());
        return $this->handleSource();
    }

    /**
     * Starts the process and combines all files.
     *
     * @return bool
     */
    public function handleSource()
    {
        if (!$this->_source || !$this->_target || !$this->_targetDir) {
            return false;
        }

        $content = '';

        if (is_dir($this->_source)) {
            foreach (glob($this->_source . '/*' . $this->_extension) as $filename) {
                if (file_exists($filename)) {
                    $files[] = $filename;
                }
            }

            $content = $this->readFiles($files, "\n\n");
        }

        if ($this->_targetCallback) {
            $content = call_user_func($this->_targetCallback, $content);
        }

        $this->createFiles($this->_target, $content);

        return true;
    }

    /**
     * Adds the target file to the WordPress enqueue.
     *
     * @param string $name     Enqueue name.
     * @param array  $supports Name of other enqueues to be loaded before.
     * @param string $version  The version of the file. With '' a uniqid() is generated and the file is not cached by the browser.
     *
     * @return void
     */
    public function enqueue(string $name = 'combined', array $supports = [], string $version = '')
    {
        if (trim($version) === '') {
            $version = uniqid();
        }

        if ($this->_extension === '.js') {
            wp_enqueue_script($name, $this->toUrl($this->_target), $supports, $version, true);
        }

        if (in_array($this->_extension, ['.css', '.less', '.scss'])) {
            wp_enqueue_style($name, $this->toUrl($this->_target), $supports, $version, false);
        }
    }

    /**
     * Reads one or more files.
     *
     * @param string|string[] $path The files to be read.
     * @param string          $sep  Specifies how the individual contents are separated from each other.
     *
     * @return string
     */
    public function readFiles($path, string $sep = "\n\n")
    {
        $results = [];

        if (is_array($path)) {
            foreach ($path as $p) {
                if (file_exists($p)) {
                    $results[] = file_get_contents($p);
                }
            }

            return implode($sep, $results);
        }

        if (file_exists($path)) {
            $results[] = file_get_contents($path);
        }

        return implode($sep, $results);
    }

    /**
     * Creates one or more files with content.
     *
     * @param string|string[] $path    The files to be created.
     * @param string          $content The content of the files.
     *
     * @return void
     */
    public function createFiles($path, string $content = '')
    {
        if (is_array($path)) {
            foreach ($path as $p) {
                file_put_contents($p, $content);
            }

            return;
        }

        file_put_contents($path, $content);

        return;
    }

    /**
     * Deletes one or more files.
     *
     * @param string|string[] $path The files to be deleted.
     *
     * @return void
     */
    public function deleteFiles($path)
    {
        $results = [];

        if (is_array($path)) {
            foreach ($path as $p) {
                if (file_exists($p)) {
                    $results[] = [
                        'file' => $p,
                        'deleted' => unlink($p),
                    ];
                }
            }

            return $results;
        }

        if (file_exists($path)) {
            $results[] = [
                'file' => $p,
                'deleted' => unlink($path),
            ];
        }

        return $results;
    }

    /**
     * Converts a path into a URL.
     *
     * @param string $file The path of the file.
     * @param string $protocol The protocol of the URL.
     *
     * @return string
     */
    public function toUrl(string $file, string $protocol = 'https://')
    {
        return esc_url($protocol . $_SERVER['HTTP_HOST'] . str_replace($_SERVER['DOCUMENT_ROOT'], '/', $file));
    }
}
