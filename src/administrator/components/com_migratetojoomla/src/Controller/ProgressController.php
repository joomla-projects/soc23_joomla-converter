<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Controller;

use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\LogHelper;

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
        if (!Session::checkToken('get')) {
            $this->app->setHeader('status', 403, true);
            $this->app->sendHeaders();
            echo Text::_('JINVALID_TOKEN_NOTICE');
            $this->app->close();
        }
        $app   = Factory::getApplication();
        $input = $app->input;
        $field = $input->getArray(['name' => ''])['name'];
        $key   = $input->getArray(['key' => ''])['key'];

        $this->callPluginMethod($field, $key);

        $default[] = [];

        $response = Factory::getSession()->get('migratetojoomla.ajaxresponse', $default);

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

        if ($field == "end") {
            LogHelper::writeLogFileOfSession();
        }
        if ($field == "media") {
            // calling media plugin method
            PluginHelper::importPlugin('migratetojoomla', 'mediadownload');

            $event = AbstractEvent::create(
                'migratetojoomla_downloadmedia',
                [
                    'subject'  => $this,
                    'formname' => 'com_migratetojoomla.parameter',
                ]
            );

            Factory::getApplication()->triggerEvent('migratetojoomla_downloadmedia', $event);
        } else {
            // calling framework specific plugin method for database migration

            $framework = Factory::getApplication()->getUserState('com_migratetojoomla.migrate')['framework'];

            // import framework plugin

            PluginHelper::importPlugin('migratetojoomla', $framework);

            $eventname = "migratetojoomla_" . $field;

            $event = AbstractEvent::create(
                $eventname,
                [
                    'subject'  => $this,
                    'formname' => 'com_migratetojoomla.parameter',
                    'key'      => $key,
                    'field'    => $field,
                ]
            );

            Factory::getApplication()->triggerEvent($eventname, $event);
        }
    }
}
