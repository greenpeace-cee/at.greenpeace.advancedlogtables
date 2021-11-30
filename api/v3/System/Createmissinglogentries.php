<?php
use CRM_Advancedlogtables_ExtensionUtil as E;

/**
 * System.Createmissinglogentries API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_system_createmissinglogentries_spec(&$spec) {
}

/**
 * System.Createmissinglogentries API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_system_createmissinglogentries($params) {
  $dsn = DB::parseDSN(CIVICRM_DSN);
  $db = $log_db = $dsn['database'];
  if (defined('CIVICRM_LOGGING_DSN')) {
    $dsn = DB::parseDSN(CIVICRM_LOGGING_DSN);
    $log_db = $dsn['database'];
  }
  $logging = new CRM_Logging_Schema();
  $results = [];
  foreach ($logging->getLogTableSpec() as $table => $spec) {
    // get all columns of the main table. we assume schemas are in sync
    $query = "SELECT COLUMN_NAME
              FROM   INFORMATION_SCHEMA.COLUMNS
              WHERE  TABLE_SCHEMA = '{$db}' AND TABLE_NAME = '{$table}'";
    $dao = CRM_Core_DAO::executeQuery($query);
    $selectColumns = '';
    $insertColumns = '';
    $hasId = FALSE;
    while ($dao->fetch()) {
      $selectColumns .= "main.{$dao->COLUMN_NAME}, ";
      $insertColumns .= "{$dao->COLUMN_NAME}, ";
      if ($dao->COLUMN_NAME == 'id') {
        $hasId = TRUE;
      }
    }
    // only process tables that have an "id" column
    if (!$hasId) {
      continue;
    }
    $insertColumns .= 'log_conn_id, log_user_id, log_action, log_date';
    $selectColumns = "{$selectColumns} @uniqueID AS log_conn_id, @civicrm_user_id AS log_user_id, 'Initialization' AS log_action, NOW() as log_date";
    $fromJoinWhere = "FROM {$table} main LEFT JOIN `{$log_db}`.log_{$table} log ON log.id = main.id WHERE log.id IS NULL";
    $query = "INSERT INTO `{$log_db}`.log_{$table} ({$insertColumns})
              SELECT {$selectColumns} {$fromJoinWhere}
              LIMIT 1000";

    // for some reason $dao->N doesn't work for INSERT INTO ... SELECT queries,
    // so we have to count missing rows manually and loop until none are left,
    // inserting 1000 rows at a time
    $countQuery = "SELECT COUNT(DISTINCT main.id) $fromJoinWhere";
    $count = CRM_Core_DAO::singleValueQuery($countQuery);
    if ($count > 0) {
      $results[$table] = $count;
    }
    for ($i = 0; $i < ceil($count / 1000); $i++) {
      CRM_Core_DAO::executeQuery($query, [], TRUE, NULL, FALSE, FALSE);
    }
  }
  return civicrm_api3_create_success($results);
}
