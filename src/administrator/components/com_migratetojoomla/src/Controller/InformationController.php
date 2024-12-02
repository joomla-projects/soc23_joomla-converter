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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseDriver;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Information controller class.
 *
 * @since  1.0
 */
class InformationController extends FormController
{
    /**
     * @var object  Media Download object
     *
     * @since 1.0
     */
    public $mediaDownloadManager;

    /**
     * @var object Database parameters object
     *
     * @since 1.0
     */
    public $options;

    /**
     * @var object Database object
     *
     * @since 1.0
     */
    public $db;

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since
     */
    protected $text_prefix = 'COM_MIGRATETOJOOMLA_INFORMATION';

    /**
     * Method to save form data
     *
     * @since 1.0
     */
    public function storeFormAndPrevious()
    {
        $this->checkToken();
        $data  = $this->input->post->get('jform', [], 'array');

        $this->app->setUserState('com_migratetojoomla.information', $data);

        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla&view=migrate', false));
    }

    /**
     * Method to save form data and redirect to next view
     *
     * @since 1.0
     */
    public function storeFormAndNext()
    {
        $this->checkToken();
        $data  = $this->input->post->get('jform', [], 'array');

        $this->checkMediaConnection(0);
        $this->checkDatabaseConnection(0);
        $this->app->setUserState('com_migratetojoomla.information', $data);

        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla&view=parameter', false));
    }

    /**
     * Method to check media connection.
     *
     * @since 1.0
     */
    public function checkMediaConnection()
    {
        $this->checkToken();

        $data  = $this->input->post->get('jform', [], 'array');

        // Store data in session
        $this->app->setUserState('com_migratetojoomla.information', $data);

        PluginHelper::importPlugin('migratetojoomla', 'mediadownload');

        $event = AbstractEvent::create(
            'migratetojoomla_testmediaconnection',
            [
                'subject' => $this,
            ]
        );

        $this->app->triggerEvent('migratetojoomla_testmediaconnection', $event);

        $this->app->setUserState('com_migratetojoomla.information', $data);

        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla&view=information', false));
    }

    /**
     * Method to check Database connection
     *
     * @since 1.0
     */
    public function checkDatabaseConnection($msgshow = 1)
    {
        $this->checkToken();

        $data  = $this->input->post->get('jform', [], 'array');

        $session = $this->app->getSession();

        if (self::setdatabase($this, $data)) {
            $msgshow && $this->app->enqueueMessage(Text::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_SUCCESSFULLY'), 'success');
            $session->set('databaseconnectionresult', true);
        } else {
            $msgshow && $this->app->enqueueMessage(Text::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_UNSUCCESSFULLY'), 'error');
            $session->set('databaseconnectionresult', false);
        }

        $this->app->setUserState('com_migratetojoomla.information', $data);

        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla&view=information', false));
    }

    /**
     * Method to set database
     *
     * @param array form data
     * @return boolean True on success
     *
     * @since 1.0
     */
    public static function setdatabase($instance, $data = [])
    {
        if (\is_resource($instance->db)) {
            return true;
        }

        $options = [
            'driver'   => $data['dbdriver'],
            'host'     => $data['dbhostname'] . ':' . $data['dbport'],
            'user'     => $data['dbusername'],
            'password' => $data['dbpassword'],
            'database' => $data['dbname'],
            'prefix'   => $data['dbtableprefix'],
        ];

        try {
            $db = DatabaseDriver::getInstance($options);
            $db->getVersion();
            $instance->db = $db;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
