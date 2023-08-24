<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright     (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
/** @var \Joomla\Component\MigrateToJoomla\Administrator\View\check\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('com_migratetojoomla.admin-migratetojoomla')
    ->useScript('keepalive')
    ->useStyle('com_migratetojoomla.migratetojoomla');

$session = Factory::getSession();
$framework = $session->get('framework');
?>
<div id="migratetojoomla" class="p-3">
    <h3 class="mt-2"><?php echo Text::_('COM_MIGRATETOJOOMLA_CHECK_INFORMATION') ?></h3>

    <div id="migratetojoomla_status" class="alert alert-info">
        <h3 class="alert-heading">
            <?= TEXT::_('COM_MIGRATETOJOOMLA_FRAMEWORK_SELECTED') . ' ' . TEXT::_($framework) ?>
            <a href="index.php?option=com_migratetojoomla&view=migrate">
                <?= Text::_('COM_MIGRATETOJOOMLA_CHANGE') ?>
            </a>
        </h3>
    </div>
    <div id="migratetojoomla_status" class="alert alert-<?= $this->ismediaconnection ? 'info' : 'warning' ?>">
        <?php if ($this->ismediaconnection) : ?>
            <h3 class="alert-heading">
                <?= Text::_('COM_MIGRATETOJOOMLA_MEDIA_CONNECTION_SUCCESSFULLY') ?>
            </h3>
        <?php else : ?>
            <h3 class="alert-heading">
                <?= Text::_('COM_MIGRATETOJOOMLA_MEDIA_CONNECTION_UNSUCCESSFULLY') ?>
            </h3>
            <a href="index.php?option=com_migratetojoomla&view=information">
                <?= Text::_('COM_MIGRATETOJOOMLA_CHECK') ?>
            </a>
        <?php endif ?>
    </div>

    <div id="migratetojoomla_status" class="alert alert-<?= $this->isdatabaseconnection ? 'info' : 'warning' ?>">
        <?php if ($this->isdatabaseconnection) : ?>
            <h3 class="alert-heading">
                <?= Text::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_SUCCESSFULLY') ?>
            </h3>
        <?php else : ?>
            <h3 class="alert-heading">
                <?= Text::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_UNSUCCESSFULLY') ?>
            </h3>

            <a href="index.php?option=com_migratetojoomla&view=information">
                <?= Text::_('COM_MIGRATETOJOOMLA_CHECK') ?>
            </a>

        <?php endif ?>
    </div>
</div>