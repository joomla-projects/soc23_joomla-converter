<?php

namespace Joomla\Component\MigrateToJoomla\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   
 * @license     GNU General Public License version 3; see LICENSE
 */

/**
 * Default Controller of MigrateToJoomla component
 *
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 */
class DisplayController extends BaseController 
{
    /**
     * The default view for the display method.
     *
     * @var string
     * @since  1.6
     */
    protected $default_view = 'main';

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link \JFilterInput::clean()}.
     *
     * @return  BaseController|boolean  This object to support chaining.
     *
     * @since   1.5
     */

    public function display($cachable = false, $urlparams = [])
    {
        return parent::display($cachable, $urlparams);
    }
    
}