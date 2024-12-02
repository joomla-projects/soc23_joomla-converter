<?php

namespace Joomla\Component\MigrateToJoomla\Administrator\View\Migrate;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\MigrateToJoomla\Administrator\Model\MigrateModel;

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright     (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Migrate "Migrate To Joomla" Migrate View
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The Form object
     *
     * @var  \Joomla\CMS\Form\Form
     */
    protected $form;

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
        /** @var MigrateModel $model */
        $model       = $this->getModel();
        $this->form  = $model->getForm();

        if (!$this->form) {
            Factory::getApplication()->enqueueMessage('This is a warning message', 'warning');
        }
        // Set ToolBar title
        ToolbarHelper::title(Text::_('COM_MIGRATETOJOOMLA'), 'Migrate To Joomla');

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
        $toolbar->customButton('next')
            ->html('<joomla-toolbar-button><button onclick="Joomla.submitbutton(\'migrate.storeFormAndNext\')" '
                . 'class="btn btn-primary"><span class="icon-next" aria-hidden="true"></span>'
                . Text::_('COM_MIGRATETOJOOMLA_NEXT') . '</button></joomla-toolbar-button>');
    }
}
