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
        $currentDateTime = date('Y-m-d H:i:s') . PHP_EOL;
        fwrite($file, 'Timestamp : ' . $currentDateTime);
        fwrite($file, $contentToWrite);
        fclose($file);
    }

    public static function writeSessionLog($status = NULL, $field = NULL)
    {
        if (is_null($status) || is_null($field)) {
            return;
        }
        self::writeLog("Line no 54", "success");
        $session = Factory::getApplication()->getSession()->get('migratetojoomla.log', []);

        $fieldValue = ["success" => 0, "error" => 0];
        if (array_key_exists($field, $session)) {
            $fieldValue = $session[$field];
        }

        if ($status == "success") {
            $fieldValue["success"]  = $fieldValue["success"] + 1;
            self::writeLog("Line no 61  : " . $fieldValue["success"], "success");
        } else if ($status == "error") {
            $fieldValue["error"] = $fieldValue["error"] + 1;
        }

        $session[$field] = $fieldValue;

        Factory::getApplication()->getSession()->set('migratetojoomla.log', $session);
    }

    public static function writeLogFileOfSession()
    {
        $session = Factory::getApplication()->getSession()->get('migratetojoomla.log', []);
        self::writeLog("Migration Report........", "success");
        foreach ($session as $field => $value) {
            self::writeLog($field . "s Imported Successfully  = " . $value["success"], "success");
            self::writeLog($field . "s Imported Unsuccessfully  = " . $value["error"], "error");
        }
        Factory::getApplication()->getSession()->clear('migratetojoomla.log');
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
