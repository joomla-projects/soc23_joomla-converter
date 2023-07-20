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

use Joomla\Component\MigrateToJoomla\Administrator\Helper\MainHelper;
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


class FilesystemHelper
{

    /**
     * Method to check Enter base url connection
     * 
     * @param string Base url of live website
     * @return boolean True on success
     * 
     * since
     */
    public static function testconnection($path = NULL)
    {
        $app   = Factory::getApplication();

        $check = FilesystemHelper::isdir($path);
        if ($check) {
            $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_FS_CONNECTION_SUCCESSFULLY'), 'success');
            return true;
        }
        $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_FS_CONNECTION_UNSUCCESSFULLY'), 'warning');
        return false;
    }

    /**
     * Method to list files in a directory
     * 
     * @param string Directory
     * @return array List of files
     */
    public static function listdirectory($directory)
    {
        $files = array();
        if (FilesystemHelper::isdir($directory) && scandir($directory)) {
            $files = scandir($directory);
        }
        return $files;
    }

    /** Method to check given path is directory
     * 
     * @param string $path Path
     * @return boolean
     */
    public static function isdir($path)
    {
        return is_dir($path);
    }

    /**
     *  Method to get content of File with File system
     * 
     * @param string Source 
     * @return string File content
     */

    public static function getcontent($source)
    {
        $content = false;
        $content = file_get_contents($source);
        return $content;
    }
}
