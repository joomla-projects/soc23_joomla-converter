<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright     (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
/** @var \Joomla\Component\MigrateToJoomla\Administrator\View\Parameter\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useStyle('com_migratetojoomla.migratetojoomla');

$framework = @Factory::getApplication()->getUserState('com_migratetojoomla.migrate', [])['framework'];

$lang = Factory::getLanguage();
$lang->load('plg_migratetojoomla_' . $framework, JPATH_ADMINISTRATOR);

?>
<div id="migratetojoomla" class="p-3">
    <h3 class="mt-2"><?php echo @ucfirst($framework) . ' ' . Text::_('COM_MIGRATETOJOOMLA_FRAMEWORK_PARAMETERS') ?></h3>
    <form action="<?php echo Route::_('index.php?option=com_migratetojoomla'); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
        <?php echo $this->form->renderField('mediamigratestatus'); ?>
        <?php echo $this->form->renderField('databasemigratestatus'); ?>

        <hr />
        <?php foreach ($this->form->getFieldsets('frameworkparams') as $name => $fieldSet) : ?>
            <?php foreach ($this->form->getFieldset($name) as $field) : ?>
                <?php echo $field->renderField(); ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <input type="hidden" name="task" value="">
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
