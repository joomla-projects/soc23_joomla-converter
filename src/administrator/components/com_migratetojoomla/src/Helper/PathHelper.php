<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class PathHelper
{
    /**
     * Method to append a trailing slash.
     *
     * @param string file or directory path
     * @return string file or directory path
     *
     * @since 1.0
     */
    public static function addTrailingSlashit($path)
    {
        return pathHelper::untrailingslashit($path) . '/';
    }

    /**
     * Method to remove trailing forward slashes and backslashes if they exist.
     *
     * @param string file or directory path
     * @return string file or directory path
     *
     * @since 1.0
     */
    public static function unTrailingSlashit($path)
    {
        return rtrim($path, '/\\');
    }
}
