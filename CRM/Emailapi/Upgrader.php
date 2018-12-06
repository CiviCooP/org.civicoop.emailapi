<?php

/**
 * Collection of upgrade steps
 */
class CRM_Emailapi_Upgrader extends CRM_Emailapi_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Install CiviRule Action Send E-mail
   */
  public function install() {
    if(civicrm_api3('Extension', 'get', ['key' => 'civirules', 'status' => 'installed'])['count']){
      $this->executeSqlFile('sql/insertSendEmailAction.sql');
    }

  }

  /**
   * remove managed entity
   */
  public function upgrade_1001() {
  $this->ctx->log->info('Applying update 1001 (remove managed entity');
  if (CRM_Core_DAO::checkTableExists('civicrm_managed')) {
    $query = 'DELETE FROM civicrm_managed WHERE module = %1 AND entity_type = %2';
    CRM_Core_DAO::executeQuery($query, array(
      1 => array('org.civicoop.emailapi', 'String'),
      2 => array('CiviRuleAction', 'String'),
    ));
    }
  return TRUE;
  }

  /**
   * re-add send email action if required
   */
  public function upgrade_1002() {
    if(civicrm_api3('Extension', 'get', ['key' => 'civirules', 'status' => 'installed'])['count']){
      $this->ctx->log->info('Applying update 1002');
      $select = "SELECT COUNT(*) FROM civirule_action WHERE class_name = %1";
      $count = CRM_Core_DAO::singleValueQuery($select, array(1 => array('CRM_Emailapi_CivirulesAction', 'String')));
      if ($count == 0) {
        $this->executeSqlFile('sql/insertSendEmailAction.sql');
      }
    }
    return TRUE;
  }

  /**
   * update class name of the send e-mail action and add the send e-mail to related contact
   */
  public function upgrade_1003() {
    CRM_Core_DAO::executeQuery("UPDATE civirule_action SET class_name = 'CRM_Emailapi_CivirulesAction_Send' WHERE `name` = 'emailapi_send'");
    CRM_Core_DAO::executeQuery("INSERT INTO civirule_action (name, label, class_name, is_active)
      VALUES('emailapi_send_relationship', 'Send E-mail to a related contact', 'CRM_Emailapi_CivirulesAction_SendToRelatedContact', 1);"
    );
    return true;
  }

  /**
   * Upgrader to update old civicrm_queue_items so they reflect the new class names.
   */
  public function upgrade_1004() {
    CRM_Core_DAO::executeQuery("UPDATE `civicrm_queue_item` SET data = REPLACE(data, '\"class_name\";s:28:\"CRM_Emailapi_CivirulesAction\"', '\"class_name\";s:33:\"CRM_Emailapi_CivirulesAction_Send\"')  WHERE data like '%\"class_name\";s:28:\"CRM_Emailapi_CivirulesAction\"%'");
    CRM_Core_DAO::executeQuery("UPDATE `civicrm_queue_item` SET data = REPLACE(data, 'O:28:\"CRM_Emailapi_CivirulesAction\"', 'O:33:\"CRM_Emailapi_CivirulesAction_Send\"') WHERE data like '%O:28:\"CRM_Emailapi_CivirulesAction\"%' ");
    return true;
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled
   *
  public function uninstall() {
   $this->executeSqlFile('sql/myuninstall.sql');
  }

  /**
   * Example: Run a simple query when a module is enabled
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a simple query when a module is disabled
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a couple simple queries
   *
   * @return TRUE on success
   * @throws Exception
   *
  public function upgrade_4200() {
    $this->ctx->log->info('Applying update 4200');
    CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
    CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
    return TRUE;
  } // */


  /**
   * Example: Run an external SQL script
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
