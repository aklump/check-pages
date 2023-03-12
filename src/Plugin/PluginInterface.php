<?php

namespace AKlump\CheckPages\Plugin;

interface PluginInterface extends \Symfony\Component\EventDispatcher\EventSubscriberInterface {

  public static function getPluginId(): string;
}
