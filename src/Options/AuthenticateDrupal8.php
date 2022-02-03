<?php

namespace AKlump\CheckPages\Options;

final class AuthenticateDrupal8 extends AuthenticateDrupalBase {

  /**
   * AuthenticateDrupal8 constructor.
   *
   * @param string $path_to_users_login_data
   *   The resolved path to the JSON or YAML file for the users.
   * @param string $absolute_login_url
   *   The absolute URL to the login form.
   * @param string $form_selector
   *   A CSS selector to find the login form on in the DOM of the
   *   $absolute_login_url.
   * @param string $form_id
   *   The value of the hidden input name=form_id.
   */
  public function __construct(
    string $path_to_users_login_data,
    string $absolute_login_url,
    string $form_selector = 'form.user-login-form',
    string $form_id = 'user_login_form'
  ) {
    parent::__construct($path_to_users_login_data, $absolute_login_url, $form_selector, $form_id);
  }

  public function login(UserInterface $user) {
    parent::login($user);

    // Sniff the page for the user ID.
    $body = strval($this->getResponse()->getBody());
    if (preg_match('/"currentPath"\:"user.+?(\d+)"/', $body, $matches)
      || preg_match('/html\-\-user\-\-(\d+)\.html\.twig/', $body, $matches)
      || preg_match('/page\-\-user\-\-(\d+)\.html\.twig/', $body, $matches)) {
      $user->setId(intval($matches[1]));
    }

    // TODO Figure out how to get the email address.
  }

}
