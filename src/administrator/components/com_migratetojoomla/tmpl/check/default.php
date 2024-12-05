<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright     (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
/** @var \Joomla\Component\MigrateToJoomla\Administrator\View\Check\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useStyle('com_migratetojoomla.migratetojoomla');

$app = Factory::getApplication();

$data = $app->getUserState('com_migratetojoomla.parameter', []);
;

$parameterformdata = @$data["frameworkparams"];
$framework = @$app->getUserState('com_migratetojoomla.migrate', [])['framework'];

$datafieldskey =  is_null($parameterformdata) ? [] : array_keys($parameterformdata);

// call createmigratedata plugin method to remove unwanted fields
PluginHelper::importPlugin('migratetojoomla', $framework);

$eventname = "migratetojoomla_createdisplaydata";
$event = AbstractEvent::create(
    $eventname,
    [
        'subject'    => $this,
        'data'       => $datafieldskey
    ]
);
Factory::getApplication()->triggerEvent($eventname, $event);
$importstring = Factory::getSession()->get('migratetojoomla.displayimportstring', []);

// no database migration then change status of database table to 0

if (@$data['databasemigratestatus'] == '0') {
    foreach ($parameterformdata as $key => $field) {
        $parameterformdata[$key] = '0';
    }
}
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
    <div>
        <div>
            <h2 class="alert-heading text-center p-2 font-weight-bold">
                <?= Text::_('COM_MIGRATETOJOOMLA_MIGRATION_INFORMATION') ?>
            </h2>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <?php
                foreach ($datafieldskey as $item) :
                    ?>
                    <ul class="list-group">
                        <?php if (in_array($item, $importstring) && $parameterformdata[$item] == '1') : ?>
                            <li class="list-group-item bg-success text-white m-1"><?php echo ucfirst($item) . " " . TEXT::_('COM_MIGRATETOJOOMLA_WILL_MIGRATE') ?></li>
                        <?php endif ?>
                    </ul>
                <?php endforeach; ?>
            </div>
            <div class="col-sm-6">
                <?php
                foreach ($datafieldskey as $item) :
                    ?>
                    <ul class="list-group">
                        <?php if (in_array($item, $importstring) && $parameterformdata[$item] == '0') : ?>
                            <li class="list-group-item bg-danger text-white m-1"><?php echo ucfirst($item) . " " . TEXT::_('COM_MIGRATETOJOOMLA_WILL_NOT_MIGRATE') ?></li>
                        <?php endif ?>
                    </ul>
                <?php endforeach; ?>
            </div>
        </div>


    </div>
</div>
