<?php

namespace Acquia\Blt\Robo\Commands\Local;

use Acquia\Blt\Robo\BltTasks;

/**
 * Defines commands in the "local:*" namespace.
 */
class LocalSyncCommand extends BltTasks {

  /**
   * Install dependencies, builds docroot, installs Drupal.
   *
   * @command local:setup
   */
  public function setup() {
    $status_code = $this->invokeCommands([
      'setup',
    ]);
    return $status_code;
  }

  /**
   * @command local:drupal:install
   */
  public function installDrupal() {
    $status_code = $this->invokeCommands([
      'setup:drupal:install',
    ]);
    return $status_code;
  }

  /**
   * Refreshes local environment from upstream testing database.
   *
   * @command local:refresh
   */
  public function refresh() {
    $status_code = $this->invokeCommands([
      'setup:build',
    ]);
    if ($status_code) {
      return $status_code;
    }
    $this->sync();
    $this->update();
  }

  /**
   * Synchronize local environment from remote (remote --> local).
   *
   * @command local:sync
   */
  public function sync() {

  }

  /**
   * Update current database to reflect the state of the Drupal file system;
   *
   * @command local:update
   */
  public function update() {

  }

}
