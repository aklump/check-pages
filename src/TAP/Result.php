<?php

namespace AKlump\CheckPages\TAP;

enum Result: string {

  case NONE = 'none';
  case OK = 'ok';
  case NOT_OK = 'not ok';

}
