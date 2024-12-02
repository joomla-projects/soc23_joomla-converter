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
/** @var \Joomla\Component\MigrateToJoomla\Administrator\View\Log\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useStyle('com_migratetojoomla.migratetojoomla');


$data = Factory::getApplication()->getSession()->get('migratetojoomla.logwrite', []);
?>
<div id="migratetojoomla" class="p-3">
    <div id="migratetojoomla_log">
        <div class="row">
            <div class="col-6">
                <h3 class="p-2 w-75"><?php echo Text::_('COM_MIGRATETOJOOMLA_MIGRATION_LOG') ?></h3>
            </div>
            <div class="col-6 d-flex justify-content-end">
                <form action="<?php echo Route::_('index.php?option=com_migratetojoomla'); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
                    <button type="button" id="migratetojoomlamediaconnection" class="btn btn-primary" onclick="Joomla.submitbutton('log.download')"><?php echo Text::_('COM_MIGRATETOJOOMLA_DOWNLOAD_COMPLETE_LOG') ?></button>
                    <input type="hidden" name="task" value="">
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
            </div>
        </div>
        <?php
        foreach ($data['success'] as $item) :
            ?>
            <ul class="list-group">
                <li class="list-group-item bg-success text-white m-1"><?php echo $item ?></li>
            </ul>
        <?php endforeach; ?>
        <hr />
        <?php
        foreach ($data['error'] as $item) :
            ?>
            <ul class="list-group">
                <?php if (strpos($item, '0') == false) : ?>
                    <li class="list-group-item bg-danger text-white m-1"><?php echo $item ?></li>
                <?php endif ?>
            </ul>
        <?php endforeach; ?>
    </div>

</div>
