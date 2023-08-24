<?php

namespace Joomla\Component\MigrateToJoomla\Administrator\View\Check;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\MigrateToJoomla\Administrator\Controller\InformationController;

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright     (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Migrate "Migrate To Joomla" Admin View
 */
class HtmlView extends BaseHtmlView
{   
    /**
	 * Media connection
	 *
	 * @var  bool
	 */
    public $ismediaconnection = false;

    /**
	 * Database connection
	 *
	 * @var  bool
	 */
    public $isdatabaseconnection  = false;
    /**
     * Display the Migrate "Migrate To Joomla" view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     * 
     * @since  1.0
     */
    public function display($tpl = null)
    {
        // Set ToolBar title
        ToolbarHelper::title(Text::_('COM_MIGRATETOJOOMLA'), 'Migrate To Joomla');

        $this->addToolbar();
        $this->onBeforeDisplay();
        parent::display($tpl);
    }

    /**
     * Method that loads necessary data
     *
     * @return  void
     *
     * @since   1.6
     */
    public function onBeforeDisplay()
    {
        $infocontroller = new InformationController();

        $this->ismediaconnection = $infocontroller->checkMediaConnection(0);

        // $this->isdatabaseconnection = $infocontroller->checkDatabaseConnection(0);
    }
    /**
     * Setup the Toolbar
     *
     * @return  void
     *
     * @since   1.6
     */
    protected function addToolbar(): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->customButton('previous')
            ->html('<joomla-toolbar-button><button onclick="Joomla.submitbutton(\'check.storeFormAndPrevious\')" '
                . 'class="btn btn-primary"><span class="icon-previous" aria-hidden="true"></span>'
                . Text::_('COM_MIGRATETOJOOMLA_PREVIOUS') . '</button></joomla-toolbar-button>');
        $toolbar->customButton('next')
            ->html('<joomla-toolbar-button><button onclick="Joomla.submitbutton(\'check.storeFormAndNext\')" '
                . 'class="btn btn-primary"><span class="icon-next" aria-hidden="true"></span>'
                . Text::_('COM_MIGRATETOJOOMLA_NEXT') . '</button></joomla-toolbar-button>');
    }
}
