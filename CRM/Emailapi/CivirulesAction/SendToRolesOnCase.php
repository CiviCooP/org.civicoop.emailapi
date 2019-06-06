<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

class CRM_Emailapi_CivirulesAction_SendToRolesOnCase extends CRM_Civirules_Action {

  protected static $alreadySend = array();

  /**
   * Process the action
   *
   * @param CRM_Civirules_TriggerData_TriggerData $triggerData
   * @access public
   */
  public function processAction(CRM_Civirules_TriggerData_TriggerData $triggerData) {
    $actionParams = $this->getActionParameters();
    $case = $triggerData->getEntityData('Case');
    $actionParams['case_id'] = $case['id'];

    // Find the related contact(s)
    $related_contacts = $this->getRelatedContacts($case['id'], $actionParams['relationship_type']);
    foreach($related_contacts as $related_contact_id) {
      // Make sure an e-mail is only send once for a case.
      if (isset(self::$alreadySend[$case['id']][$related_contact_id])) {
        continue;
      }
      $params = $actionParams;
      $params['contact_id'] = $related_contact_id;

      // change e-mailaddress if other location type is used, falling back on primary if set
      $alternativeAddress = $this->checkAlternativeAddress($params, $related_contact_id);
      if ($alternativeAddress) {
        $params['alternative_receiver_address'] = $alternativeAddress;
      }
      $extra_data = (array) $triggerData;
      $params['extra_data'] = $extra_data["\0CRM_Civirules_TriggerData_TriggerData\0entity_data"];
      //execute the action
      civicrm_api3('Email', 'send', $params);
      
      self::$alreadySend[$case['id']][$related_contact_id] = true;
    }
  }

  protected function getRelatedContacts($case_id, $relationship_type) {
    $dir = 'b';
    if (stripos($relationship_type, 'b_') === 0) {
      $dir = 'a';
    }
    $relationship_type_id = substr($relationship_type, 2);
    $sql = "SELECT contact_id_{$dir} AS contact_id
        FROM civicrm_relationship r
        INNER JOIN civicrm_contact c ON c.id = r.contact_id_{$dir} 
        WHERE is_active = 1 AND (start_date IS NULL OR start_date <= CURRENT_DATE()) AND (end_date IS NULL OR end_date >= CURRENT_DATE())
        AND c.is_deleted = 0
        AND case_id = %1";
    $sqlParams[1] = array($case_id, 'Integer');
    if ($relationship_type_id) {
      $sql .= " AND relationship_type_id = %2";
      $sqlParams[2] = array($relationship_type_id, 'Integer');
    }
    $dao = CRM_Core_DAO::executeQuery($sql,$sqlParams);
    $contacts = array();
    if ($dao) {
      while($dao->fetch()) {
        if (!in_array($dao->contact_id, $contacts)) {
          $contacts[] = $dao->contact_id;
        }
      }
    }
    return $contacts;
  }

  /**
   * Method to check if an alternative address is required. This is the case if:
   * - the location type is set, then the e-mailaddress of the specific location type (if found) is to be used.
   * - if alternative receiver address is set, that is to be used
   *
   * @param array $actionParameters
   * @param int $contactId
   * @return string|bool
   */
  private function checkAlternativeAddress($actionParameters, $contactId) {
    if (isset($actionParameters['location_type_id']) && !empty($actionParameters['location_type_id'])) {
      try {
        $alternateAddress = civicrm_api3('Email', 'getvalue', array(
          'return' => 'email',
          'contact_id' => $contactId,
          'location_type_id' => $actionParameters['location_type_id'],
          'options' => array('limit' => 1, 'sort' => 'id DESC'),
        ));
        return (string) $alternateAddress;
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return FALSE;
  }

  public static function getRelationshipTypes() {
    $return = CRM_Emailapi_CivirulesAction_SendToRelatedContact::getRelationshipTypes('a_b');
    return $return;
  }

  /**
   * Returns a redirect url to extra data input from the user after adding a action
   *
   * Return false if you do not need extra data input
   *
   * @param int $ruleActionId
   * @return bool|string
   * $access public
   */
  public function getExtraDataInputUrl($ruleActionId) {
    return CRM_Utils_System::url('civicrm/civirules/actions/emailapi_rolesoncase', 'rule_action_id='.$ruleActionId);
  }

  /**
   * Returns a user friendly text explaining the condition params
   * e.g. 'Older than 65'
   *
   * @return string
   * @access public
   */
  public function userFriendlyConditionParams() {
    $template = 'unknown template';
    $params = $this->getActionParameters();
    $version = CRM_Core_BAO_Domain::version();
    // Compatibility with CiviCRM > 4.3
    if($version >= 4.4) {
      $messageTemplates = new CRM_Core_DAO_MessageTemplate();
    } else {
      $messageTemplates = new CRM_Core_DAO_MessageTemplates();
    }
    $messageTemplates->id = $params['template_id'];
    $messageTemplates->is_active = true;
    if ($messageTemplates->find(TRUE)) {
      $template = $messageTemplates->msg_title;
    }
    if (isset($params['location_type_id']) && !empty($params['location_type_id'])) {
      try {
        $locationText = 'location type ' . civicrm_api3('LocationType', 'getvalue', array(
            'return' => 'display_name',
            'id' => $params['location_type_id'],
          )) . ' with primary e-mailaddress as fall back';
      }
      catch (CiviCRM_API3_Exception $ex) {
        $locationText = 'location type ' . $params['location_type_id'];
      }
    }
    else {
      $locationText = "primary e-mailaddress";
    }
    $to = '';
    $relationship_types = self::getRelationshipTypes();
    if (isset($relationship_types[$params['relationship_type']])) {
      $to .= " with role: '".$relationship_types[$params['relationship_type']]."'";
    }

    $cc = "";
    if (!empty($params['cc'])) {
      $cc = ts(' and cc to %1', array(1=>$params['cc']));
    }
    $bcc = "";
    if (!empty($params['bcc'])) {
      $bcc = ts(' and bcc to %1', array(1=>$params['bcc']));
    }
    return ts('Send e-mail from "%1 (%2 using %3)" with Template "%4" to %5 %6 %7', array(
      1=>$params['from_name'],
      2=>$params['from_email'],
      3=>$locationText,
      4=>$template,
      5 => $to,
      6 => $cc,
      7 => $bcc
    ));
  }

  /**
   * This function validates whether this action works with the selected trigger.
   *
   * This function could be overriden in child classes to provide additional validation
   * whether an action is possible in the current setup.
   *
   * @param CRM_Civirules_Trigger $trigger
   * @param CRM_Civirules_BAO_Rule $rule
   * @return bool
   */
  public function doesWorkWithTrigger(CRM_Civirules_Trigger $trigger, CRM_Civirules_BAO_Rule $rule) {
    if ($trigger->doesProvideEntity('Case')) {
      return true;
    }
    return false;
  }
}