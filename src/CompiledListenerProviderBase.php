<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\Container\ContainerInterface;
use Psr\Event\Dispatcher\EventInterface;
use Psr\Event\Dispatcher\ListenerProviderInterface;

class CompiledListenerProviderBase implements ListenerProviderInterface
{
    /** @var ContainerInterface */
    protected $container;

    // This nested array will be generated by the compiler in a subclass.  It's listed here for reference only.
    // Its structure is an ordered list of array definitions, each of which corresponds to one of the defined
    // entry types in the classes seen in getListenerForEvent().  See each class's getProperties() method for the
    // exact structure.
    /** @var array */
    // protected $listeners = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getListenersForEvent(EventInterface $event): iterable
    {
        /** @var array $listener */
        foreach ($this->listeners as $listener) {
            if ($event instanceof $listener['type']) {
                switch ($listener['entryType']) {
                    case ListenerFunctionEntry::class:
                        yield $listener['listener'];
                        break;
                    case ListenerStaticMethodEntry::class:
                        yield [$listener['class'], $listener['method']];
                        break;
                    case ListenerServiceEntry::class:
                        yield function (EventInterface $event) use ($listener) {
                            $this->container->get($listener['serviceName'])->{$listener['method']}($event);
                        };
                        break;
                }
            }
        }
    }
}
