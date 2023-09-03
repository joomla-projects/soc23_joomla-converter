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
    <h3 class="mt-2"><?php echo Text::_('COM_MIGRATETOJOOMLA_FRAMEWORK_PARAMETERS') ?></h3>

    <div id="migratetojoomla_progresscontainer">
        <ul id="migratetojoomla_listgroup" class="list-group">
        </ul>
    </div>

    <div class="d-flex justify-content-center">

        <button type="button" id="migratetojoomla_startmigrate" class="btn btn-primary"><?php echo Text::_('COM_MIGRATETOJOOMLA_START_MIGRATE') ?></button>
    </div>

</div>