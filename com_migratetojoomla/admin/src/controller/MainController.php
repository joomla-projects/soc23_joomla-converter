<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Versioning\VersionableControllerTrait;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\MainHelper;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\HttpHelper;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\FilesystemHelper;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\FtpHelper;
use Joomla\CMS\Filesystem\path;
use Joomla\Database\DatabaseDriver;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * main controller class.
 *
 * @since  1.0
 */
class MainController extends FormController
{
    use VersionableControllerTrait;

    /**
     * @var object  Media Download object
     * 
     * @since 1.0
     */
    public  $mediaDownloadManager;

    /**
     * @var object Database parameters object
     * 
     * @since 1.0
     */
    public $options;

    /**
     * @var object Database object
     * 
     * @since 1.0
     */
    public $db;

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  
     */
    protected $text_prefix = 'COM_MIGRATETOJOOMLA_MAIN';

    /**
     * Method to check media connection.
     * 
     * @since 1.0
     */
    public function checkMediaConnection()
    {
        $this->checkToken();

        $app   = Factory::getApplication();

        $data  = $this->input->post->get('jform', array(), 'array');

        $this->testMediaConnection($data);
        // Store data in session
        $app->setUserState('com_migratetojoomla.main', $data);

        // redirect in all case
        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla', false));
    }

    /**
     * Controller funciton to check Database connection
     * 
     * @since 1.0
     */
    public function checkDatabaseConnection()
    {
        $this->checkToken();

        $app   = Factory::getApplication();
        $data  = $this->input->post->get('jform', array(), 'array');

        $options = [
            'driver'    => $data['dbdriver'],
            'host'      => $data['dbhostname'] . ':' . $data['dbport'],
            'user'      => $data['dbusername'],
            'password'  => $data['dbpassword'],
            'database'  => $data['dbname'],
            'prefix'    => $data['dbtableprefix'],
        ];

        try {
            $db = DatabaseDriver::getInstance($options);
            $db->getVersion();
            $app->enqueueMessage('Database connection succesfully', 'success');
        } catch (\Exception $e) {
            $app->enqueueMessage('Cannot connect to database, verify that you specified the correct database details', 'error');
        }

        // Store data in session
        $app->setUserState('com_migratetojoomla.main', $data);

        // redirect in all case
        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla', false));
    }

    /**
     * Method to Download 
     * 
     * @since  1.0
     */
    public function download()
    {
        $this->checkMediaConnection();
        $app   = Factory::getApplication();
        try {
            $data  = $this->input->post->get('jform', array(), 'array');
            $load = new self();
            $load->downloadMedia($data);

            $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_DOWNLOAD_MEDIA_SUCCESSFULLY'), 'success');
        } catch (\RuntimeException $th) {
            $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_DOWNLOAD__MEDIA_UNSUCCESSFULLY'), 'danger');
        }
        // redirect in all case
        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla', false));
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

        if ($method == "http") {
            // Http
            HttpHelper::testConnection($data['livewebsiteurl']);
        } else if ($method == "fs") {
            // File system
            FilesystemHelper::testConnection($data['basedir']);
        } else if ($method == "ftp") {
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
    public  function downloadMedia($data = [])
    {
        $app   = Factory::getApplication();
        $method = $data['mediaoptions'];
        $source = '';

        switch ($method) {
            case "fs":
                $source = $data['basedir'];
                break;
            case 'ftp':
                $this->mediaDownloadManager = new FtpHelper($data);
                $response = $this->mediaDownloadManager->login();
                $source = $data['ftpbasedir'];
                break;
            case "http":
            default:
                $this->mediaDownloadManager = new HttpHelper($data['livewebsiteurl']);
                $source = $data['livewebsiteurl'];
                break;
        }

        $source = MainHelper::addTrailingSlashit($source) . 'wp-content\uploads';
        $destination = MainHelper::addTrailingSlashit(JPATH_ROOT) . 'images';

        try {
            if ($method == "fs") {
                Folder::copy($source, $destination, '', true, false);
            } else {
                $this->copy($source, $destination);
            }
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
        $destination = path::clean($destination);
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
}
