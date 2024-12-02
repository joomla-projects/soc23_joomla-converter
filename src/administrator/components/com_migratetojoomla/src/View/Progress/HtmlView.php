<?php

namespace Joomla\Component\MigrateToJoomla\Administrator\View\Progress;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright     (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Migrate "Migrate To Joomla" Progress View
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

        $temp   = $this->importstring;
        $output = [];
        foreach ($temp as $key => $value) {
            array_push($output, str_replace("data", "", $value[1]));
        }
        Factory::getApplication()->getSession()->set('migratetojoomla.arrayimportstring', $output);
        // calling plugin storemaxkey method
        $framework = @Factory::getApplication()->getUserState('com_migratetojoomla.migrate', [])['framework'];

        PluginHelper::importPlugin('migratetojoomla', $framework);

        $event = AbstractEvent::create(
            'migratetojoomla_storemaxprimarykey',
            [
                'subject' => $this,
            ]
        );

        Factory::getApplication()->triggerEvent('migratetojoomla_storemaxprimarykey', $event);


        $event = AbstractEvent::create(
            'migratetojoomla_storeprimarykey',
            [
                'subject' => $this,
            ]
        );

        Factory::getApplication()->triggerEvent('migratetojoomla_storeprimarykey', $event);


        $doc->addScriptOptions("com_migratetojoomla.importstring", $this->importstring);
        $doc->addScriptOptions("com_migratetojoomla.arrayimportstring", $output);

        $doc->addScriptOptions('com_migratetojoomla.displayimportstring', Factory::getSession()->get('migratetojoomla.displayimportstring', []));
        $doc->addScriptOptions('com_migratetojoomla.keys', Factory::getSession()->get('migratetojoomla.tablekeys', []));
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

        @$isdatabasemigration = $data["databasemigratestatus"];
        @$ismediamigration    = $data["mediamigratestatus"];
        $displayimportstring  = [];
        if ($isdatabasemigration == "1") {
            $databasetable = $data["frameworkparams"];

            foreach ($databasetable as $field => $value) {
                if ($value == "1") {
                    $fielddata = [];
                    if (\count($this->importstring) == 0) {
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
            $fielddata = [];
            if (\count($this->importstring) == 0) {
                array_push($fielddata, "active");
            } else {
                array_push($fielddata, "remain");
            }

            array_push($fielddata, "mediadata");
            array_push($this->importstring, $fielddata);
        }
    }
}
