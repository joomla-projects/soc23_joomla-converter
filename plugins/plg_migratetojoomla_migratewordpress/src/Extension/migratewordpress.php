<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.contact
 *
 * @copyright   (C) 2014 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\MigrateToJoomla\MediaDownload\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\EventInterface;
use Joomla\CMS\Form\Form;
use ReflectionClass;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * MediaDownload Plugin
 *
 * @since  3.2
 */
final class MigrateWordpress extends CMSPlugin
{
    /**
     * The form event.
     *
     * @param   Form      $form  The form
     * @param   stdClass  $data  The data
     *
     * @return   boolean
     *
     * @since   4.0.0
     */
    public function onContentPrepareForm(EventInterface $event)
    {
        $form = $event->getArgument('0');
        $data = $event->getArgument('1');

        $name = $form->getName();

        $allowedForms = [
            'com_migratetojoomla.parameter'
        ];

        if (!\in_array($name, $allowedForms)) {
            return;
        }

        Form::addFormPath(JPATH_PLUGINS . '/' .'migratetojoomla' . '/' . 'migratewordpress'. '/forms');
        // $form->loadFile('migratewordpress.xml', false);
        $form->loadFile('migratewordpress', false);
        return true;
    }

    /**
     *
     * @param   Form      $form The form
     * @param   \stdClass $data The data
     *
     * @return  boolean|\stdClass
     *
     * @since   4.0.0
     */
    public function enhanceForm(Form $form)
    {
        // Load XML file from "parent" plugin
        $path = dirname((new ReflectionClass(static::class))->getFileName());

        if (is_file($path . '/forms/migratewordpress.xml')) {
            $form->loadFile($path . '/forms/migratewordpress.xml');
        }
    }

}
