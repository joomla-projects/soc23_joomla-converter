<?php

namespace Joomla\Component\MigrateToJoomla\Administrator\View\Main;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright     (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Main "Migrate To Joomla" Admin View
 */
class HtmlView extends BaseHtmlView
{
    
    /**
     * Display the main "Migrate To Joomla" view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     * 
     * @since  4.3.0
     */
    public function display($tpl = null)
    {
        parent::display($tpl);
    }

}