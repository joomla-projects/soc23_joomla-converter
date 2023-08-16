<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.contact
 *
 * @copyright   (C) 2014 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\MigrateToJoomla\MediaDownload\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\PathHelper;
use Joomla\Plugin\MigrateToJoomla\MediaDownload\Extension\HttpDownload;
use Joomla\Plugin\MigrateToJoomla\MediaDownload\Extension\FilesystemDownload;
use Joomla\Plugin\MigrateToJoomla\MediaDownload\Extension\FtpDownload;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * MediaDownload Plugin
 *
 * @since  3.2
 */
final class MediaDownload extends CMSPlugin
{
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
            HttpDownload::testConnection($data['livewebsiteurl']);
        } else if ($method == "fs") {
            // File system
            FilesystemDownload::testConnection($data['basedir']);
        } else if ($method == "ftp") {
            FtpDownload::testConnection($data);
        }
    }
}
