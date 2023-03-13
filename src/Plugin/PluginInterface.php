<?php

namespace AKlump\CheckPages\Plugin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface PluginInterface extends EventSubscriberInterface {

  public static function getPluginId(): string;

}
