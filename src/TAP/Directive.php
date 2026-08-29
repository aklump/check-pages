<?php

namespace AKlump\CheckPages\TAP;

enum Directive: string {

  case NONE = '';
  case TODO = '# TODO';
  case SKIP = '# skip';

}
