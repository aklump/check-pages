#!/usr/bin/env bash

##
 # Install Check Pages as a Stand Along composer project
 #
 # @return 1 If installation fails.
 # @return 0 On success.
 ##

# ========= Configuration =========
install_dir_basename='check_pages'

# ========= Utility functions =========
function error() {
  local message="$1"
  printf "\e[41m\e[97m🚫 %-80s\e[0m\n" "$message"
}
function status() {
  local message="$1"
  printf "\e[42m\e[97m✅ %-80s\e[0m\n" "$message"
}
function suggest() {
  local message="$1"
  printf "\e[48;5;208m\e[97m⚠️ %-80s\e[0m\n" "$message"
}
function check_composer() {
  command -v composer &> /dev/null
  return $?
}

# ========= Execute installation =========
! check_composer && error "Composer is missing; installation failed." && exit 1

# Create destination directory
! mkdir "$install_dir_basename" && error "Directory \"$install_dir_basename\" already exists." && exit 1
! cd "$install_dir_basename" && error 'Cannot create stand-alone directory.' && exit 1
status "Directory \"$install_dir_basename\" created."

# Composer installation
! echo '{"name":"aklump/check-pages-project","type":"project","require":{"aklump/check-pages":"^0.23.0"},"config":{"allow-plugins":{"wikimedia/composer-merge-plugin":true}}}' > composer.json && echo '' && exit 1
! composer install && error 'Cannot install dependencies.' && exit 1

# User feedback
echo
suggest "Optional, add this to your RC file"
suggest "export PATH=\"$PWD/vendor/bin/check_pages:\$PATH\""
echo
status "Next step, get help: "
status "./$install_dir_basename/vendor/bin/check_pages"
