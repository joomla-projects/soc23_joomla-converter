<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MigrateToJoomla\Administrator\Event;

use Joomla\Event\AbstractEvent;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Class for Content event.
 * Example:
 *  new ContentPrepareEvent('onEventName', ['context' => 'com_example.example', 'subject' => $contentObject, 'params' => $params, 'page' => $pageNum]);
 *
 * @since  5.0.0
 */
class MigrationStatusEvent extends AbstractEvent
{
    protected $lastID;
    protected $status;
    protected $error;

    public function getLastID(): int
    {
        return (int) $this->lastID;
    }

    public function setLastID(int $lastID)
    {
        $this->lastID = $lastID;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(int $status)
    {
        $this->status = $status;
    }

    public function setError($error)
    {
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * Setter for the subject argument.
     *
     * @param   object  $value  The value to set
     *
     * @return  object
     *
     * @since  5.0.0
     */
    protected function onSetSubject(object $value): object
    {
        return $value;
    }

    /**
     * Setter for the params argument.
     *
     * @param   Registry  $value  The value to set
     *
     * @return  Registry
     *
     * @since  5.0.0
     */
    protected function onSetParams($value): Registry
    {
        // This is for b/c compatibility, because some extensions pass a mixed types
        if (!$value instanceof Registry) {
            $value = new Registry($value);

            // @TODO: In 6.0 throw an exception
            @trigger_error(
                \sprintf('The "params" attribute for the event "%s" must be type of Registry. In 6.0 it will throw an exception', $this->getName()),
                E_USER_DEPRECATED
            );
        }

        return $value;
    }

    /**
     * Setter for the page argument.
     *
     * @param   ?int  $value  The value to set
     *
     * @return  ?int
     *
     * @since  5.0.0
     */
    protected function onSetPage(?int $value): ?int
    {
        return $value;
    }

    /**
     * Getter for the item argument.
     *
     * @return  object
     *
     * @since  5.0.0
     */
    public function getItem(): object
    {
        return $this->arguments['subject'];
    }

    /**
     * Getter for the item argument.
     *
     * @return  Registry
     *
     * @since  5.0.0
     */
    public function getParams(): Registry
    {
        return $this->arguments['params'];
    }

    /**
     * Getter for the page argument.
     *
     * @return  ?int
     *
     * @since  5.0.0
     */
    public function getPage(): ?int
    {
        return $this->arguments['page'];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // TODO: Implement offsetSet() method.
    }

    public function offsetUnset(mixed $offset): void
    {
        // TODO: Implement offsetUnset() method.
    }
}
