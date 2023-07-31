<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\HttpHelper;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\FilesystemHelper;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\FtpHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class MainHelper
{
    /**
     * @var object  Media Download object
     * 
     * @since 1.0
     */
    public  $mediaDownloadManager;

    /**
     * @var array Form data
     * 
     * @since 1.0
     */
    public $data;

    /**
     * Class constructor
     * 
     * @param array ftpparameters
     * 
     * @since 1.0
     */
    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * Method to check connection with respective method
     * 
     * @param array form data
     * @return boolean True on success
     * 
     * @since 1.0
     */
    public static function testMediaConnection($data = [])
    {
        $method = $data['mediaoptions'];

        if ($method == 1) {
            // Http
            HttpHelper::testConnection($data['livewebsiteurl']);
        } else if ($method == 2) {
            // File system
            FilesystemHelper::testConnection($data['basedir']);
        } else if ($method == 3) {
            FtpHelper::testConnection($data);
        }
    }

    /**
     * Method to Download 
     * 
     * @param array form data
     * 
     * @since  1.0
     */
    public  function downloadMedia()
    {
        $app   = Factory::getApplication();
        $method = $this->data['mediaoptions'];
        $source = '';

        switch ($method) {
            case 2:
                $this->mediaDownloadManager = new FilesystemHelper;
                $source = $this->data['basedir'];
                break;
            case 3:
                $this->mediaDownloadManager = new FtpHelper($this->data);
                $response = $this->mediaDownloadManager->login();
                $source = $this->data['ftpbasedir'];
                break;
            case 1:
            default:
                $this->mediaDownloadManager = new HttpHelper($this->data['livewebsiteurl']);
                $source = $this->data['livewebsiteurl'];
                break;
        }

        $source = MainHelper::addTrailingSlashit($source) . 'wp-content/uploads/';
        $destination = MainHelper::addTrailingSlashit(JPATH_ROOT) . 'images\\';

        try {
            $this->copy($source, $destination);
            $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_DOWNLOAD_MEDIA_SUCCESSFULLY'), 'success');
        } catch (\RuntimeException $th) {
            $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_DOWNLOAD_MEDIA_UNSUCCESSFULLY'), 'danger');
        }
    }

    /**
     * Method to copy a file or a directory
     *
     * @param string $source Original file or directory name
     * @param string $destination Destination file or directory name
     * @param bool $recursive Recursive copy?
     * @return bool File copied or not
     * 
     * @since  1.0
     */
    public function copy($source, $destination)
    {
        if ($this->mediaDownloadManager->isDir($source)) {
            // Directory
            return $this->copyDir($source, $destination);
        } else {
            // File
            return $this->copyFile($source, $destination);
        }
    }

    /**
     * Method to copy file
     * 
     * @param string $source source path
     * @param string $destination destination path
     * 
     * @return boolean True on success
     * 
     * @since  1.0
     */
    public function copyFile($source, $destination)
    {
        $response = false;
        if (file_exists($destination) && (filesize($destination) > 0)) {
            // file Already downloaded 
            return true;
        }

        $response = $this->mediaDownloadManager->getContent($source, $destination);
        return $response;
    }

    /**
     * Method to make directory and copy it's content
     * 
     * @param string $source Source path
     * @param string $source Destination path
     * 
     * @return boolean True on Success
     * 
     * @since  1.0
     */
    public function copyDir($source, $destination)
    {
        $response = true;
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true); // Create the directory if not exist
        }
        $files = $this->mediaDownloadManager->listDirectory($source);

        if (is_array($files) || is_object($files)) {
            foreach ($files as $file) {
                if (preg_match('/^\.+$/', $file)) { // Skip . and ..
                    continue;
                }
                $source_filename = MainHelper::addTrailingSlashit($source) . $file;
                $dest_filename = MainHelper::addTrailingSlashit($destination) . $file;
                $response = $this->copy($source_filename, $dest_filename);
            }
        }
        return $response;
    }

    /**
     * Method to append a trailing slash.
     * 
     * @param string file or directory path
     * @return string file or dirctory path
     * 
     * @since 1.0
     */
    public static function addTrailingSlashit($path)
    {
        return MainHelper::untrailingslashit($path) . '/';
    }

    /**
     * Method to remove trailing forward slashes and backslashes if they exist.
     * 
     * @param string file or directory path
     * @return string file or dirctory path
     * 
     * @since 1.0
     */
    public static function unTrailingSlashit($path)
    {
        $path = rtrim($path, '/\\');
        return $path;
    }

    /**
     * Method to remove start and trailing forward slashes and backslashes if they exist.
     * 
     * @param string file or directory path
     * @return string file or dirctory path
     * 
     * @since 1.0
     */
    public static function unSlashit($path)
    {
        $path  = trim($path, '/\\');
        return $path;
    }
}
