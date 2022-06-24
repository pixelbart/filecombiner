<?php

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
     * @param string $source    Der Ordner, in dem alle Dateien sind.
     * @param string $target    Die Zieldatei, in der alles kombiniert werden soll.
     * @param string $extension Die Dateiendung die beachtet werden soll.
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
     * Eine Funktion, oder Methode, die zum Schluß auf den Inhalt der Zieldatei angewendet werden soll.
     * Ist nützlich, falls man die Datei minifizieren möchte.
     *
     * @param string $callback Die Funktion/Methode die ausgeführt werden soll.
     *
     * @return void
     */
    public function setTargetCallback(string $callback)
    {
        $this->_targetCallback = $callback;
    }

    /**
     * Prüft das Quellverzeichnis und löst die Verarbeitung aus,
     * falls sich eine Datei verändert hat.
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
     * Führt ein Update der Dateien durch.
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
     * Startet den Vorgang und kombiniert alle Dateien.
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
     * Fügt die Zieldatei der WordPress-Enqueue zu.
     *
     * @param string $name     Name der Enqueue.
     * @param array  $supports Name anderer Enqueues, die vorher geladen werden sollen.
     * @param string $version  Die Version der Datei. Mit '' wird eine uniqid() generiert und die Datei nicht vom Browser gecached.
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
     * Liest eine Datei oder mehrere Dateien.
     *
     * @param string|string[] $path Die Dateien die gelesen werden sollen.
     * @param string          $sep  Gibt an, wie die einzelnen Inhalt voneinander getrennt werden.
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
     * Erstellt eine Datei oder mehrere Dateien mit Inhalt.
     *
     * @param string|string[] $path    Die Dateien die erstellt werden sollen.
     * @param string          $content Der Inhalt der Dateien.
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
     * Löscht eine Datei oder mehrere Dateien.
     *
     * @param string|string[] $path Die Dateien die gelöscht werden sollen.
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
     * Wandelt einen Pfad in eine URL um.
     *
     * @param string $file Der Pfad der Datei.
     * @param string $protocol Das Protokoll der URL.
     *
     * @return string
     */
    public function toUrl(string $file, string $protocol = 'https://')
    {
        return esc_url($protocol . $_SERVER['HTTP_HOST'] . str_replace($_SERVER['DOCUMENT_ROOT'], '/', $file));
    }
}
