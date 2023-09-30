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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class LogHelper
{
    /**
     * Method to write in log file
     * 
     * @param string content to write
     * @param string Is content is success , error or message(normal)
     * @since 1.0
     */
    public static function writeLog($content = "", $type = "normal")
    {
        if (empty($content)) {
            return;
        }

        self::checkLogFile();

        $selectedframework = Factory::getApplication()->getUserState('com_migratetojoomla.migrate', [])['framework'];

        $logfilename = $selectedframework . '-to-Joomla.log';
        $logfilepath = JPATH_COMPONENT_ADMINISTRATOR . '/logs/' . $logfilename;

        $contentToWrite =  '{' . $type . '}' . $content  . '{' . 'contentend' . '}' . PHP_EOL;
        $file = @fopen($logfilepath, 'a');
        $currentDateTime = date('Y-m-d H:i:s').PHP_EOL;
        fwrite($file, 'Timestamp : '.$currentDateTime);
        fwrite($file, $contentToWrite);
        fclose($file);
    }

    /** Method to check log file exist or not and create if not exist
     * 
     * @since 1.0
     */
    public static function checkLogFile()
    {

        $selectedframework = Factory::getApplication()->getUserState('com_migratetojoomla.migrate', [])['framework'];

        $logfilename = $selectedframework . '-to-Joomla.log';
        $logfilepath = JPATH_COMPONENT_ADMINISTRATOR . '/logs/' . $logfilename;
        if (!file_exists($logfilepath)) {
            $file = @fopen($logfilepath, 'w');
            fclose($file);
        }
    }
}
