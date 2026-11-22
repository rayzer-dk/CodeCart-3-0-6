<?php
/**
 * @package     OpenCart
 * @author      Daniel Kerr
 * @copyright   Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license     https://opensource.org/licenses/GPL-3.0
 * @link        https://www.opencart.com
 */

/**
 * Event class
 *
 * Event System Userguide
 * https://github.com/opencart/opencart/wiki/Events-(script-notifications)-2.2.x.x
 */
class Event {
    protected $registry;
    protected $data = array();
    protected $processed = array();
    protected $refresh = false;
    // null until the first trigger; otherwise a plain boolean per request.
    protected $diagnosticsActive = null;

    /**
     * Constructor
     *
     * @param object $registry
     */
    public function __construct($registry) {
        $this->registry = $registry;
    }

    /**
     * Register an event listener.
     *
     * Plain triggers are prefix-matched without regex, as in OpenCart 3. Wildcard patterns are evaluated
     * only when needed and the resolved listener list is cached per event for the
     * lifetime of the request. This preserves OpenCart 3 early-return semantics.
     *
     * @param string $trigger
     * @param Action $action
     * @param int    $priority
     */
    public function register($trigger, Action $action, $priority = 0, $eventId = 0) {
        $trigger = (string)$trigger;

        $this->data[] = array(
            'trigger'  => $trigger,
            'action'   => $action,
            'priority' => (int)$priority,
            'event_id' => (int)$eventId,
            'wildcard' => (strpos($trigger, '*') !== false || strpos($trigger, '?') !== false)
        );

        $this->refresh = true;
    }

    /**
     * Trigger an event.
     *
     * @param string $event
     * @param array  $args
     */
    public function trigger($event, array $args = array()) {
        $event = (string)$event;

        if ($this->refresh) {
            $sort_order = array();

            foreach ($this->data as $key => $value) {
                $sort_order[$key] = $value['priority'];
            }

            if ($sort_order) {
                array_multisort($sort_order, SORT_ASC, SORT_NUMERIC, $this->data);
            }

            $this->processed = array();
            $this->refresh = false;
        }

        if (!isset($this->processed[$event])) {
            $this->processed[$event] = array();

            foreach ($this->data as $value) {
                if (!$value['wildcard']) {
                    if (strpos($event, $value['trigger']) === 0) {
                        $this->processed[$event][] = $value;
                    }
                    continue;
                }

                $pattern = '/^' . str_replace(array('\\*', '\\?'), array('.*', '.'), preg_quote($value['trigger'], '/')) . '/';

                if (preg_match($pattern, $event)) {
                    $this->processed[$event][] = $value;
                }
            }
        }

        // One cheap filesystem check per request. Off by default; no arguments are logged.
        if ($this->diagnosticsActive === null) {
            $this->diagnosticsActive = false;
            if (defined('DIR_LOGS') && is_file(rtrim(DIR_LOGS, '/\\') . '/codecart-event-trace.enabled')) {
                require_once DIR_SYSTEM . 'library/codecart/event_diagnostics.php';
                $this->diagnosticsActive = CodeCartEventDiagnostics::expiration() > 0;
            }
        }
        foreach ($this->processed[$event] as $value) {
            $capture = $this->diagnosticsActive && $value['event_id'] > 0;
            $started = $capture ? microtime(true) : 0.0;
            try {
                $result = $value['action']->execute($this->registry, $args);
            } catch (\Throwable $exception) {
                if ($capture) {
                    CodeCartEventDiagnostics::record($value['event_id'], 'error', microtime(true) - $started, $exception);
                }
                throw $exception;
            }

            if ($capture) {
                CodeCartEventDiagnostics::record($value['event_id'], $result instanceof \Throwable ? 'error' : 'ok', microtime(true) - $started, $result instanceof \Throwable ? $result : null);
            }
            if (!is_null($result) && !($result instanceof Exception)) {
                return $result;
            }
        }
    }

    /**
     * Unregister a listener.
     *
     * @param string $trigger
     * @param string $route
     */
    public function unregister($trigger, $route) {
        foreach ($this->data as $key => $value) {
            if ($trigger == $value['trigger'] && $value['action']->getId() == $route) {
                unset($this->data[$key]);
                $this->refresh = true;
            }
        }
    }

    /**
     * Remove all listeners for a trigger.
     *
     * @param string $trigger
     */
    public function clear($trigger) {
        foreach ($this->data as $key => $value) {
            if ($trigger == $value['trigger']) {
                unset($this->data[$key]);
                $this->refresh = true;
            }
        }
    }
}
