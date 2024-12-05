<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright     (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
/** @var \Joomla\Component\MigrateToJoomla\Administrator\View\Information\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useStyle('com_migratetojoomla.migratetojoomla');

$framework = @ucfirst(Factory::getApplication()->getUserState('com_migratetojoomla.migrate', [])['framework'] . ' ');
?>
<div id="migratetojoomla" class="p-3">
    <form action="<?php echo Route::_('index.php?option=com_migratetojoomla'); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">

        <h3 class="mt-2"><?php echo $framework . Text::_('COM_MIGRATETOJOOMLA_MEDIA_INFORMATION') ?></h3>

        <?php foreach ($this->form->getFieldsets() as $fieldset) : ?>
            <?php echo $this->form->renderFieldset($fieldset->name); ?>
        <?php endforeach; ?>

        <button type="button" id="migratetojoomlamediaconnection" class="btn btn-primary" onclick="Joomla.submitbutton('information.checkMediaConnection')"><?php echo Text::_('COM_MIGRATETOJOOMLA_CHECK_MEDIA_CONNECTION') ?></button>

        <button type="button" id="migratetojoomladatabaseconnection" class="btn btn-primary" onclick="Joomla.submitbutton('information.checkDatabaseConnection')"><?php echo Text::_('COM_MIGRATETOJOOMLA_TEST_DATABASE_CONNECTION') ?></button>
        <input type="hidden" name="task" value="">
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
