#!/usr/bin/env bash

##
 # Install Check Pages as a Stand Along composer project
 #
 # @param string Optional, Check Pages version constraint e.g. "0.19.1", "@dev"
 #
 # @return 1 If installation fails
 # @return 0 If inst
 ##

# ========= Configuration =========
install_path="$HOME/.check-pages"
install_version="${1:-^0}"

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
! mkdir -p "$install_path" && error "Directory \"$install_path\" already exists." && exit 1
! cd "$install_path" && error 'Cannot create stand-alone directory.' && exit 1
status "Directory \"$install_path\" created."

# Composer installation
! printf '{"name":"aklump/check-pages-project","type":"project","require":{"aklump/check-pages":"%s"},"config":{"allow-plugins":{"wikimedia/composer-merge-plugin":true}}}' "$install_version" > composer.json && echo '' && exit 1
! composer install && error 'Cannot install dependencies.' && exit 1

command_path='bin/checkpages'
mkdir ./bin || exit 1
cd ./bin || exit 1
ln -s '../vendor/bin/checkpages' "$(basename "$command_path")" || exit 1

# User feedback
suggest 'Optional: To make checkpages accessible from anywhere, add the following line'
suggest 'to your shell startup file (e.g., ~/.bashrc, ~/.zshrc, or equivalent):'
echo "export PATH=\"${install_path/$HOME/\$HOME}/$(dirname "$command_path"):\$PATH\""
suggest 'After adding the line, either restart your terminal or source the updated file.'
status "Thank you for installing Check Pages, happy testing!"
status "Get help: $install_path/$command_path"
