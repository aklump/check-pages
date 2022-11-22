<?php

namespace AKlump\Messaging;

interface MessageType {

  const ERROR = 'error';

  const SUCCESS = 'success';

  const INFO = 'info';

  const DEBUG = 'debug';
}
