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


class DownloadHelper
{
    public  $downloadmanager;
    /**
     * Method to check connection with respective method
     * 
     * @param array form data
     * @return boolean True on success
     * 
     * since
     */
    public static function testconnection($data = [])
    {
        $method = $data['mediaoptions'];

        if ($method == 1) {
            // Http
            HttpHelper::testconnection($data['livewebsiteurl']);
        } else if ($method == 2) {
            // File system
            FilesystemHelper::testconnection($data['basedir']);
        }
    }

    /**
     * Method to Download 
     * 
     * @param array form data
     * 
     */
    public static function download($data = [])
    {
        $method = $data['mediaoptions'];

        switch ($method) {
            case 2:
                DownloadHelper::$downloadmanager = new FilesystemHelper;
            case 3:
                DownloadHelper::$downloadmanager = new FtpHelper;
            case 1:
            default:
                DownloadHelper::$downloadmanager = new HttpHelper;
        }
        
        $source = $data['basedir'].'wp-content\uploads';

        $destination = JPATH_BASE.'\images';

        DownloadHelper::copy($source , $destination);

    }

    /**
     * Method to copy a file or a directory
     *
     * @param string $source Original file or directory name
     * @param string $destination Destination file or directory name
     * @param bool $recursive Recursive copy?
     * @return bool File copied or not
     */
    public function copy($source, $destination, $recursive = true)
    {
        if ($this->isdir($source)) {
            // Directory
            return $this->copydir($source, $destination);
        } else {
            // File
            return $this->copyfile($source, $destination);
        }
    }

    /**
     * Method to list directory content
     * 
     * @param string $directory path
     * @return array List of files and directory
     * 
     */
    public function listdirectory($directory = '')
    {
        return DownloadHelper::$downloadmanager::listdirectory($directory);
    }

    /**
     * Method to path is directory or not
     * 
     * @param string $path 
     * @param bool True on success
     * 
     */
    public function isdir($path = '')
    {
        return DownloadHelper::$downloadmanager::isdir($path);
    }

    /**
     * Method to copy file
     * 
     * @param string $source source path
     * @param string $destination destination path
     * 
     * @return boolean True on success
     */
    public function copyfile($source, $destination)
    {
        $response = false;

        if (file_exists($destination) && (filesize($destination) > 0)) {
            // file Already downloaded 
            return true;
        }

        $filecontent = DownloadHelper::$downloadmanager->getcontent($source);
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
     */
    public function copydir($source, $destination)
    {
        $response = true;
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true); // Create the directory if not exist
        }
        foreach ($this->listdirectory($source) as $file) {
            $isdirectory = false;
            $source_filename = $source . $file;
            $dest_filename = $destination . $file;
            if ($this->isdir($source_filename)) {
                $isdirectory = true;
            }
            $response |= copy($source_filename, $$dest_filename, $isdirectory);
        }
        return $response;
    }
}
