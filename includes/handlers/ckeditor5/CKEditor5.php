<?php

namespace AKlump\CheckPages\Handlers;

class CKEditor5 implements HandlerInterface {

  public static function getId(): string {
    return 'ckeditor5';
  }

  public static function getSubscribedEvents() {
    return [];
  }
}
