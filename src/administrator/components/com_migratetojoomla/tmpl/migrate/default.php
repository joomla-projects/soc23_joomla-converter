<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright     (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

/** @var \Joomla\Component\MigrateToJoomla\Administrator\View\Migrate\HtmlView $this */
?>
<div id="migratetojoomla" class="p-4">
    <h1><?php echo Text::_('COM_MIGRATETOJOOMLA_VIEW_MIGRATE_TITLE'); ?></h1>
    <p><?php echo Text::_('COM_MIGRATETOJOOMLA_VIEW_MIGRATE_DESCRIPTION'); ?></p>
    <?php foreach ($this->types as $type) :?>
        <a href="<?php echo Route::_('index.php?option=com_migratetojoomla&view=information&type=' . $type->name); ?>" class="card">
            <div class="card-body">
                <h5 class="card-title"><?php echo Text::_($type->title); ?></h5>
                <p class="card-text"><?php echo Text::_($type->desc); ?></p>
            </div>
        </a>
    <?php endforeach; ?>
</div>
