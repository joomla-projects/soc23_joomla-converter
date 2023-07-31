<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Controller;

use Joomla\Database\DatabaseFactory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Versioning\VersionableControllerTrait;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\MainHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * main controller class.
 *
 * @since  1.0
 */
class MainController extends FormController
{
    use VersionableControllerTrait;

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  
     */
    protected $text_prefix = 'COM_MIGRATETOJOOMLA_MAIN';

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

        MainHelper::testconnection($data);
        // Store data in session
        $app->setUserState('com_migratetojoomla.main', $data);

        // redirect in all case
        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla', false));
    }

    /**
     * Controller funciton to check Database connection
     * 
     * @since 1.0
     */
    public function checkDatabaseConnection()
    {
        $this->checkToken();

        $app   = Factory::getApplication();

        $data  = $this->input->post->get('jform', array(), 'array');

        $option = array(); //prevent problems

        $option['driver']   = $data['dbdriver'];
        $option['host']     = $data['dbhostname'] . ':' . $data['dbport'];
        $option['user']     = $data['dbusername'];
        $option['password'] = $data['dbpassword'];
        $option['database'] = $data['dbname'];
        $option['prefix']   = $data['dbtableprefix'];

        $factory      = new DatabaseFactory();
        $db = $factory->getDriver(
            $option['driver'],
            $option
        );

        if ($db) {
            $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_SUCCESSFULLY'), 'success');
        } else {
            $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_UNSUCCESSFULLY'), 'danger');
        }
        // Store data in session
        $app->setUserState('com_migratetojoomla.main', $data);

        // redirect in all case
        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla', false));
    }

    /**
     * Method to Download file
     * 
     * @since  1.0
     */
    public function download()
    {
        $this->checkMediaConnection();
        $app   = Factory::getApplication();
        try {

            $data  = $this->input->post->get('jform', array(), 'array');
            $load = new MainHelper();
            $load->downloadMedia($data);

            $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_DOWNLOAD_MEDIA_SUCCESSFULLY'), 'success');
        } catch (\RuntimeException $th) {
            $app->enqueueMessage(TEXT::_('COM_MIGRATETOJOOMLA_DOWNLOAD__MEDIA_UNSUCCESSFULLY'), 'success');
        }
        // redirect in all case
        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla', false));
    }
}
