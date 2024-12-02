<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

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

        $contentToWrite  =  '{' . $type . '}' . $content  . '{' . 'contentend' . '}' . PHP_EOL;
        $file            = @fopen($logfilepath, 'a');
        $currentDateTime = date('Y-m-d H:i:s') . PHP_EOL;
        fwrite($file, 'Timestamp : ' . $currentDateTime);
        fwrite($file, $contentToWrite);
        fclose($file);
    }

    /** Method to write log into session
     *
     * @since 1.0
     */
    public static function writeSessionLog($status = null, $field = null)
    {
        if (\is_null($status) || \is_null($field)) {
            return;
        }
        $session = Factory::getApplication()->getSession()->get('migratetojoomla.log', []);

        $fieldValue = ["success" => 0, "error" => 0];
        if (\array_key_exists($field, $session)) {
            $fieldValue = $session[$field];
        }

        if ($status == "success") {
            $fieldValue["success"]  = $fieldValue["success"] + 1;
        } elseif ($status == "error") {
            $fieldValue["error"] = $fieldValue["error"] + 1;
        }

        $session[$field] = $fieldValue;

        Factory::getApplication()->getSession()->set('migratetojoomla.log', $session);
    }

    /** Method to write log file from session
     *
     * @since 1.0
     */
    public static function writeLogFileOfSession()
    {
        $session    = Factory::getApplication()->getSession()->get('migratetojoomla.log', []);
        $logsession = ['success' => [], 'error' => []];
        self::writeLog("Migration Report........", "success");
        foreach ($session as $field => $value) {
            $statementsuccess   = ucwords($field) . "s " . Text::_('COM_MIGRATETOJOOMLA_IMPORT_SUCCESSFULLY') . " = " . $value["success"];
            $statementunsuccess = ucwords($field) . "s " . Text::_('COM_MIGRATETOJOOMLA_IMPORT_UNSUCCESSFULLY') . " = " . $value["error"];
            self::writeLog($statementsuccess, "success");
            self::writeLog($statementunsuccess, "error");
            array_push($logsession['success'], $statementsuccess);
            array_push($logsession['error'], $statementunsuccess);
        }
        Factory::getApplication()->getSession()->clear('migratetojoomla.log');
        Factory::getApplication()->getSession()->set('migratetojoomla.logwrite', $logsession);
    }

    /** Method to check log file exist or not and create if not exist
     *
     * @since 1.0
     */
    public static function checkLogFile()
    {

        $selectedframework = Factory::getApplication()->getUserState('com_migratetojoomla.migrate', [])['framework'];

        $logfilename   = $selectedframework . '-to-Joomla.log';
        $logfolderpath = JPATH_COMPONENT_ADMINISTRATOR . '/logs';
        $logfilepath   =  $logfolderpath . $logfilename;
        if (!file_exists($logfolderpath)) {
            if (!mkdir($logfolderpath, 0777, true)) {
                $app   = Factory::getApplication();
                $app->enqueueMessage(Text::_('COM_MIGRATETOJOOMLA_LOG_FILE_CREATED_UNSUCCESSFULLY'), 'success');
                return;
            }
        }
        if (!file_exists($logfilepath)) {
            $file = @fopen($logfilepath, 'w');
            fclose($file);
        }
    }
}
