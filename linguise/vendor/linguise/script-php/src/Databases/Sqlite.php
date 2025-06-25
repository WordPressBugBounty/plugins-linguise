<?php

namespace Linguise\Vendor\Linguise\Script\Core\Databases;

use Linguise\Vendor\Linguise\Script\Core\Configuration;
use Linguise\Vendor\Linguise\Script\Core\Request;

defined('LINGUISE_SCRIPT_TRANSLATION') or die();

class Sqlite
{
    /**
     * @var null|Sqlite
     */
    private static $_instance = null;

    /**
     * @var string Urls table name
     */
    protected $_database_table_urls;

    /**
     * @var \SQLite3
     */
    protected $_database;

    /**
     * @var \SQLite3|null
     */
    protected $_root_database;

    /**
     * Database connection status
     *
     * @var boolean
     */
    protected $_ready = false;

    /**
     * Retrieve singleton instance
     *
     * @return Sqlite
     */
    public static function getInstance() {

        if(is_null(self::$_instance)) {
            self::$_instance = new Sqlite();
        }

        return self::$_instance;
    }

    private function __construct()
    {
    }

    /**
     * Check if the database is connected
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->_ready;
    }

    /**
     * Connect to the mysql database
     *
     * @param $config Array configuration
     *
     * @return boolean
     */
    public function connect()
    {
        if ($this->_ready) {
            return true;
        }

        if (defined('LINGUISE_BASE_DIR')) {
            $root_db = LINGUISE_BASE_DIR . '.databases' . DIRECTORY_SEPARATOR . 'linguise-main.db';
            if (!file_exists($root_db)) {
                touch($root_db);
                chmod($root_db, 0766);
            }
            $this->_root_database = new \SQLite3($root_db);
        } else {
            $this->_root_database = null;
        }

        $database_exists = true;
        $database_root_dir = Configuration::getInstance()->get('data_dir') . DIRECTORY_SEPARATOR . 'database';
        $database_path = $database_root_dir . DIRECTORY_SEPARATOR . 'linguise.sqlite';
        if (!file_exists($database_path)) {
            $database_exists = false;
            touch($database_path);
            chmod($database_path, 0766);
        }
        $this->_database = new \SQLite3($database_path);
        $this->_database_table_urls = 'urls';

        //$this->_database->set_charset("utf8");

        //$existing_tables = array();

        if (!$database_exists) {
            $this->_database->exec('
                    CREATE TABLE '. $this->_database_table_urls.' (
                      `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                      `language` STRING NOT NULL,
                      `source` BINARY NOT NULL,
                      `translation` BINARY NOT NULL,
                      `hash_source` STRING NOT NULL,
                      `hash_translation` STRING NOT NULL
                    );
                ');
            $this->_database->exec('CREATE UNIQUE INDEX source ON '. $this->_database_table_urls.' (hash_source, language)');
            $this->_database->exec('CREATE UNIQUE INDEX translation ON '. $this->_database_table_urls.' (hash_translation, language)');
            // todo: store creation date
            // todo: number of url usage
        }

        $this->_database->busyTimeout(3000);
        $this->_database->exec('PRAGMA journal_mode = wal;');
        $this->_ready = true;

        return true;
    }

    public function getSourceUrl($url) {
        $smt = $this->_database->prepare('SELECT * from ' . $this->_database_table_urls . ' WHERE hash_translation=:hash_translation AND language=:language LIMIT 0, 1');
        if ($smt === false) {
            return false;
        }
        $smt->bindValue(':hash_translation', md5($url), SQLITE3_TEXT);
        $smt->bindValue(':language', Request::getInstance()->getLanguage(), SQLITE3_TEXT);

        $result = $smt->execute()->fetchArray();
        
        // Ensure that $result is of array type
        if (empty($result) || !is_array($result)) {
            return false;
        }

        return $result['source'];
    }

    public function getTranslatedUrl($url) {
        $smt = $this->_database->prepare('SELECT * from ' . $this->_database_table_urls . ' WHERE hash_source=:hash_source AND language=:language LIMIT 0, 1');
        $smt->bindValue(':hash_source', md5($url), SQLITE3_TEXT);
        $smt->bindValue(':language', Request::getInstance()->getLanguage(), SQLITE3_TEXT);

        try {
            $result = $smt->execute()->fetchArray();
        } catch (\Exception $e) {
            return false;
        }

        // Ensure that $result is of array type
        if (empty($result) || !is_array($result)) {
            return false;
        }

        return $result[0]['translation'];
    }

    public function saveUrls($urls)
    {
        $query = 'INSERT OR REPLACE INTO ' . $this->_database_table_urls . ' (language, source, translation, hash_source, hash_translation) VALUES ';

        foreach ($urls as $translation => $source) {
            $smt = $this->_database->prepare($query . '(:language, :source, :translation, :hash_source, :hash_translation)');
            $smt->bindValue(':language', Request::getInstance()->getLanguage(), SQLITE3_TEXT);
            $smt->bindValue(':source', $source, SQLITE3_BLOB);
            $smt->bindValue(':translation', $translation, SQLITE3_BLOB);
            $smt->bindValue(':hash_source', md5($source), SQLITE3_TEXT);
            $smt->bindValue(':hash_translation', md5($translation), SQLITE3_TEXT);

            try {
                $smt->execute();
            } catch (\Exception $e) {}
        }
    }

    public function removeUrls($urls)
    {
        $query = 'DELETE FROM ' . $this->_database_table_urls . ' WHERE (hash_source) IN ';

        $elements = array();
        foreach ($urls as $source) {
            $elements[] = '"'.md5($source).'"';
        }
        $query .= '(' . implode(',', $elements) . ') ';

        $smt = $this->_database->prepare($query . ' AND language=:language');
        $smt->bindValue(':language', Request::getInstance()->getLanguage(), SQLITE3_TEXT);
        try {
            $smt->execute();
        } catch (\Exception $e) {}
    }

    public function installOptions() {
        if (empty($this->_root_database)) {
            return false;
        }

        $this->_root_database->exec('
            CREATE TABLE IF NOT EXISTS options (
              `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
              `name` STRING NOT NULL,
              `value` BLOB NOT NULL
            );
        ');
        $this->_root_database->exec('CREATE UNIQUE INDEX IF NOT EXISTS `name` ON options (`name`)');
    }

    public function retrieveOtherParam($option_name) {
        if (empty($this->_root_database)) {
            return false;
        }

        $smt = $this->_root_database->prepare('SELECT value from options WHERE name=:name LIMIT 0, 1');
        $smt->bindValue(':name', $option_name, SQLITE3_TEXT);
        $result = $smt->execute()->fetchArray();

        // Ensure that $result is of array type
        if (empty($result) || !is_array($result)) {
            return false;
        }

        // Unserialize
        $value = @unserialize($result['value']);
        if (empty($value)) {
            return null;
        }
        return $value;
    }

    public function saveOtherParam($option_name, $value) {
        if (!$this->_root_database) {
            return false;
        }

        $smt = $this->_root_database->prepare('INSERT OR REPLACE INTO options (name, value) VALUES (:name, :value)');
        $smt->bindValue(':name', $option_name, SQLITE3_TEXT);
        $smt->bindValue(':value', serialize($value), SQLITE3_BLOB);
        try {
            $smt->execute();
        } catch (\Exception $e) {}
    }

    public function close() {
        $this->_database->close();
    }
}
