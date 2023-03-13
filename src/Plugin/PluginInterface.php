<?php

namespace AKlump\CheckPages\Plugin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface PluginInterface extends EventSubscriberInterface {

  public static function getPluginId(): string;

  /**
   * @param $context
   *   Such as an event to analyze and determine if this plugin should be
   *   applied to that context.
   *
   * @return bool
   *   False if the plugin should be skipped for the given context.
   */
//  public static function doesApply($context): bool;
  // TODO Once more plugins have been upgraded, make this part of the interface?
}
