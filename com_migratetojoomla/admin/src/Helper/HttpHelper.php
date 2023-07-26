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
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class HttpHelper
{
    /**
     * Method to check Enter http url connection
     * 
     * @param string Http url of live website
     * @return boolean True on success
     * 
     * @since 1.0
     */
    public static function testConnection($url = NULL)
    {

        $app   = Factory::getApplication();

        $headers = [];
        try {
            $response = HttpFactory::getHttp()->get($url, $headers);
            $statusCode = $response->code;

            if ($statusCode == 200) {

                $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_HTTP_CONNECTION_SUCCESSFULLY'), 'success');
            } else {
                $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_HTTP_CONNECTION_UNSUCCESSFULLY'), 'warning');
            }
        } catch (\RuntimeException $exception) {
            $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_HTTP_CONNECTION_UNSUCCESSFULLY'), 'warning');
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
        // Not required in HTTP
        return array();
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
        // Not required in HTTP
        return false;
    }
    // /**
    //  * Method to list files in a directory
    //  * 
    //  * @param string Directory
    //  * @return array List of files
    //  * 
    //  * @since  1.0
    //  */
    // public static function listDirectory($directory)
    // {
    //     $files = array();
    //     if (HttpHelper::isDir($directory) && scandir($directory)) {
    //         $files = scandir($directory);
    //     }
    //     return $files;
    // }

    // /** Method to check given path is directory
    //  * 
    //  * @param string $path Path
    //  * @return boolean
    //  * 
    //  * @since  1.0
    //  */
    // public static function isDir($path)
    // {
    //     return is_dir($path);
    // }

    /**
     *  Method to get content of File with Http
     * 
     * @param string Source 
     * @return string File content
     * 
     * @since  1.0
     */

    public static function getContent($source)
    {
        $app   = Factory::getApplication();
        $source = str_replace(" ", "%20", $source); // for filenames with spaces
        $source = str_replace("&amp;", "&", $source); // for filenames with &

        try {
            $response = HttpFactory::getHttp([], ['curl', 'stream'])->get($source);
            $statusCode = $response->code;

            if ($statusCode === 200) {

                $content = $response->body;
            } else {
                $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_HTTP_DOWNLOAD_ERROR'), 'danger');
                return false;
            }
        } catch (\RuntimeException $exception) {
            $app->enqueueMessage($exception->getMessage(), 'warning');
            return false;
        }

        return $content;
    }
}
