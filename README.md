RezzzaCommandBusBundle
======================

Integration of the [**CommandBus**](http://github.com/rezzza/command-bus) library
into Symfony2.

Installation
------------

Require [`rezzza/command-bus-bundle`](https://packagist.org/packages/rezzza/command-bus-bundle)
to your `composer.json` file:


```json
{
    "require": {
        "rezzza/command-bus-bundle": "1.*@dev"
    }
}
```

Register the bundle in `app/AppKernel.php`:

```php
// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        new Rezzza\CommandBusBundle\RezzzaCommandBusBundle()
    );
}
```

Usage
-----

```php

$bus = $container->get('rezzza_command_bus.command_bus.synchronous'); // synchronous is the name you given to your bus in configuration.
$bus->handle(new FooCommand());
```

Bus
---

Direct & SncRedis are currently supported.

Commands
--------

Commands must inherit from `Rezzza\CommandBus\Domain\CommandInterface`.

Register a command handler:

```xml
<service id="...." class="....">
    <tag name="rezzza_command_bus.command_handler" command="FQCN\MyFirstActionCommand" /> <!-- when handle this command, method `myFirstAction` will be handled. -->
    <tag name="rezzza_command_bus.command_handler" command="FQCN\MySecondActionCommand" method="myMethod" />
</service>
```

Events
------

Events:

    - on_consumer_response
    - pre_handle_command

Register a listener:

```xml
<service id="service_id" class="Foo">
    <tag name="rezzza_command_bus.event_listener" event="pre_handle_command" method="onPreHandleCommand" />
    <tag name="rezzza_command_bus.event_listener" event="on_consumer_response" method="onConsumerResponse" />
</service>

```

Fail Strategy
-------------

See [command-bus documentation](https://github.com/rezzza/command-bus#fail-strategies).

Console Command
---------------

A Symfony console command is provided in this bundle

```
$ app/console rezzza:command_bus:consume "CommandToConsume"

Options available:

 --consumer             Which consumer should we use ? (default: "default")
 --iteration-limit (-i) Limit of iterations, -1 for infinite.. (default: -1)
 --time-limit           During how many time this command will listen to the queue. (default: 60)
 --usleep               Micro seconds (default: 100000)
 --lock                 Only one command processing ?
```


Configuration
-------------

```yaml
rezzza_command_bus:
    buses:
      synchronous: direct
      asynchronous:
        snc_redis:
            client: default # snc redis client.
            read_block_timeout: 1 # see blpop documentation
            consumers:
                default:
                    bus: synchronous
                    fail_strategy:
                        retry_then_fail: # When the command fail, it uses this strategy.
                        # you could use too requeue, none, service.
                            attempts: 10
                            requeue_on_fail: true
    handlers: # Do you want to use handlers provided in this bundle ?
        retry:  synchronous  # If you used retry_then_fail strategy, this handler is linked to Retry commands.
        failed: synchronous # If you used retry_then_fail strategy, this handler is linked to Failed commands.
```
