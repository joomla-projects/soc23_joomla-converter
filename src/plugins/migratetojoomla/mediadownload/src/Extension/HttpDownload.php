<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla.mediadownload
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\MigrateToJoomla\MediaDownload\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\path;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\pathHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class HttpDownload
{
    /**
     * @var string live website url
     *
     * @since 1.0
     */
    public $websiteurl;

    /**
     * Http constructor
     *
     * @since 1.0
     */
    public function __construct($websiteurl = '')
    {
        $this->websiteurl = pathHelper::unTrailingSlashit($websiteurl);
    }
    /**
     * Method to check Enter http url connection
     *
     * @param   string  $string  Http url of live website
     * @return  boolean True on success
     *
     * @since 1.0
     */
    public static function testConnection($url = '')
    {
        $app     = Factory::getApplication();
        $headers = [];
        try {
            $response   = HttpFactory::getHttp()->get($url, $headers);
            $statusCode = $response->code;

            if ($statusCode == 200) {
                $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_HTTP_CONNECTION_SUCCESSFULLY'), 'success');
            } else {
                $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_HTTP_CONNECTION_UNSUCCESSFULLY'), 'warning');
            }
            $instance        = new self();
            $isdirectorylist = $instance->listDirectoriesAndFiles($url);

            if (empty($isdirectorylist)) {
                $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_HTTP_PLEASE_ALLOW_LIST_DIRECTORY'), 'warning');
            }
        } catch (\RuntimeException $exception) {
            $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_HTTP_CONNECTION_UNSUCCESSFULLY'), 'warning');
        }
    }

    /**
     * Method to list files in a directory
     *
     * @param   string  $url  Directory
     * @return  array List of files
     *
     * @since  1.0
     */
    public function listDirectory($url = '')
    {
        $files = [];

        $tmpfiles  = $this->listDirectoriesAndFiles($url);

        // remove live website url from current url so that url contain directory path inside which directory and files scan
        // Response obtain contain this in path so required to remove
        $pos = strpos($url, $this->websiteurl);
        if ($pos !== false) {
            $url = substr_replace($url, '', $pos, \strlen($this->websiteurl));
        }
        $url = path::clean($url);

        // remove current directory path from file/directory path to get exact file/directory path
        foreach ($tmpfiles as $file) {
            $pos    = strpos($file, $url);
            $result = $file;
            if ($pos !== false) {
                $result = substr_replace($result, '', $pos, \strlen($url));
            }
            array_push($files, path::clean($result));
        }

        return $files;
    }

    /** Method to check given path is directory
     *
     * @param   string  $path  Path
     * @return  boolean
     *
     * @since  1.0
     */
    public function isDir($url = '')
    {
        $result = true;
        // If current url represent file then file get content will return true;
        if (!($this->listDirectoriesAndFiles($url))) {
            $result = false;
        }
        return $result;
    }

    /**
     *  Method to get content of File with Http
     *
     * @param string $source Source
     * @param string $destination Destination
     * @return string File content
     *
     * @since  1.0
     */

    public function getContent($source, $destination)
    {
        $app    = Factory::getApplication();
        $source = str_replace(" ", "%20", $source); // for filenames with spaces
        $source = str_replace("&amp;", "&", $source); // for filenames with &

        try {
            $content = @file_get_contents($source);

            if ($content) {
                $response = (file_put_contents($destination, $content) !== false);
                return $response;
            }
            $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_HTTP_DOWNLOAD_ERROR'), 'danger');
        } catch (\RuntimeException $exception) {
            $app->enqueueMessage($exception->getMessage(), 'warning');
        }

        return false;
    }

    /**
     *  Method to get list of directory and files in a http url
     *
     * @param   string  $url  a directory url
     *
     * @return  array
     * Empty array if directory listing is disable
     * False if given url is file
     * Directory and Files in given directory url
     *
     * @since 1.0
     */
    public function listDirectoriesAndFiles($url = '')
    {
        $html = @file_get_contents($url);

        // if unable to load data it mean directory list is disable on server so return empty array
        if ($html === false) {
            // Error handling if unable to fetch the content.
            return [];
        }

        // Create a DOMDocument object to parse the HTML content.
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true); // Disable error reporting for invalid HTML.
        $dom->loadHTML($html);
        libxml_clear_errors();

        $links               = $dom->getElementsByTagName('a');
        $directoriesAndFiles = [];

        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            if ($href !== '../') {
                $directoriesAndFiles[] = $href;
            }
        }

        // it is not directory $directoryAndFiles will empty
        if (empty($directoriesAndFiles)) {
            return false;
        }

        // remove Http urls
        // response obtain from dom document contain wordpress and livewebsite utl
        $tmpfiles = [];
        $count    = -1;
        foreach ($directoriesAndFiles as $file) {
            $pos   = strpos($file, 'http');
            $count = $count + 1;
            if (!($pos !== false)) {
                array_push($tmpfiles, $file);
            }
        }

        // remove unnecceary elements
        // response obtain using href contain 4 unwanted element
        if (\count($tmpfiles) > 4) {
            // remove unwanted elements from array
            $tmpfiles = \array_slice($tmpfiles, 4);
        }
        return $tmpfiles;
    }
}
