<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Controller;

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
class MigrateController extends FormController
{
    /**
     * Method to save form data and redirect to next view
     *
     * @since 1.0
     */
    public function storeFormAndNext()
    {
        $this->checkToken();
        $data  = $this->input->post->get('jform', [], 'array');

        $session = $this->app->getSession();
        $session->set('framework', $data['framework']);

        $this->app->setUserState('com_migratetojoomla.migrate', $data);

        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla&view=information', false));
    }
}
