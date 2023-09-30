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
/** @var \Joomla\Component\MigrateToJoomla\Administrator\View\Migrate\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();

$wa->useScript('com_migratetojoomla.admin-migratetojoomla')
    ->useScript('keepalive')
    ->useStyle('com_migratetojoomla.migratetojoomla');

$data = Factory::getApplication()->getUserState('com_migratetojoomla.parameter', []);

?>
<div id="migratetojoomla" class="p-4">
    <h3 class="mt-2 mb-4"><?php echo Text::_('COM_MIGRATETOJOOMLA_MIGRATE_PROGRESS') ?></h3>
    <div id="migratetojoomla_progresscontainer">
        <ul id="migratetojoomla_listgroup" class="list-group">
        </ul>
    </div>

    <div class="d-flex justify-content-center">

        <button type="button" id="migratetojoomla_startmigrate" class="btn btn-primary mt-5"><?php echo Text::_('COM_MIGRATETOJOOMLA_START_MIGRATE') ?></button>
    </div>

    <div id="migratetojoomla_progress" style="display:none;">
        <div id="progresspercent" class="mt-3 text-center h3 ">start</div>
        <div class="progress mt-1 ">
            <div class="progress-bar progress-bar-striped bg-success progress-bar-animated" id="migratetojoomlabar" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>

    <!-- log view -->
    <div id="migratetojoomla_log" style="display:none;">
        <?php echo "this is log view" ?>;
        <textarea class="form-control" rows="20" id="migratetojoomlalog">

        </textarea>
    </div>

</div>