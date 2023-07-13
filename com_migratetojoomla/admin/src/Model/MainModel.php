<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Versioning\VersionableModelTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Migratetojoomla Component main Model
 *
 * @since  
 */
class MainModel extends AdminModel
{
    use VersionableModelTrait;

    /**
     * @var    string  The prefix to use with controller messages.
     * @since  3.1
     */
    protected $text_prefix = 'COM_MIGRATETOJOOMLA';

    /**
     * @var    string  The type alias for this content type.
     * @since  3.2
     */
    public $typeAlias = 'com_migratetojoomla.main';

    /**
     * Method to get the form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  bool|\Joomla\CMS\Form\Form  A Form object on success, false on failure
     *
     * @since   
     */
    public function getForm($data = [], $loadData = true)
    {

        // Get the form.
        $form = $this->loadForm('com_migratetojoomla.main', 'main', ['control' => 'jform', 'load_data' => $loadData]);

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
     * @since   
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_migratetojoomla.main', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_migratetojoomla.main', $data);

        return $data;
    }

}
