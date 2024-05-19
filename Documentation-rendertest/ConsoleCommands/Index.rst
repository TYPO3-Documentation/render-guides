..  include:: /Includes.rst.txt
..  _console_commands:

================
Console commands
================

Single commands
===============

..  console:command:: cache:flush
    :json: command.json
    :script: vendor/bin/typo3
    :exclude-option: help, quiet, verbose, version, ansi, no-ansi, no-interaction
    :no-help:
    :noindex:

..  console:command:: language:update
    :json: command.json
    :include-option: skip-extension
    :noindex:

..  console:command:: setup
    :json: command.json
    :script: bin/typo3
    :noindex:


Commands in namespace cache
===========================

..  console:command-list:: cache
    :json: command.json
    :script: vendor/bin/typo3
    :exclude-option: help, quiet, verbose, version, ansi, no-ansi, no-interaction
    :noindex:

Global commands
===============

..  console:command-list:: _global
    :json: command.json
    :script: bin/typo3
    :exclude-command: completion, help
    :exclude-option: help, quiet, verbose, version, ansi, no-ansi, no-interaction
    :noindex:

All commands
============

..  console:command-list::
    :json: command.json
    :show-hidden:
