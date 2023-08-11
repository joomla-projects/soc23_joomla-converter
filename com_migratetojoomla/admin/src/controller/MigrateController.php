<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Controller;

use Joomla\Database\DatabaseDriver;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Versioning\VersionableControllerTrait;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\MigrateHelper;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\HttpHelper;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\FilesystemHelper;
use Joomla\Component\MigrateToJoomla\Administrator\Helper\FtpHelper;
use Joomla\CMS\Filesystem\path;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Migrate controller class.
 *
 * @since  1.0
 */
class MigrateController extends FormController
{
    use VersionableControllerTrait;

    
    
}
