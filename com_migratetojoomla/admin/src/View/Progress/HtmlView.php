<?php

namespace Joomla\Component\MigrateToJoomla\Administrator\View\Progress;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\MigrateToJoomla\Administrator\Model\MigrateModel;

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
     * Import Data information
     * 
     * @var array 
     * 
     * @since 1.0
     */
    public $importstring = [];
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
        $doc = Factory::getDocument();

        $this->createmigratedata();

        $doc->addScriptOptions("com_migratetojoomla.importstring", $this->importstring);

        // Ajax url
        $doc->addScriptOptions('migratetojoomla.AjaxURL', 'index.php?option=com_migratetojoomla&view=information&task=ajax');

        $doc->getWebAssetManager()
            ->useScript("com_migratetojoomla.admin-migratetojoomla");

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Setup the Toolbar
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function addToolbar(): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->linkButton('previous')
            ->icon('icon-previous')
            ->text('COM_MIGRATETOJOOMLA_PREVIOUS')
            ->url(Route::_('index.php?option=com_migratetojoomla&view=check'));
    }

    /**
     * Method to create import information
     * 
     * @return  void
     *
     * @since   1.0
     * 
     */

    public function createmigratedata()
    {
        $data = Factory::getApplication()->getUserState('com_migratetojoomla.parameter', []);

        $isdatabasemigration = $data["databasemigratestatus"];
        $ismediamigration = $data["mediamigratestatus"];

        if ($isdatabasemigration == "1") {
            $databasetable = $data["frameworkparams"];

            foreach ($databasetable as $field => $value) {
                if ($value == "1") {

                    $fielddata = array();
                    if (count($this->importstring) == 0) {
                        array_push($fielddata, "active");
                    } else {
                        array_push($fielddata, "remain");
                    }

                    array_push($fielddata, $field);
                    array_push($this->importstring, $fielddata);
                }
            }
        }

        if ($ismediamigration == "1") {
            
            $fielddata = array();
            if (count($this->importstring) == 0) {
                array_push($fielddata, "active");
            } else {
                array_push($fielddata, "remain");
            }

            array_push($fielddata, "mediadata");
            array_push($this->importstring, $fielddata);
        }
    }
}
