<?php
/**
 * Class for CiviRule Condition Emailapi
 *
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */
class CRM_Emailapi_CivirulesAction_Send extends CRM_CivirulesActions_Generic_Api {

  /**
   * Method to get the api entity to process in this CiviRule action
   *
   * @access protected
   * @abstract
   */
  protected function getApiEntity() {
    return 'Email';
  }

  /**
   * Method to get the api action to process in this CiviRule action
   *
   * @access protected
   * @abstract
   */
  protected function getApiAction() {
    return 'send';
  }

  /**
   * Returns an array with parameters used for processing an action
   *
   * @param array $parameters
   * @param CRM_Civirules_TriggerData_TriggerData $rtiggerData
   * @return array
   * @access protected
   */
  protected function alterApiParameters($parameters, CRM_Civirules_TriggerData_TriggerData $triggerData) {
    //this method could be overridden in subclasses to alter parameters to meet certain criteria
    $contactId = $triggerData->getContactId();
    $parameters['contact_id'] = $contactId;
    $actionParameters = $this->getActionParameters();
    // change e-mailaddress if other location type is used, falling back on primary if set
    $alternativeAddress = $this->checkAlternativeAddress($actionParameters, $contactId);
    if ($alternativeAddress) {
      $parameters['alternative_receiver_address'] = $alternativeAddress;
    }
    if (!empty($actionParameters['file_on_case'])) {
      $case = $triggerData->getEntityData('Case');
      $parameters['case_id'] = $case['id'];
    }
		if (!empty($actionParameters['cc'])) {
      $parameters['cc'] = $actionParameters['cc'];
    }
		if (!empty($actionParameters['bcc'])) {
      $parameters['bcc'] = $actionParameters['bcc'];
    }
    $extra_data = (array) $triggerData;
    $parameters['extra_data'] = $extra_data["\0CRM_Civirules_TriggerData_TriggerData\0entity_data"];
    return $parameters;
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
    if (isset($actionParameters['alternate_receiver_address']) && !empty($actionParameters['alternate_receiver_address'])) {
      return (string) $actionParameters['alternate_receiver_address'];
    }
    return FALSE;
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
    return CRM_Utils_System::url('civicrm/civirules/actions/emailapi', 'rule_action_id='.$ruleActionId);
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
    $to = ts('the contact');
    if (!empty($params['alternative_receiver_address'])) {
      $to = $params['alternative_receiver_address'];
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
}