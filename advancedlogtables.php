<?php

require_once 'advancedlogtables.civix.php';
use CRM_Advancedlogtables_Config as C;
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
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function advancedlogtables_civicrm_install() {
  _advancedlogtables_civix_civicrm_install();
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
 * Alter log table triggers and tables based on settings
 *
 * This code is based on nz.co.fuzion.innodbtriggers by Eileen McNaughton
 *
 * @TODO: Handle existing indexes
 *
 * @param $logTableSpec
 */
function advancedlogtables_civicrm_alterLogTables(&$logTableSpec) {

  // Load exclusion table list
  $pseudovars = C::singleton()->getParams();
  $negated = $pseudovars['negateexclusion'] ?? FALSE;
  $pseudovars['excludedtables'] = empty($pseudovars['excludedtables']) ? [] : $pseudovars['excludedtables'];
  foreach (array_keys($logTableSpec) as $tableName) {
    if (!$negated) {
      if (!in_array($tableName, $pseudovars['excludedtables'])) {
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
      } else {
        unset($logTableSpec[$tableName]);
      }
    } else {
      if (in_array($tableName, $pseudovars['excludedtables'])) {
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
      } else {
        unset($logTableSpec[$tableName]);
      }
    }
  }
}

function advancedlogtables_civicrm_navigationMenu(&$menu) {
  $path = "Administer/System Settings";
  _advancedlogtables_civix_insert_navigation_menu($menu, $path, array(
    'label' => E::ts('Advancedlog Tables'),
    'name' => 'advancedlogtables_config',
    'url' => 'civicrm/admin/advancedlogtables/config?reset=1',
    'permission' => 'administer CiviCRM',
    'operator' => '',
    'separator' => 0,
  ));
  _advancedlogtables_civix_navigationMenu($menu);
}
