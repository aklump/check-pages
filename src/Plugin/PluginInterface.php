<?php

namespace AKlump\CheckPages\Plugin;

interface PluginInterface extends \Symfony\Component\EventDispatcher\EventSubscriberInterface {

  public function getPluginId(): string;
}
