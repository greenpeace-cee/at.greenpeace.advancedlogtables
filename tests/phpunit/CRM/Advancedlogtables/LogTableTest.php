<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Test execution of hook_civicrm_alterLogTables and application of settings
 *
 * Some of these tests mirror existing core tests; we still need them to verify
 * that extension settings are applied correctly.
 *
 * Note: DDL queries generally force a commit in MySQL, so TransactionalInterface
 * does not work as intended here and tests should either clean up after themselves
 * or should be written to work regardless of previous state.
 *
 * @group headless
 */
class CRM_Advancedlogtables_LogTableTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    $this->resetLogging();
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  private function resetLogging() {
    // disable logging
    Civi::settings()->set('logging', FALSE);
    // remove test log table if it exists
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS log_civicrm_acl');
    // remove cached schema spec
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
  }

  /**
   * Test change of storage engine
   */
  public function testEngineChange() {
    // enable logging
    Civi::settings()->set('logging', TRUE);

    // verify default extension settings are applied
    $log_table = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE log_civicrm_acl');
    $log_table->fetch();
    $defaultEngine = Civi::settings()->get('advancedlogtables_storage_engine');
    $defaultConfig = Civi::settings()->get('advancedlogtables_storage_engine_config');
    $this->assertRegexp(
      "/{$defaultConfig}/",
      $log_table->Create_Table,
      "Table storage engine config should be set to '{$defaultConfig}'"
    );
    $this->assertRegexp(
      "/ENGINE={$defaultEngine}/",
      $log_table->Create_Table,
      "Table storage engine should be '{$defaultEngine}'"
    );

    // change engine extension setting to MyISAM
    Civi::settings()->set('advancedlogtables_storage_engine', 'MyISAM');

    // update log table schema
    $this->callApiSuccess('System', 'updatelogtables');

    // verify table engine was changed
    $log_table = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE log_civicrm_acl');
    $log_table->fetch();
    $this->assertRegexp(
      '/ENGINE=MyISAM/',
      $log_table->Create_Table,
      'Table storage engine should be MyISAM'
    );
  }

  /**
   * Test change of storage engine config, CiviCRM 5.15+ only
   */
  public function testEngineConfigChange() {
    if (version_compare(CRM_Utils_System::version(), '5.15', '<')) {
      $this->markTestSkipped(
        'Storage engine config changes are supported by CiviCRM 5.15+ only'
      );
    }
    // change engine extension setting to InnoDB
    Civi::settings()->set('advancedlogtables_storage_engine', 'InnoDB');

    // start with engine config extension setting to ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=4
    Civi::settings()->set('advancedlogtables_storage_engine_config', 'ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=4');

    // enable logging
    Civi::settings()->set('logging', TRUE);

    $log_table = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE log_civicrm_acl');
    $log_table->fetch();
    $this->assertRegexp(
      '/KEY_BLOCK_SIZE=4/',
      $log_table->Create_Table,
      'Table storage engine config should include "KEY_BLOCK_SIZE=4"'
    );

    // change engine extension setting to InnoDB
    Civi::settings()->set('advancedlogtables_storage_engine', 'InnoDB');

    // change engine config extension setting to ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8
    Civi::settings()->set('advancedlogtables_storage_engine_config', 'ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8');

    // update log table schema
    $this->callApiSuccess('System', 'updatelogtables', [
      'updateChangedEngineConfig' => TRUE,
    ]);

    // verify table engine was changed
    $log_table = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE log_civicrm_acl');
    $log_table->fetch();
    $this->assertRegexp(
      '/KEY_BLOCK_SIZE=8/',
      $log_table->Create_Table,
      'Table storage engine config should include "KEY_BLOCK_SIZE=8"'
    );
  }

  /**
   * Test creation/non-creation of index based on setting
   */
  public function testIndexCreation() {
    // enable index on log_date column
    Civi::settings()->set('advancedlogtables_index_log_date', TRUE);
    // enable logging
    Civi::settings()->set('logging', TRUE);

    $log_table = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE log_civicrm_acl');
    $log_table->fetch();
    $this->assertRegexp(
      '/KEY `index_log_date` \(`log_date`\)/',
      $log_table->Create_Table,
      'Table should have index on log_date column'
    );

    $this->resetLogging();

    // disable index on log_date column
    Civi::settings()->set('advancedlogtables_index_log_date', FALSE);
    // enable logging
    Civi::settings()->set('logging', TRUE);

    $log_table = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE log_civicrm_acl');
    $log_table->fetch();
    $this->assertNotRegExp(
      '/KEY `index_log_date` \(`log_date`\)/',
      $log_table->Create_Table,
      'Table should not have index on log_date column'
    );
  }

}
