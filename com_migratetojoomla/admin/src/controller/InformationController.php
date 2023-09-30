<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Controller;

use Joomla\Database\DatabaseDriver;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\Plugin\Editors\TinyMCE\PluginTraits\DisplayTrait;
use Joomla\CMS\Plugin\PluginHelper;

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
    use DisplayTrait;

    /**
     * @var object  Media Download object
     * 
     * @since 1.0
     */
    public  $mediaDownloadManager;

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
     * Method to save form data and redirect to next view
     * 
     * @since 1.0
     */
    public function storeFormAndPrevious()
    {
        $this->checkToken();
        $app   = Factory::getApplication();
        $data  = $this->input->post->get('jform', array(), 'array');

        $app->setUserState('com_migratetojoomla.information', $data);

        // redirect in all case
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
        $app   = Factory::getApplication();
        $data  = $this->input->post->get('jform', array(), 'array');

        $this->checkMediaConnection(0);
        $this->checkDatabaseConnection(0);
        $app->setUserState('com_migratetojoomla.information', $data);

        // redirect in all case
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

        $app   = Factory::getApplication();

        $data  = $this->input->post->get('jform', array(), 'array');

        // Store data in session
        $app->setUserState('com_migratetojoomla.information', $data);

        PluginHelper::importPlugin('migratetojoomla', 'mediadownload');

        $event = AbstractEvent::create(
            'migratetojoomla_testmediaconnection',
            [
                'subject'    => $this
            ]
        );

        Factory::getApplication()->triggerEvent('migratetojoomla_testmediaconnection', $event);

        // Store data in session
        $app->setUserState('com_migratetojoomla.information', $data);

        // redirect in all case
        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla&view=information', false));
    }

    /**
     * Controller funciton to check Database connection
     * 
     * @since 1.0
     */
    public function checkDatabaseConnection($msgshow = 1)
    {
        $this->checkToken();

        $app   = Factory::getApplication();
        $data  = $this->input->post->get('jform', array(), 'array');

        $session = Factory::getSession();

        if (self::setdatabase($this, $data)) {
            $msgshow && $app->enqueueMessage(Text::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_SUCCESSFULLY'), 'success');
            $session->set('databaseconnectionresult', true);
        } else {
            $msgshow &&  $app->enqueueMessage(Text::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_UNSUCCESSFULLY'), 'error');
            $session->set('databaseconnectionresult', false);
        }

        // Store data in session
        $app->setUserState('com_migratetojoomla.information', $data);

        // redirect in all case
        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla&view=information', false));
    }

    /**
     * Method to set database $db if it is not set
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
            'driver'    => $data['dbdriver'],
            'host'      => $data['dbhostname'] . ':' . $data['dbport'],
            'user'      => $data['dbusername'],
            'password'  => $data['dbpassword'],
            'database'  => $data['dbname'],
            'prefix'    => $data['dbtableprefix'],
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
