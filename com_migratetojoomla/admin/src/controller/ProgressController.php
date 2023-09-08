<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Migrate controller class.
 *
 * @since  1.0
 */
class ProgressController extends BaseController
{
	/**
	 * @var string  Contain current migration field
	 * 
	 * @since 1.0
	 */
	public $currentmigration = "";

	public function ajax()
	{

		if (!Session::checkToken('get')) {
			$this->app->setHeader('status', 403, true);
			$this->app->sendHeaders();
			echo Text::_('JINVALID_TOKEN_NOTICE');
			$this->app->close();
		}

		$this->currentmigration = ""; // set ajax response 

		$update[] = ['name' => 'kaushik'];

		echo json_encode($update);
		$this->app->close();
	}

	public function kaushik()
	{
		echo 'kaushik Vishwakarma5467788';
		die;
	}

	/**
	 * Method to call specific plugin methods
	 * 
	 * @since 1.0
	 */
	public function callpluginmethod()
	{

		if ($this->currentmigration == "media") {
			// calling media plugin method
			PluginHelper::importPlugin('migratetojoomla', 'mediadownload');

			$event = AbstractEvent::create(
				'migratetojoomla_mediadownload',
				[
					'subject'    => $this,
					'formname'   => 'com_migratetojoomla.parameter',
				]
			);

			Factory::getApplication()->triggerEvent('migratetojoomla_mediadownload', $event);
		} else {
			// calling framework specific plugin method for database migration

			$framework = Factory::getApplication()->getUserState('com_migratetojoomla.migrate')['framework'];

			// import framwork plugin

			PluginHelper::importPlugin('migratetojoomla', $framework);

			$eventsuffix = preg_replace("data", '', $this->currentmigration);

			$eventname = "migratetojoomala_" . $eventsuffix;

			$event = AbstractEvent::create(
				$eventname,
				[
					'subject'    => $this,
					'formname'   => 'com_migratetojoomla.parameter',
				]
			);

			Factory::getApplication()->triggerEvent($eventname, $event);
		}
	}
}
