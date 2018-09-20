<?php namespace Teronis\EventDispatcher;

/**
 * @author Roman Ožana <ozana@omdesign.cz>
 */

class EventDispatcher
{
	private $events;

	public function __construct() {
		$this->events = new stdClass();
	}

    /**
     * Return events object
     */
    public function getEvents(): \stdClass
    {
        return $this->events;
    }

    /**
     * Return listeners
     *
     * @param $event
     * @return mixed
     */
    public function listeners(string $event)
    {
        if (isset(getEvents()->$event)) {
            ksort(getEvents()->$event);
            return call_user_func_array('array_merge', getEvents()->$event);
        }
    }

    /**
     * Add event listener
     *
     * @param string $event
     * @param callable $listener
     * @param int $priority
     */
    public function on(string $event, callable $listener = null, int $priority = 10)
    {
        getEvents()->{$event}[$priority][] = $listener;
    }

    /**
     * Trigger only once.
     *
     * @param $event
     * @param callable $listener
     * @param int $priority
     */
    public function one(string $event, callable $listener, int $priority = 10)
    {
        $once = function () use (&$once, $event, $listener) {
            off($event, $once);
            return call_user_func_array($listener, func_get_args());
        };

        on($event, $once, $priority);
    }

    /**
     * Remove one or all listeners from event.
     *
     * @param $event
     * @param callable $listener
     * @return bool
     */
    public function off(string $event, callable $listener = null): bool
    {
        if (!isset(getEvents()->$event)) {
            return false;
        }

        if ($listener === null) {
            unset(getEvents()->$event);
        } else {
            foreach (getEvents()->$event as $priority => $listeners) {
                if (false !== ($index = array_search($listener, $listeners, true))) {
                    unset(getEvents()->{$event}[$priority][$index]);
                }
            }
        }

        return true;
    }

    /**
     * Trigger events
     *
     * @param string|array $events
     * @param array $args
     * @return array
     */
    public function trigger($events, ...$args): array
    {
        $out = [];
        foreach ((array) $events as $event) {
            foreach ((array) listeners($event) as $listener) {
                if (($out[] = call_user_func_array($listener, $args)) === false) {
                    break;
                }
                // return false ==> stop propagation
            }
        }

        return $out;
    }

    /**
     * Pass variable with all filters.
     *
     * @param string|array $events
     * @param null $value
     * @param array $args
     * @return mixed|null
     * @internal param null $value
     */
    public function filter($events, $value = null, ...$args)
    {
        array_unshift($args, $value);
        foreach ((array) $events as $event) {
            foreach ((array) listeners($event) as $listener) {
                $args[0] = $value = call_user_func_array($listener, $args);
            }
        }
        return $value;
    }

    /**
     * @param $event
     * @param callable $listener
     * @param int $priority
     */
    public function add_filter(string $event, callable $listener, $priority = 10)
    {
        on($event, $listener, $priority);
    }

    /**
     * Ensure that something will be handled
     *
     * @param string $event
     * @param callable $listener
     * @return mixed
     */
    public function ensure(string $event, callable $listener = null)
    {
        if ($listener) {
            on($event, $listener, 0);
        }
        // register default listener

        if ($listeners = listeners($event)) {
            return call_user_func_array(end($listeners), array_slice(func_get_args(), 2));
        }
    }
}
