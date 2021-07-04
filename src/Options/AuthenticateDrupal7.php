<?php

namespace AKlump\CheckPages\Options;

final class AuthenticateDrupal7 extends AuthenticateDrupalBase {

  /**
   * AuthenticateDrupal8 constructor.
   *
   * @param string $path_to_users_file
   *   The resolved path to the JSON or YAML file for the users.
   * @param string $absolute_login_url
   *   The absolute URL to the login form.
   * @param string $form_id
   *   The value of the hidden input name=form_id.
   * @param string $form_selector
   *   A CSS selector to find the login form on in the DOM of the
   *   $absolute_login_url.
   */
  public function __construct(
    string $path_to_users_file,
    string $absolute_login_url,
    string $form_id = 'user_login',
    string $form_selector = 'form[action="/user/login"]'
  ) {
    parent::__construct($path_to_users_file, $absolute_login_url, $form_id, $form_selector);
  }

}
