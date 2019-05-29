<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;


/**
 * Test execution of hook_civicrm_alterLogTables and application of settings
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
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }


  /**
   * Test change of storage engine
   */
  public function testEngineChange() {
    // enable logging
    $this->callApiSuccess('Setting', 'create', [
      'logging' => 1,
    ]);

    // change engine extension setting to MyISAM
    $this->callApiSuccess('Setting', 'create', [
      'advancedlogtables_storage_engine' => 'MyISAM',
    ]);

    // update log table schema
    $this->callApiSuccess('System', 'updatelogtables', []);

    // verify table engine was changed
    $log_table = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE log_civicrm_acl');
    $log_table->fetch();
    $this->assertRegexp(
      '/ENGINE=MyISAM/',
      $log_table->Create_Table,
      'Table storage engine should be MyISAM'
    );
  }

}
