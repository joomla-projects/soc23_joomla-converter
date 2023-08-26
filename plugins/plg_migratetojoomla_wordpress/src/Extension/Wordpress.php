<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.contact
 *
 * @copyright   (C) 2014 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\MigrateToJoomla\Wordpress\Extension;

use Hoa\Event\Test\Unit\Event;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\EventInterface;
use Joomla\CMS\Form\Form;
use ReflectionClass;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Factory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * MediaDownload Plugin
 *
 * @since  3.2
 */

final class Wordpress extends CMSPlugin implements SubscriberInterface
{

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   4.3.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepareFormmigrate' => 'onContentPrepareForm',
            'onContentPrepareDatamigrate' =>'onContentPrepareData'
        ];
    }

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
        $form = $event->getArgument('form');
        $formName = $event->getArgument('formname');

        if ($this->_name !== $event->getArgument('framework')) {
            return true;
        }

        $allowedForms = [
            'com_migratetojoomla.parameter'
        ];

        if (!in_array($formName, $allowedForms, true)) {
            return true;
        }

        Form::addFormPath(JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/forms');

        $form->loadFile('wordpress', false);
        return true;
    }

    /**
     * @param   Event  $event  The event we are handling
     *
     * @return  void
     *
     * @throws  Exception
     * @since   4.0.0
     */
    public function onContentPrepareData(EventInterface $event)
    {
        /**
         * @var   string|null        $context  The context for the data
         * @var   array|object|null  $data     An object or array containing the data for the form.
         */
        $context = $event->getArgument('context');
        $data = $event->getArgument('data');

        if (!\in_array($context, ['com_migratetojoomla.migrate'])) {
            return;
        }
        return true;
    }
}
