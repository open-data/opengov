<?php

namespace Drupal\og_ext_user\AccessChecks;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Listens to the user access checks.
 */
class UserAccessChecks implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route being checked.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $roles = $account->getRoles();
    if (count($roles) > 1) {
      return AccessResultAllowed::allowedIf(in_array('administrator', $roles) === TRUE);
  }
    return AccessResult::neutral();
  }
}
