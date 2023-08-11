<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Controller;

use Joomla\Database\DatabaseDriver;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Versioning\VersionableControllerTrait;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\PathHelper;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\HttpHelper;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\FilesystemHelper;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\FtpHelper;
use Joomla\CMS\Filesystem\path;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Migrate controller class.
 *
 * @since  1.0
 */
class MigrateController extends FormController
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
    protected $text_prefix = 'COM_MIGRATETOJOOMLA_Migrate';

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
        $app->setUserState('com_migratetojoomla.information', $data);

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

        if (self::setdatabase($this, $data)) {
            $app->enqueueMessage(Text::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_SUCCESSFULLY'), 'success');
        } else {
            $app->enqueueMessage(Text::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_UNSUCCESSFULLY'), 'error');
        }

        // Store data in session
        $app->setUserState('com_migratetojoomla.information', $data);

        // redirect in all case
        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla', false));
    }

    /**
     * Method to set database $db if it is not set
     * 
     * @param array form data
     * @return boolean True on success
     * 
     * @since 1.0
     */
    public static function setdatabase($instance, $data = [])
    {
        if (\is_resource($instance->db)) {
            return true;
        }

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
            $instance->db = $db;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /** Method to import database  
     * 
     * @since 1.0
     */
    public function importDatabase()
    {
        $this->checkToken();
        $data  = $this->input->post->get('jform', array(), 'array');
        $this->checkDatabaseConnection();
        $this->importUsers($data);
        // redirect in all case
        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla', false));
    }

    /** 
     * Method to import user table
     * 
     * @since 1.0
     */
    public function importUsers($data = [])
    {
        $app   = Factory::getApplication();
        // Get the database connection
        $db = $this->db;
        $jdb = Factory::getDbo();
        // Specify the table name
        $tableName = rtrim($data['dbtableprefix'], '_') . '_users';
        $config['dbo'] = $this->db;
        $tablePrefix = Factory::getConfig()->get('dbprefix');
        // $query = $db->getQuery(true)
        //     ->select('*')
        //     ->from($db->quoteName($tableName));

        // $db->setQuery($query);
        // $results = $db->loadAssocList();

        // $data = array();

        // $app->enqueueMessage('user start', 'success');
        $query = $jdb->getQuery(true)
            ->select('*')
            ->from($jdb->quoteName($tablePrefix . '_users'));
        $results2 = $jdb->loadAssocList();

        echo $tablePrefix;
        foreach ($results2 as $row) {
            echo "<pre>";
            echo var_dump($row);
            echo "<br/>";
        }
        die;
        $count = 1;

        foreach ($results as $row) {
            // $data = array(
            //     $jdb->quoteName('id')             => $row['id'],
            //     $jdb->quoteName('name')           => $row['display_name'],
            //     $jdb->quoteName('username')       => $row['user_login'],
            //     $jdb->quoteName('email')          => $row['user_email'],
            //     $jdb->quoteName('registeredDate') => $row['user_registered'],
            //     $jdb->quoteName('activation')     => $row['user_activation_key'],
            //     $jdb->quoteName('requireReset')   => 1
            // );
            // print_r($row);
            // echo '<pre>';
            // var_dump($row);
            // die;
            $c = array(
                $row['ID'], $row['display_name'], $row['user_login'],
                $row['user_email'], $row['user_registered'], $row['user_activation_key'], 1
            );
            $dateTimeObject = new \DateTime($row['user_registered']);
            // echo "<pre>";
            // echo var_dump($row['user_registered']);
            // echo var_dump($dateTimeObject);
            // echo var_dump(Factory::getDate($row['user_registered'])->toSql());
            // echo var_dump($row);
            die;
            $dateTimeObject = new \DateTime($row['user_registered']);
            $query = $jdb->getQuery(true)
                ->clear()
                ->insert($jdb->quoteName($tablePrefix . 'users'))
                ->columns(
                    $jdb->quoteName('id'),
                    $jdb->quoteName('name'),
                    $jdb->quoteName('username'),
                    $jdb->quoteName('email'),
                    $jdb->quoteName('registerDate'),
                    $jdb->quoteName('activation'),
                    $jdb->quoteName('requireReset')
                )
                ->values(
                    $row['ID'],
                    $row['display_name'],
                    $row['user_login'],
                    $row['user_email'],
                    $row['user_registered'],
                    $row['user_activation_key'],
                    1
                );
            // echo $query;
            // die;
            $jdb->setQuery($query);
            $jdb->execute();
            $app->enqueueMessage(strval($count), 'warning');
            $count = $count + 1;
        }
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

        $source = PathHelper::addTrailingSlashit($source) . 'wp-content\uploads';
        $destination = PathHelper::addTrailingSlashit(JPATH_ROOT) . 'images';

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
                $source_filename = PathHelper::addTrailingSlashit($source) . $file;
                $dest_filename = PathHelper::addTrailingSlashit($destination) . $file;
                $response = $this->copy($source_filename, $dest_filename);
            }
        }
        return $response;
    }
}
