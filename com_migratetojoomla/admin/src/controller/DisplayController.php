<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright     (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Controller\BaseController;

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
    protected $default_view = 'migrate';

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return  BaseController|boolean  This object to support chaining.
     *
     * @since   1.5
     */
    public function display($cachable = false, $urlparams = [])
    {
        // Get the document object.
        $document = $this->app->getDocument();

        $view   = $this->input->get('view', 'migrate');
        $layout = $this->input->get('layout', 'migrate');

        if ($view = $this->getView($view, $document->getType())) {
            $view->setLayout($layout);

            // Push document object into the view.
            $view->document = $document;
            $view->display();
        }

        return $this;
    }
}
