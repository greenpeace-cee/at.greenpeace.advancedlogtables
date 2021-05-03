<?php

class CRM_Advancedlogtables_Config {

  private static $_singleton = NULL;
  private $params = [];

  static function &singleton() {
    if (self::$_singleton === NULL) {
      // first, attempt to get configuration object from cache
      $cache = CRM_Utils_Cache::singleton();
      self::$_singleton = $cache->get('CRM_Advancedlogtables_Config');
      // if not in cache, fire off config construction
      if (!self::$_singleton) {
        self::$_singleton = new CRM_Advancedlogtables_Config();
        self::$_singleton->_initialize();
        $cache->set('CRM_Advancedlogtables_Config', self::$_singleton);
      }
      else {
        self::$_singleton->_initialize();
      }
    }
    return self::$_singleton;
  }

  private function _initialize() {
    $this->params['tables'] = self::fetchRelatedTables();
    $this->params['excludedtables'] = Civi::settings()->get('advancedlogtables_excludetables');
    $this->params['negateexclusion'] = Civi::settings()->get('advancedlogtables_negate_exclusion');
  }

  private function fetchRelatedTables() {
    $includedTables = [];
    // The following are following what is done in https://lab.civicrm.org/dev/core/-/blob/master/CRM/Logging/Schema.php#L117
    $dao = new CRM_Contact_DAO_Contact();
    $civiDBName = $dao->_database;

    $dao = CRM_Core_DAO::executeQuery("
      SELECT TABLE_NAME
      FROM   INFORMATION_SCHEMA.TABLES
      WHERE  TABLE_SCHEMA = '{$civiDBName}'
      AND    TABLE_TYPE = 'BASE TABLE'
      AND    TABLE_NAME LIKE 'civicrm_%'
      ");
    while ($dao->fetch()) {
      $includedTables[$dao->TABLE_NAME] = $dao->TABLE_NAME;
    }
    // do not log temp import, cache, menu and log tables
    $includedTables = preg_grep('/^civicrm_import_job_/', $includedTables, PREG_GREP_INVERT);
    $includedTables = preg_grep('/_cache$/', $includedTables, PREG_GREP_INVERT);
    $includedTables = preg_grep('/_log/', $includedTables, PREG_GREP_INVERT);
    $includedTables = preg_grep('/^civicrm_queue_/', $includedTables, PREG_GREP_INVERT);
    //CRM-14672
    $includedTables = preg_grep('/^civicrm_menu/', $includedTables, PREG_GREP_INVERT);
    $includedTables = preg_grep('/_temp_/', $includedTables, PREG_GREP_INVERT);
    // CRM-18178
    $includedTables = preg_grep('/_bak$/', $includedTables, PREG_GREP_INVERT);
    $includedTables = preg_grep('/_backup$/', $includedTables, PREG_GREP_INVERT);
    // dev/core#462
    $includedTables = preg_grep('/^civicrm_tmp_/', $includedTables, PREG_GREP_INVERT);

    // do not log civicrm_mailing_event* tables, CRM-12300
    $includedTables = preg_grep('/^civicrm_mailing_event_/', $includedTables, PREG_GREP_INVERT);

    // dev/core#1762 Don't log subscription_history
    $includedTables = preg_grep('/^civicrm_subscription_history/', $includedTables, PREG_GREP_INVERT);

    // Convert values to keys
    $includedTables = array_combine($includedTables, $includedTables);

    return $includedTables;
  }

  public function getParams($param = NULL) {
    if (isset($param))
      return isset($this->params[$param]) ? $this->params[$param] : NULL;
    else
      return $this->params;
  }

}
