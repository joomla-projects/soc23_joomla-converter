<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Event;

use Joomla\CMS\Event\AbstractImmutableEvent;
use Joomla\CMS\Event\Result\ResultAwareInterface;
use Joomla\CMS\Event\Result\ResultTypeMixedAware;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Class for onAjax... events
 *
 * @since  5.0.0
 */
class MigrationtypeEvent extends AbstractImmutableEvent implements ResultAwareInterface
{
    use ResultTypeMixedAware;

    /**
     * Appends data to the result of the event.
     *
     * @param   mixed  $data  What to add to the result.
     *
     * @return  void
     * @since   5.0.0
     */
    public function addResult($data): void
    {
        $this->arguments['result'] = $this->arguments['result'] ?? [];
        $this->arguments['result'][] = $data;
    }

    /**
     * Update the result of the event.
     *
     * @param   mixed  $data  What to add to the result.
     *
     * @return  static
     * @since   5.0.0
     */
    public function updateEventResult($data): static
    {
        $this->arguments['result'] = $data;

        return $this;
    }

    /**
     * Get the event result.
     *
     * @return  mixed
     * @since   5.0.0
     */
    public function getEventResult(): mixed
    {
        return $this->arguments['result'] ?? null;
    }
}
