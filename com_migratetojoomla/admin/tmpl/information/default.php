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

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
/** @var \Joomla\Component\Banners\Administrator\View\Banner\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('com_migratetojoomla.admin-migratetojoomla')
    ->useScript('keepalive')
    ->useStyle('com_migratetojoomla.migratetojoomla');

?>
<div id="migratetojoomla" class="p-3">
    <h3 class="mt-2"><?php echo Text::_('COM_MIGRATETOJOOMLA_FRAMEWORK_INFORMATION') ?></h3>
    <form action="<?php echo Route::_('index.php?option=com_migratetojoomla'); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">

        <?php echo $this->form->renderField('mediaoptions'); ?>
        <?php echo $this->form->renderField('livewebsiteurl');  ?>
        <?php echo $this->form->renderField('basedir');  ?>

        <?php echo $this->form->renderField('ftphost');  ?>
        <?php echo $this->form->renderField('ftpport');  ?>
        <?php echo $this->form->renderField('ftpusername');  ?>
        <?php echo $this->form->renderField('ftppassword');  ?>
        <?php echo $this->form->renderField('protocol');  ?>
        <?php echo $this->form->renderField('ftpbasedir');  ?>

        <button type="button" id="migratetojoomlamediaconnection" class="btn btn-primary" onclick="Joomla.submitbutton('information.checkMediaConnection')"><?php echo Text::_('COM_MIGRATETOJOOMLA_CHECK_MEDIA_CONNECTION') ?></button>

        <button type="button" id="migratetojoomladownload" class="btn btn-primary" onclick="Joomla.submitbutton('information.download')"><?php echo Text::_('COM_MIGRATETOJOOMLA_DOWNLOAD_MEDIA') ?></button>

        <br>
        <br>
        <h3 class="mt-2"><?php echo Text::_('COM_MIGRATETOJOOMLA_WORDPRESS_DATABASE_PARAMETERS') ?></h3>
        <br>
        <?php echo $this->form->renderField('dbhostname');  ?>
        <?php echo $this->form->renderField('dbdriver');  ?>
        <?php echo $this->form->renderField('dbport');  ?>
        <?php echo $this->form->renderField('dbname');  ?>
        <?php echo $this->form->renderField('dbusername');  ?>
        <?php echo $this->form->renderField('dbpassword');  ?>
        <?php echo $this->form->renderField('dbtableprefix');  ?>

        <button type="button" id="migratetojoomladatabaseconnection" class="btn btn-primary" onclick="Joomla.submitbutton('information.checkDatabaseConnection')"><?php echo Text::_('COM_MIGRATETOJOOMLA_TEST_DATABASE_CONNECTION') ?></button>
        <button type="button" id="migratetoimportdatabase" class="btn btn-primary" onclick="Joomla.submitbutton('information.importDatabase')"><?php echo "import Database" ?></button>
        <input type="hidden" name="task" value="">
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
