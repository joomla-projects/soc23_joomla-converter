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
     * @since 1.0
     */
    public static function testConnection($path = '' , $msgshow = 1)
    {
        $app = Factory::getApplication();
        if (is_dir($path)) {
           $msgshow && $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_FS_CONNECTION_SUCCESSFULLY'), 'success');
            return true;
        }
        $msgshow && $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_FS_CONNECTION_UNSUCCESSFULLY'), 'warning');
        return false;
    }
}
