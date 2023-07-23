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
use Joomla\Component\MigrateToJoomla\Administrator\Helper\MainHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class DownloadHelper
{
    public  static $downloadmanager;
    /**
     * Method to check connection with respective method
     * 
     * @param array form data
     * @return boolean True on success
     * 
     * @since 1.0
     */
    public static function testConnection($data = [])
    {
        $method = $data['mediaoptions'];

        if ($method == 1) {
            // Http
            HttpHelper::testConnection($data['livewebsiteurl']);
        } else if ($method == 2) {
            // File system
            FilesystemHelper::testConnection($data['basedir']);
        }else if($method ==3){
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
    public static function download($data = [])
    {
        $method = $data['mediaoptions'];
        $source='';

        switch ($method) {
            case 2:
                DownloadHelper::$downloadmanager = new FilesystemHelper;
                $source = MainHelper::addTrailingSlashit($data['basedir']) . 'wp-content\uploads\\';
                break;
            case 3:
                DownloadHelper::$downloadmanager = new FtpHelper;
                break;
            case 1:
            default:
                DownloadHelper::$downloadmanager = new HttpHelper;
                $source = MainHelper::addTrailingSlashit($data['livewebsiteurl']) . 'wp-content\uploads\\';
                break;
        }

        $destination = MainHelper::addTrailingSlashit(JPATH_ROOT) . 'images\\';
        $app   = Factory::getApplication();

        try {
            DownloadHelper::copy("https://kaushik.sfclient.co.uk/wp-content/uploads/2023/07/kaushik12.jpeg", $destination.'kaushik12.jpeg');
            // HttpHelper::getContent($source);
            
            $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_DOWNLOAD_MEDIA_SUCCESSFULLY'), 'success');
        } catch (\Throwable $th) {
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
    public static function copy($source, $destination)
    {
        if (DownloadHelper::isdir($source)) {
            // Directory
            return DownloadHelper::copydir($source, $destination);
        } else if (file_exists($source)) {
            // File
            return DownloadHelper::copyfile($source, $destination);
        }
    }

    /**
     * Method to list directory content
     * 
     * @param string $directory path
     * @return array List of files and directory
     * 
     * @since  1.0
     */
    public static function listDirectory($directory = '')
    {
        return DownloadHelper::$downloadmanager::listDirectory($directory);
    }

    /**
     * Method to path is directory or not
     * 
     * @param string $path 
     * @param bool True on success
     * 
     * @since  1.0
     */
    public static function isDir($path = '')
    {
        return DownloadHelper::$downloadmanager::isDir($path);
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
    public static function copyFile($source, $destination)
    {
        $response = false;
        if (file_exists($destination) && (filesize($destination) > 0)) {
            // file Already downloaded 
            return true;
        }

        $filecontent = DownloadHelper::$downloadmanager::getContent($source);
        if ($filecontent !== false) {
            $response = (file_put_contents($destination, $filecontent) !== false);
        }
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
    public static function copyDir($source, $destination)
    {
        $response = true;
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true); // Create the directory if not exist
        }
        $files = DownloadHelper::listDirectory($source);

        if (is_array($files) || is_object($files)) {
            foreach ($files as $file) {
                if (preg_match('/^\.+$/', $file)) { // Skip . and ..
                    continue;
                }
                $source_filename = MainHelper::addTrailingSlashit($source) . $file;
                $dest_filename = MainHelper::addTrailingSlashit($destination) . $file;
                $response = DownloadHelper::copy($source_filename, $dest_filename);
            }
        }
        return $response;
    }
}
