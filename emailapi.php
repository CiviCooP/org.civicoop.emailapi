<?php

require_once 'emailapi.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function emailapi_civicrm_config(&$config) {
  _emailapi_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function emailapi_civicrm_xmlMenu(&$files) {
  _emailapi_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function emailapi_civicrm_install() {
  _emailapi_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function emailapi_civicrm_uninstall() {
  _emailapi_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function emailapi_civicrm_enable() {
  _emailapi_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function emailapi_civicrm_disable() {
  _emailapi_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function emailapi_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _emailapi_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function emailapi_civicrm_managed(&$entities) {
  _emailapi_civix_civicrm_managed($entities);

  if (_emailapi_is_civirules_installed()) {
    $select = "SELECT COUNT(*) FROM civirule_action WHERE `name` = 'emailapi_send'";
    $count = CRM_Core_DAO::singleValueQuery($select);
    if ($count == 0) {
      CRM_Core_DAO::executeQuery("INSERT INTO civirule_action (name, label, class_name, is_active) VALUES('emailapi_send', 'Send E-mail', 'CRM_Emailapi_CivirulesAction_Send', 1);");
    }

    $select = "SELECT COUNT(*) FROM civirule_action WHERE `name` = 'emailapi_send_relationship'";
    $count = CRM_Core_DAO::singleValueQuery($select);
    if ($count == 0) {
      CRM_Core_DAO::executeQuery("INSERT INTO civirule_action (name, label, class_name, is_active) VALUES('emailapi_send_relationship', 'Send E-mail to a related contact', 'CRM_Emailapi_CivirulesAction_SendToRelatedContact', 1);");
    }
  }
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function emailapi_civicrm_caseTypes(&$caseTypes) {
  _emailapi_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function emailapi_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _emailapi_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function _emailapi_is_civirules_installed() {
  if (civicrm_api3('Extension', 'get', ['key' => 'civirules', 'status' => 'installed'])['count']) {
    return true;
  } elseif (civicrm_api3('Extension', 'get', ['key' => 'org.civicoop.civirules', 'status' => 'installed'])['count']) {
    return true;
  }
  return false;
}
