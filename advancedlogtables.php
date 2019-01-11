<?php

require_once 'advancedlogtables.civix.php';
use CRM_Advancedlogtables_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function advancedlogtables_civicrm_config(&$config) {
  _advancedlogtables_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function advancedlogtables_civicrm_xmlMenu(&$files) {
  _advancedlogtables_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function advancedlogtables_civicrm_install() {
  _advancedlogtables_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function advancedlogtables_civicrm_postInstall() {
  _advancedlogtables_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function advancedlogtables_civicrm_uninstall() {
  _advancedlogtables_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function advancedlogtables_civicrm_enable() {
  _advancedlogtables_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function advancedlogtables_civicrm_disable() {
  _advancedlogtables_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function advancedlogtables_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _advancedlogtables_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function advancedlogtables_civicrm_managed(&$entities) {
  _advancedlogtables_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function advancedlogtables_civicrm_caseTypes(&$caseTypes) {
  _advancedlogtables_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function advancedlogtables_civicrm_angularModules(&$angularModules) {
  _advancedlogtables_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function advancedlogtables_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _advancedlogtables_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function advancedlogtables_civicrm_entityTypes(&$entityTypes) {
  _advancedlogtables_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Alter log table triggers and tables based on settings
 *
 * This code is based on nz.co.fuzion.innodbtriggers by Eileen McNaughton
 *
 * @TODO: Handle existing indexes
 *
 * @param $logTableSpec
 */
function advancedlogtables_civicrm_alterLogTables(&$logTableSpec) {

  foreach (array_keys($logTableSpec) as $tableName) {
    $logTableSpec[$tableName]['engine'] = Civi::settings()->get('advancedlogtables_storage_engine');
    $logTableSpec[$tableName]['engine_config'] = Civi::settings()->get('advancedlogtables_storage_engine_config');
    if (Civi::settings()->get('advancedlogtables_index_contact')) {
      $contactReferences = CRM_Dedupe_Merger::cidRefs();
      $contactRefsForTable = CRM_Utils_Array::value($tableName, $contactReferences, []);
      foreach ($contactRefsForTable as $fieldName) {
        $logTableSpec[$tableName]['indexes']['index_' . $fieldName] = $fieldName;
      }
    }

    if (Civi::settings()->get('advancedlogtables_index_conn_id')) {
      $logTableSpec[$tableName]['indexes']['index_log_conn_id'] = 'log_conn_id';
    }

    if (Civi::settings()->get('advancedlogtables_index_log_date')) {
      $logTableSpec[$tableName]['indexes']['index_log_date'] = 'log_date';
    }

    if (Civi::settings()->get('advancedlogtables_index_id')) {
      // Check if current table has an "id" column. If so, index it too
      $dsn = DB::parseDSN(CIVICRM_DSN);
      $dbName = $dsn['database'];
      $dao = CRM_Core_DAO::executeQuery(
        "SELECT
          COLUMN_NAME
        FROM
          INFORMATION_SCHEMA.COLUMNS
        WHERE
          TABLE_SCHEMA = '{$dbName}' AND
          TABLE_NAME = '{$tableName}' AND
          COLUMN_NAME = 'id'"
      );
      if ($dao->fetch()) {
        $logTableSpec[$tableName]['indexes']['index_id'] = 'id';
      }
    }
  }
}
