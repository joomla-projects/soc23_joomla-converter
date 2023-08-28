<?php
/**
 * @package   Migratetojoomla
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * This file passes parameters to the migratetojoomla.js script using Joomla's script options API
 *
 * @var  $this  Joomla\Component\MigratetoJoomla\Administrator\View\Progress\HtmlView
 */

$escapedBaseURL = addslashes(Uri::base());


// Initialization
$this->document->addScriptOptions('migratetojoomla.progress.importstring', $this->importstring);


// Push language strings to Javascript
Text::script('COM_AKEEBABACKUP_BACKUP_TEXT_LASTRESPONSE');
;
