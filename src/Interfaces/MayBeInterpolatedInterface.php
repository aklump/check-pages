<?php

namespace AKlump\CheckPages\Interfaces;

interface MayBeInterpolatedInterface {

  /**
   * Retrieves the interpolation keys for a given scope.
   *
   * @param int $scope The scope for which to retrieve the interpolation keys.
   *
   * @return array An array containing the interpolation keys for the given scope.
   *
   * @see \AKlump\CheckPages\Interfaces\ScopeInterface::SUITE
   * @see \AKlump\CheckPages\Interfaces\ScopeInterface::TEST
   */
  public function getInterpolationKeys(int $scope): array;
}
