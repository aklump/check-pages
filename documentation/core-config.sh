#!/bin/bash
disabled = "drupal html text mediawiki"
php = $(which php)
lynx = $(which lynx)
not_source_do_not_edit__md = '<!-- Compiled from SOURCE: DO NOT EDIT -->'
README = '../README.md'
CHANGELOG = '../CHANGELOG.md'
pre_hooks = "plugins.php add_test_option.php"
version_file = "../composer.json"
website_dir = '../docs'

