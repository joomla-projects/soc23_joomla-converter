<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
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
class CheckController extends FormController
{

    
    /**
     * Method to save form data and redirect to next view
     * 
     * @since 1.0
     */
    public function storeFormAndNext()
    {
        //redirect in all case
        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla&view=check', false));
    }

    /**
     * Method to save form data and redirect to next view
     * 
     * @since 1.0
     */
    public function storeFormAndPrevious()
    {
        //redirect in all case
        $this->setRedirect(Route::_('index.php?option=com_migratetojoomla&view=parameter', false));
    }
}
