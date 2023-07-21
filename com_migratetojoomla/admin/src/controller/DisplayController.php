<?php

namespace Joomla\Component\MigrateToJoomla\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright     (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Default Controller of Migrate To Joomla component
 *
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 * 
 * @since  1.0
 */
class DisplayController extends BaseController
{
    /**
     * The default view for the display method.
     *
     * @var string
     */
    protected $default_view = 'main';

    /**
     * Method to display a view.
     *
     * @param   boolean $cachable  If true, the view output will be cached
     * @param   array   $urlparams An array of safe URL parameters and their variable types, for valid values see {@link \JFilterInput::clean()}.
     *
     * @return  static |boolean  This object to support chaining. False on failure.
     *
     * @since  1.0
     */
    public function display($cachable = false, $urlparams = array())
    {
        return parent::display($cachable, $urlparams);
    }
}
