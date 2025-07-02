<?php

namespace AKlump\CheckPages\Tests\TestingMixins;

/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
/** @var \AKlump\CheckPages\Parts\Runner $runner */
/** @var array $mixin_config */

$container->set('defined_vars', (object) get_defined_vars());
