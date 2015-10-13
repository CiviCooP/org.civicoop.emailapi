<?php
/**
 * Class for CiviRule Condition Emailapi
 *
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */
class CRM_Emailapi_CivirulesAction extends CRM_CivirulesActions_Generic_Api {

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
    $parameters['contact_id'] = $triggerData->getContactId();

    return $parameters;
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

    $to = ts('the contact');
    if (!empty($params['alternative_receiver_address'])) {
      $to = $params['alternative_receiver_address'];
    }

    return ts('Send e-mail from "%1 (%2)" with template "%3" to %4', array(
        1=>$params['from_name'],
        2=>$params['from_email'],
        3=>$template,
        4 => $to,
    ));
  }
}