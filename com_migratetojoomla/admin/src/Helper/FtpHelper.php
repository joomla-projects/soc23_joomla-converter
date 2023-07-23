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
use Joomla\CMS\Client\FtpClient;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class FtpHelper
{

    public static $options = array();
    public static $ftp = false;
    /**
     * Method to check Enter base url connection
     * 
     * @param string Base url of live website
     * @return boolean True on success
     * 
     * @since 1.0
     */
    public static function testConnection($data = [])
    {
        FtpHelper::$options['host'] = $data['ftphost'];
        FtpHelper::$options['port'] = $data['ftpport'];
        FtpHelper::$options['username'] = $data['ftpusername'];
        FtpHelper::$options['password'] = $data['ftppassword'];
        FtpHelper::$options['basedir'] = $data['ftpbasefir'];

        $response = FtpHelper::login();

        $app = Factory::getApplication();

        if ($response) {
            $app->enqueueMessage("Ftp connection Success", 'success');
        }else{
            $app->enqueueMessage("Ftp connection Unsuccessful", 'danger');
        }
    }

    /**
     * Method to list files in a directory
     * 
     * @param string Directory
     * @return array List of files
     * 
     * @since  1.0
     */
    public static function listDirectory($directory)
    {
    }

    /** Method to check given path is directory
     * 
     * @param string $path Path
     * @return boolean
     * 
     * @since  1.0
     */
    public static function isDir($path)
    {
    }

    /**
     *  Method to get content of File with File system
     * 
     * @param string Source 
     * @return string File content
     * 
     * @since  1.0
     */

    public static function getContent($source)
    {
    }

    /**
     *  Method to login using ftp options
     * 
     * @return boolean True on success
     * 
     * @since 1.0
     */
    public static function login()
    {
        $result = false;
        $app = Factory::getApplication();

        FtpHelper::$ftp = new FtpClient();

        $connection = ftp_connect(FtpHelper::$options['host'], FtpHelper::$options['port']);

        if (!$connection) {
            $app->enqueueMessage("Ftp connection Unsuccessful Due to host or port", 'danger');
            return false;
        }
        
        $islogin = ftp_login($connection, FtpHelper::$options['username'], FtpHelper::$options['password']);
        
        if (!$islogin) {
            $app->enqueueMessage("Ftp login  Unsuccessful dues to username or password", 'danger');
            return false;
        }
 
        $isbasedirexist = @ftp_chdir( $connection, FtpHelper::$options['ftpbasedir'] );
        
        if (!$isbasedirexist) {
            $app->enqueueMessage("Ftp base directory not exist ", 'danger');
            return false;
        }

        $result = $connection && $islogin && $isbasedirexist;
        return $result;
    }
}
