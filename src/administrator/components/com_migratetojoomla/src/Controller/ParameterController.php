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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Migrate controller class.
 *
 * @since  1.0
 */
class ParameterController extends FormController
{
    /**
     * Method to save form data
     *
     * @since 1.0
     */
    public function storeFormAndNext()
    {
        $this->checkToken();
        $data  = $this->input->post->get('jform', [], 'array');

        $this->app->setUserState('com_migratetojoomla.parameter', $data);

        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla&view=check', false));
    }

    /**
     * Method to save form data
     *
     * @since 1.0
     */
    public function storeFormAndPrevious()
    {
        $this->checkToken();
        $data  = $this->input->post->get('jform', [], 'array');

        $session = Factory::getSession();
        $session->set('parameterformdata', $data);

        $this->appsetUserState('com_migratetojoomla.parameter', $data);

        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla&view=information', false));
    }
}
