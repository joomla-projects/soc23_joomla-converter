<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla.mediadownload
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\MigrateToJoomla\MediaDownload\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\PathHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Factory;
use Joomla\Event\SubscriberInterface;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\LogHelper;

require_once 'filesystem.php';
require_once 'ftp.php';
require_once 'http.php';

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

final class MediaDownload extends CMSPlugin implements SubscriberInterface
{
    /**
     * @var object  Media Download object
     * 
     * @since 1.0
     */
    public  $mediaDownloadManager;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   4.3.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'migratetojoomla_downloadmedia' => 'downloadMedia',
            'migratetojoomla_testmediaconnection' => 'testMediaConnection'
        ];
    }

    /**
     * Method to check connection with respective method
     * 
     * @param array form data
     * @return boolean True on success
     * 
     * @since 1.0
     */
    public static function testMediaConnection()
    {
        $data = Factory::getApplication()->getUserState('com_migratetojoomla.information', []);

        $method = $data['mediaoptions'];

        $response = false;
        if ($method == "http") {
            // Http
            $response = HttpDownload::testConnection($data['livewebsiteurl']);
        } else if ($method == "fs") {
            // File system
            $response = FilesystemDownload::testConnection($data['basedir']);
        } else if ($method == "ftp") {
            $response = FtpDownload::testConnection($data);
        }

        $session = Factory::getSession();
        $session->set('mediaconnectionresult', $response);
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
        $data = $app->getUserState('com_migratetojoomla.information', []);
        $method = $data['mediaoptions'];
        $source = '';

        switch ($method) {
            case "fs":
                $source = $data['basedir'];
                break;
            case 'ftp':
                $this->mediaDownloadManager = new FtpDownload($data);
                $response = $this->mediaDownloadManager->login();
                $source = $data['ftpbasedir'];
                break;
            case "http":
            default:
                $this->mediaDownloadManager = new HttpDownload($data['livewebsiteurl']);
                $source = $data['livewebsiteurl'];
                break;
        }

        $source = PathHelper::addTrailingSlashit($source) . 'wp-content\uploads';
        $destination = PathHelper::addTrailingSlashit(JPATH_ROOT) . 'images';

        try {
            if ($method == "fs") {
                Folder::copy($source, $destination, '', true, false);
            } else {
                $this->copy($source, $destination);
            }
            LogHelper::writeLog(TEXT::_('COM_MIGRATETOJOOMLA_DOWNLOAD_MEDIA_SUCCESSFULLY'), 'success');
        } catch (\RuntimeException $th) {
            LogHelper::writeLog(TEXT::_('COM_MIGRATETOJOOMLA_DOWNLOAD_MEDIA_UNSUCCESSFULLY'), 'error');
            LogHelper::writeLog($th, 'normal');
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
        $destination = PathHelper::clean($destination);
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
                $source_filename = PathHelper::addTrailingSlashit($source) . $file;
                $dest_filename = PathHelper::addTrailingSlashit($destination) . $file;
                $response = $this->copy($source_filename, $dest_filename);
            }
        }
        return $response;
    }
}
