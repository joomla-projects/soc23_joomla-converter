<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\MigrateToJoomla\MediaDownload\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Client\FtpClient;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class FtpDownload
{
    /**
     * @var array  ftp parameters
     * 
     * @since 1.0
     */
    public  $options = array();

    /**
     * @var object ftpclient object
     * 
     * @since 1.0
     */
    public  $ftp;

    /**
     * Class constructor
     * 
     * @param array ftpparameters
     * 
     * @since 1.0
     */
    public function __construct($data = [])
    {
        $this->options = $data;
    }

    /**
     * Method to check Enter base url connection
     * 
     * @param string Base url of live website
     * @param boolean test by user of not
     * @return boolean True on success
     * 
     * @since 1.0
     */
    public static function testConnection($data = [])
    {
        $instance = new self;
        $instance->options = $data;
        $response = $instance->login();
        $app = Factory::getApplication();

        if ($response) {
           $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_FTP_CONNECTION_SUCCESFULLY'), 'success');
        } else {
           $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_FTP_CONNECTION_UNSUCCESSFULLY'), 'danger');
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
    public function listDirectory($directory)
    {
        if (!$this->ftp->isConnected()) {
            $this->login();
        }
        $files = array();
        $files = $this->ftp->listNames($directory);
        return $files;
    }

    /** Method to check given path is directory
     * 
     * @param string $path Path
     * @return boolean
     * 
     * @since  1.0
     */
    public function isDir($path)
    {
        if (!$this->ftp->isConnected()) {
            $this->login();
        }
        return !empty($this->ftp->listNames($path));
    }

    /**
     *  Method to get content of File with File system
     * 
     * @param string Source 
     * @return string File content
     * 
     * @since  1.0
     */
    public  function getContent($source, $destination)
    {
        if (!$this->ftp->isConnected()) {
            $this->login();
        }
        return $this->ftp->get($destination, $source);
    }

    /**
     * Method to login using ftp options
     * 
     * @return boolean True on success
     * 
     * @since 1.0
     */
    public function login()
    {
        $this->ftp = new FtpClient();

        $instance = $this->ftp;
        $isconnect = $instance->connect($this->options['ftphost'], $this->options['ftpport']);
        $islogin = $instance->login($this->options['ftpusername'], $this->options['ftppassword']);
        $isdir = $instance->listNames($this->options['ftpbasedir']);

        return $isconnect && $islogin && !empty($isdir);
    }
}
