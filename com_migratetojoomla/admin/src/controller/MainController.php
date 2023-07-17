<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Versioning\VersionableControllerTrait;

use Joomla\Component\MigrateToJoomla\Administrator\Helper\HttpHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * main controller class.
 *
 * @since  
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
     * Method to check connection.
     * 
     * @return boolean True on success
     * 
     * @since
     */
    public function checkconnection()
    {
        $this->checkToken();

        $app   = Factory::getApplication();

        $data  = $this->input->post->get('jform', array(), 'array');

        $input = $app->getInput();
        $url = $input->get('livewebsiteurl', '', 'STRING');

        HttpHelper::testhttpconnection($url);
        // Store data in session
        $app->setUserState('com_migratetojoomla.main', $data);

        // redirect in all case
        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla', false));
    }

    /**
     * Method to Download file
     * 
     * 
     */
}
