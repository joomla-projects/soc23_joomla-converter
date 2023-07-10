<?php

namespace Joomla\Component\MigrateToJoomla\Administrator\View\Main;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   
 * @license     GNU General Public License version 3; see LICENSE
 */

/**
 * Main "Migrate To Joomla" Admin View
 */
class HtmlView extends BaseHtmlView {
    
    /**
     * Display the main "Migrate To Joomla" view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @since   1.5
     *
     * @return  void
     */
    public function display($tpl = null): void
    {
        parent::display($tpl);
    }

}
