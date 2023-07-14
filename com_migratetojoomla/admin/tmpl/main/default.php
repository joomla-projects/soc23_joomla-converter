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


?>
<style>
    #migratetojoomla{
        background-color: #F8FAFC;
    }
</style>
<div id="migratetojoomla" class="p-2">
    <h3 class="mt-2"><?php echo Text::_('COM_MIGRATETOJOOMLA_WORDPRESS_WEBSITE_PARAMETERS')?></h3>
    <form action="<?php echo Route::_('index.php?option=com_migratetojoomla'); ?>"
    method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">

	<?php echo $this->form->renderField('livewebsiteurl');  ?>

	<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('main.checkconnection')">Check Connection</button>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
</div>
