<?php

namespace Linguise\Vendor\Linguise\Script\Core;

defined('LINGUISE_SCRIPT_TRANSLATION') or die();

class Updater
{
    /**
     * @var null|Updater
     */
    private static $_instance = null;

    protected function __construct() {}

    /**
     * Retrieve singleton instance
     *
     * @return Updater|null
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new Updater();
        }

        return self::$_instance;
    }

    /**
     * @return string|null
     */
    public function update()
    {
        $this->log('Start updating');

        if (!$this->isZipArchiveAvailable()) {
            $this->log('Php zip extension missing');
            return $this->dieOrReturn('Update failed');
        }

        $this->prepareExecution();

        $content = $this->download($this->getUpdateUrl());

        if (!$content) {
            $this->log('Failed load update information');
            return $this->dieOrReturn('Update failed');
        }

        $result = json_decode($content);
        if (!$result) {
            $this->log('Failed decode update information');
            return $this->dieOrReturn('Update failed');
        }

        if (version_compare($result->version, LINGUISE_SCRIPT_TRANSLATION_VERSION, '<=')) {
            return $this->dieOrReturn('No update available');
        }

        $tmp_folder = $this->getTmpFolder();
        if (!file_exists($tmp_folder)) {
            mkdir($tmp_folder);
        }

        $file_content = $this->download($result->location);

        if (!$file_content) {
            $this->log('Failed load update information');
            return $this->dieOrReturn('Update failed');
        }

        $update_file = $tmp_folder . DIRECTORY_SEPARATOR . 'update.zip';
        file_put_contents($update_file, $file_content);
        $md5_sum = md5_file($update_file);

        if ($md5_sum !== $result->md5) {
            $this->log('File verification failed');
            unlink($update_file);
            return $this->dieOrReturn('Update failed');
        }

        $zip = $this->createZipArchive();
        if ($this->openZipArchive($zip, $update_file) !== true) {
            $this->log('File extraction failed');
            unlink($update_file);
            return $this->dieOrReturn('Update failed');
        }

        $zip_folder = $tmp_folder . DIRECTORY_SEPARATOR . 'update';
        if (!$this->extractZipArchive($zip, $zip_folder)) {
            $this->log('File extraction failed');
            unlink($update_file);
            return $this->dieOrReturn('Update failed');
        }
        $this->closeZipArchive($zip);

        unlink($update_file);

        $base_folder = $this->getBaseFolder();
        $backup_folder = $this->getBackupFolder($base_folder);

        $data_folder_name = $this->getDataFolderName();

        if (!rename($base_folder, $backup_folder)) {
            $this->log('File extraction failed');
            $this->rmdir($zip_folder);
            return $this->dieOrReturn('Update failed');
        }

        if (!rename($this->getExtractedBaseFolder($backup_folder, $data_folder_name), $base_folder)) {
            $this->log('Folder rename failed');
            $this->rmdir($zip_folder);
            return $this->dieOrReturn('Update failed');
        }

        if (!rename($backup_folder . DIRECTORY_SEPARATOR . 'Configuration.php', $base_folder . DIRECTORY_SEPARATOR . 'Configuration.php')) {
            $this->log('Failed moving configuration file');
            $this->rmdir($base_folder);
            rename($backup_folder, $base_folder);
            return $this->dieOrReturn('Update failed');
        }

        if (!rename($backup_folder . DIRECTORY_SEPARATOR . $data_folder_name, $base_folder . DIRECTORY_SEPARATOR . $data_folder_name)) {
            $this->log('Failed moving data directory');
            $this->rmdir($base_folder);
            rename($backup_folder, $base_folder);
            return $this->dieOrReturn('Update failed');
        }

        if (class_exists('Linguise\Core\AfterUpdate') && method_exists('Linguise\Core\AfterUpdate', 'afterUpdateRun')) {
            AfterUpdate::afterUpdateRun($base_folder); // @codeCoverageIgnore
        }

        $this->rmdir($backup_folder);

        $this->log('Update done');
        return $this->dieOrReturn('Update succeed');
    }

    protected function log($message)
    {
        Debug::log($message); // @codeCoverageIgnore
    }

    protected function isZipArchiveAvailable()
    {
        return class_exists('\ZipArchive', false); // @codeCoverageIgnore
    }

    protected function prepareExecution()
    {
        @set_time_limit(0);
        ignore_user_abort(true);
    }

    protected function getUpdateUrl()
    {
        return Configuration::getInstance()->get('update_url');
    }

    protected function download($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->isHttpsUrl($url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            if (Configuration::getInstance()->get('dl_certificates') === true) {
                curl_setopt($ch, CURLOPT_CAINFO, Certificates::getInstance()->getPath());
            }
        }
        $content = curl_exec($ch);
        if (PHP_VERSION_ID < 80000) {
            // @codeCoverageIgnoreStart
            curl_close($ch); // Since, PHP 8+ this thing actually does not do anything (deprecated in PHP 8.5)
            // @codeCoverageIgnoreEnd
        }

        return $content;
    }

    protected function isHttpsUrl($url)
    {
        return strpos((string) $url, 'https') === 0;
    }

    protected function getTmpFolder()
    {
        return Configuration::getInstance()->get('data_dir') . DIRECTORY_SEPARATOR . 'tmp';
    }

    protected function getBaseFolder()
    {
        return realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
    }

    protected function getBackupFolder($baseFolder)
    {
        return $baseFolder . uniqid('_update_');
    }

    protected function getDataFolderName()
    {
        $data_dir_parts = explode(DIRECTORY_SEPARATOR, Configuration::getInstance()->get('data_dir'));

        return $data_dir_parts[count($data_dir_parts) - 1];
    }

    protected function getExtractedBaseFolder($backupFolder, $dataFolderName)
    {
        return $backupFolder . DIRECTORY_SEPARATOR . $dataFolderName . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . 'linguise';
    }

    protected function createZipArchive()
    {
        return new \ZipArchive(); // @codeCoverageIgnore
    }

    protected function openZipArchive($zip, $file)
    {
        return $zip->open($file);
    }

    protected function extractZipArchive($zip, $destination)
    {
        return $zip->extractTo($destination);
    }

    protected function closeZipArchive($zip)
    {
        $zip->close();
    }

    protected function dieOrReturn($message)
    {
        if (defined('LINGUISE_SCRIPT_TESTING') && LINGUISE_SCRIPT_TESTING) {
            return $message;
        }

        die($message); // @codeCoverageIgnore
    }

    protected function rmdir($directory)
    {
        if (!file_exists($directory)) {
            return true; // @codeCoverageIgnore
        }

        $files = array_diff(scandir($directory), array('.', '..'));
        foreach ($files as $file) {
            (is_dir($directory . DIRECTORY_SEPARATOR . $file)) ? $this->rmdir($directory . DIRECTORY_SEPARATOR . $file) : unlink($directory . DIRECTORY_SEPARATOR . $file);
        }
        return rmdir($directory);
    }
}
