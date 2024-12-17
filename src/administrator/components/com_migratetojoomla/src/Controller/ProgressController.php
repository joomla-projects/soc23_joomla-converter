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
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\MigrateToJoomla\Administrator\Event\MigrationStatusEvent;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Progress controller class.
 *
 * @since  1.0
 */
class ProgressController extends BaseController
{
    /**
     * @var string  Contain current migration field
     *
     * @since 1.0
     */

    public function ajax()
    {
        /**if (!Session::checkToken('get')) {
            $this->app->setHeader('status', 403, true);
            $this->app->sendHeaders();
            echo Text::_('JINVALID_TOKEN_NOTICE');
            $this->app->close();
        }**/

        $input = $this->app->getInput();
        $field = $input->getArray(['name' => ''])['name'];
        $key   = $input->getArray(['key' => ''])['key'];

        $event = $this->callPluginMethod($field, $key);

        if ($event->getStatus() == 1) {
            $response = ['status' => 'success'];
        } elseif ($event->getStatus() == 0) {
            $response = ['status' => 'error'];
        } elseif ($event->getStatus() == -1) {
            $response = ['status' => 'notice'];
        }

        echo json_encode($response);
        $this->app->close();
    }

    /**
     * Method to call specific plugin methods
     *
     * @since 1.0
     */
    public function callPluginMethod($field = '', $key = null)
    {
        if (empty($field) || \is_null($key)) {
            return;
        }


        $options = [
            'format'    => '{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}',
            'text_file' => 'wordpress-to-joomla.php',
            ];
        Log::addLogger($options);

        if ($field == "media") {
            // calling media plugin method
            PluginHelper::importPlugin('migratetojoomla', 'mediadownload');

            $event = new MigrationStatusEvent(
                'migratetojoomla_downloadmedia',
                [
                    'subject'  => $this,
                    'formname' => 'com_migratetojoomla.parameter',
                ]
            );

            $this->app->getDispatcher()->dispatch('migratetojoomla_downloadmedia', $event);
        } else {
            // calling framework specific plugin method for database migration

            $framework = Factory::getApplication()->getUserState('com_migratetojoomla.migrate')['framework'];

            // import framework plugin

            PluginHelper::importPlugin('migratetojoomla', $framework);

            $eventname = "migratetojoomla_" . $field;

            $event = new MigrationStatusEvent(
                $eventname,
                [
                    'subject'  => $this,
                    'formname' => 'com_migratetojoomla.parameter',
                    'key'      => $key,
                    'field'    => $field,
                ]
            );
            $event->setLastID((int) $this->app->getUserState('com_migratetojoomla.migrate.lastKey', 0));
            $this->app->getDispatcher()->dispatch($eventname, $event);
            $this->app->setUserState('com_migratetojoomla.migrate.lastKey', $event->getLastID());
            if ($event->getStatus() == -1) {
                $this->app->setUserState('com_migratetojoomla.migrate.lastKey', 0);
            }
        }

        return $event;
    }
}
