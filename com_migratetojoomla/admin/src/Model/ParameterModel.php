<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Model;

use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Migratetojoomla Component Parameter Model
 *
 * @since  1.0
 */
class ParameterModel extends AdminModel
{
    /**
     * @var    string  The type alias for this content type.
     * @since  1.0
     */
    public $typeAlias = 'com_migratetojoomla.parameter';

    /**
     * Method to get the form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  bool|\Joomla\CMS\Form\Form  A Form object on success, false on failure
     *
     * @since   1.0
     */
    public function getForm($data = [], $loadData = true)
    {
        $framework = @Factory::getApplication()->getUserState('com_migratetojoomla.migrate')['framework'];
        // Get the form.
        $form = $this->loadForm('com_migratetojoomla.parameter', 'parameter', ['control' => 'jform', 'load_data' => $loadData]);

        PluginHelper::importPlugin('migratetojoomla');

        $event = AbstractEvent::create(
            'onContentPrepareFormmigrate',
            [
                'subject'    => $this,
                'formname'   => 'com_migratetojoomla.parameter',
                'form'       => $form,
                'framework'  => $framework
            ]
        );

        Factory::getApplication()->triggerEvent('onContentPrepareFormmigrate', $event);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.0
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_migratetojoomla.parameter', []);
        return $data;
    }
}
