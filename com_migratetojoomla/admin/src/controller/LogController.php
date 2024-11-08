<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Log controller class.
 *
 * @since  1.0
 */
class LogController extends FormController
{

    /**
     * Download the log file as a text file
     *
     * @since 1.0
     */
    public function download()
    {
        $this->checkToken();
        $app = Factory::getApplication();

        $logFileName = @trim((Factory::getApplication()->getUserState('com_migratetojoomla.migrate', [])['framework'] . '')) . '-to-Joomla-migrate.txt';

        $headWithFileName = "Content-Disposition: attachment; filename=$logFileName";

        @ob_end_clean();
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-Description: File Transfer");
        header('Content-Type: text/plain');
        header($headWithFileName);

        $this->echoRawLog();

        flush();

        $app->close();
    }

    /**
     * Method to copy lines of a file
     * 
     * @since 1.0
     */
    public function echoRawLog()
    {
        $logfolder = JPATH_ADMINISTRATOR . '\components\com_migratetojoomla\logs\\';
        $logfileName = @trim((Factory::getApplication()->getUserState('com_migratetojoomla.migrate', [])['framework'] . '')) . '-to-Joomla.log';

        $logFile = $logfolder . $logfileName;

        $file = @fopen($logFile, 'r');

        if ($file === false) {
            $app   = Factory::getApplication();
            $app->enqueueMessage(Text::_('COM_MIGRATETOJOOMLA_ERROR_WHILE_DOWNLOAD_LOG'), 'danger');
            return;
        }

        while (!feof($file)) {
            echo rtrim(fgets($file)) . "\r\n";
        }

        @fclose($file);
    }
}
