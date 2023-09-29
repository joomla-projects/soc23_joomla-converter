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
$wa->useScript('keepalive')
    ->useScript('com_migratetojoomla.admin-migratetojoomla')
    ->useStyle('com_migratetojoomla.migratetojoomla');

$app = Factory::getApplication();
$session = Factory::getSession();
$data = $session->get('parameterformdata', []);
$parameterformdata = $data["frameworkparams"];
// echo '<pre>';
// echo var_dump($parameterformdata);
// die;
$framework = $app->getUserState('com_migratetojoomla.migrate', [])['framework'];

$datafieldskey = array_keys($parameterformdata);

?>
<div id="migratetojoomla" class="p-3">
    <h3 class="mt-2"><?php echo Text::_('COM_MIGRATETOJOOMLA_CHECK_INFORMATION') ?></h3>

    <div id="migratetojoomla_status" class="alert alert-info">
        <h3 class="alert-heading d-flex justify-content-between">
            <?= TEXT::_('COM_MIGRATETOJOOMLA_FRAMEWORK_SELECTED') . ' ' . TEXT::_($framework) ?>
            <a href="index.php?option=com_migratetojoomla&view=migrate">
                <?= Text::_('COM_MIGRATETOJOOMLA_CHANGE') ?>
            </a>
        </h3>
    </div>

    <?php if (array_key_exists('mediamigratestatus', $data) && $data['mediamigratestatus'] === "1") : ?>
        <div id="migratetojoomla_status" class="alert alert-<?= $this->ismediaconnection ? 'info' : 'warning' ?>">
            <?php if ($this->ismediaconnection) : ?>
                <h3 class="alert-heading">
                    <?= Text::_('COM_MIGRATETOJOOMLA_MEDIA_CONNECTION_SUCCESSFULLY') ?>
                </h3>
            <?php else : ?>
                <h3 class="alert-heading d-flex justify-content-between">
                    <?= Text::_('COM_MIGRATETOJOOMLA_MEDIA_CONNECTION_UNSUCCESSFULLY') ?>
                    <a href="index.php?option=com_migratetojoomla&view=information">
                        <?= Text::_('COM_MIGRATETOJOOMLA_CHECK') ?>
                    </a>
                </h3>

            <?php endif ?>
        </div>
    <?php else : ?>
        <div id="migratetojoomla_status" class="alert alert-warning">

            <h3 class="alert-heading d-flex justify-content-between">
                <?= Text::_('COM_MIGRATETOJOOMLA_NO_MEDIA') ?>
                <a href="index.php?option=com_migratetojoomla&view=parameter">
                    <?= Text::_('COM_MIGRATETOJOOMLA_CHANGE') ?>
                </a>
            </h3>
        </div>
    <?php endif ?>

    <?php if (array_key_exists('databasemigratestatus', $data) && $data['databasemigratestatus'] === "1") : ?>
        <div id="migratetojoomla_status" class="alert alert-<?= $this->isdatabaseconnection ? 'info' : 'warning' ?>">
            <?php if ($this->isdatabaseconnection) : ?>
                <h3 class="alert-heading">
                    <?= Text::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_SUCCESSFULLY') ?>
                </h3>
            <?php else : ?>
                <h3 class="alert-heading d-flex justify-content-between">
                    <?= Text::_('COM_MIGRATETOJOOMLA_DATABASE_CONNECTION_UNSUCCESSFULLY') ?>
                    <a href="index.php?option=com_migratetojoomla&view=information">
                        <?= Text::_('COM_MIGRATETOJOOMLA_CHECK') ?>
                    </a>
                </h3>
            <?php endif ?>
        </div>
    <?php else : ?>
        <div id="migratetojoomla_status" class="alert alert-warning">
            <h3 class="alert-heading d-flex justify-content-between">
                <?= Text::_('COM_MIGRATETOJOOMLA_NO_DATABASE') ?>
                <a href="index.php?option=com_migratetojoomla&view=parameter">
                    <?= Text::_('COM_MIGRATETOJOOMLA_CHANGE') ?>
                </a>
            </h3>
        </div>
    <?php endif ?>

    <div class="row">
        <div class="col-sm-6">
            <h3 class="alert-heading d-flex justify-content-between p-1">
                <?= Text::_('COM_MIGRATETOJOOMLA_MIGRATE_DATA_DETAILS') ?>
            </h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th scope="col"><?= Text::_('COM_MIGRATETOJOOMLA_SR_NO') ?></th>
                        <th scope="col"><?= Text::_('COM_MIGRATETOJOOMLA_DATA_NAME') ?></th>
                        <th scope="col"><?= Text::_('COM_MIGRATETOJOOMLA_WILL_MIGRATE') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $n = 1;
                    foreach ($datafieldskey as $item) :
                    ?>
                        <?php if ($parameterformdata[$item] == '1') : ?>
                            <tr>
                                <th scope="row"><?php echo $n;
                                                $n += 1; ?></th>
                                <td><?php echo $item; ?></td>
                                <td>
                                    <?php echo "Yes" ?>
                                </td>
                            </tr>
                        <?php endif ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="col-sm-6">
            <h3 class="alert-heading d-flex justify-content-between p-1">
                <?= Text::_('COM_MIGRATETOJOOMLA_NO_MIGRATE_DATA_DETAILS') ?>
            </h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th scope="col"><?= Text::_('COM_MIGRATETOJOOMLA_SR_NO') ?></th>
                        <th scope="col"><?= Text::_('COM_MIGRATETOJOOMLA_DATA_NAME') ?></th>
                        <th scope="col"><?= Text::_('COM_MIGRATETOJOOMLA_WILL_MIGRATE') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $n = 1;
                    foreach ($datafieldskey as $item) :
                    ?>
                        <?php if ($parameterformdata[$item] == '0') : ?>
                            <tr>
                                <th scope="row"><?php echo $n;
                                                $n += 1; ?></th>
                                <td><?php echo $item; ?></td>
                                <td>
                                    <?php echo "No" ?>
                                </td>
                            </tr>
                        <?php endif ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>


</div>