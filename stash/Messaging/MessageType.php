<?php

namespace AKlump\Messaging;

interface MessageType {

  const EMERGENCY = 'emergency';

  const ERROR = 'error';

  const SUCCESS = 'success';

  const INFO = 'info';

  const DEBUG = 'debug';

  const TODO = 'todo';
}
