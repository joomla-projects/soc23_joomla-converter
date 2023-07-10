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
     */
    protected $default_view = 'main';
    
    public function display($cachable = false, $urlparams = array()) {
        return parent::display($cachable, $urlparams);
    }
    
}