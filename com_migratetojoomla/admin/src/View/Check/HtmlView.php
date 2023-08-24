<?php

namespace Joomla\Component\MigrateToJoomla\Administrator\View\Check;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
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
        $session = Factory::getSession();

        $this->ismediaconnection = $session->get('mediaconnectionresult');
        $this->isdatabaseconnection = $session->get('databaseconnectionresult');
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
      
        $toolbar->linkButton('previous')
			->icon('icon-previous')
			->text('COM_MIGRATETOJOOMLA_PREVIOUS')
			->url(Route::_('index.php?option=com_migratetojoomla&view=parameter'));
        $toolbar->linkButton('next')
			->icon('icon-next')
			->text('COM_MIGRATETOJOOMLA_NEXT')
			->url(Route::_('index.php?option=com_migratetojoomla&view=migrate'));
    }
}
