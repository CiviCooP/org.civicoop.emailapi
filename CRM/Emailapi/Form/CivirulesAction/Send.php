<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Emailapi_Form_CivirulesAction_Send extends CRM_Core_Form {

  protected $ruleActionId = false;

  protected $ruleAction;

  protected $rule;

  protected $action;

  protected $triggerClass;

  protected $hasCase = false;

  /**
   * Overridden parent method to do pre-form building processing
   *
   * @throws Exception when action or rule action not found
   * @access public
   */
  public function preProcess() {
    $this->ruleActionId = CRM_Utils_Request::retrieve('rule_action_id', 'Integer');
    $this->ruleAction = new CRM_Civirules_BAO_RuleAction();
    $this->action = new CRM_Civirules_BAO_Action();
    $this->rule = new CRM_Civirules_BAO_Rule();
    $this->ruleAction->id = $this->ruleActionId;
    if ($this->ruleAction->find(TRUE)) {
      $this->action->id = $this->ruleAction->action_id;
      if (!$this->action->find(TRUE)) {
        throw new Exception('CiviRules Could not find action with id '.$this->ruleAction->action_id);
      }
    } else {
      throw new Exception('CiviRules Could not find rule action with id '.$this->ruleActionId);
    }

    $this->rule->id = $this->ruleAction->rule_id;
    if (!$this->rule->find(TRUE)) {
      throw new Exception('Civirules could not find rule');
    }

    $this->triggerClass = CRM_Civirules_BAO_Trigger::getTriggerObjectByTriggerId($this->rule->trigger_id, TRUE);
    $this->triggerClass->setTriggerId($this->rule->trigger_id);
    $providedEntities = $this->triggerClass->getProvidedEntities();
    if (isset($providedEntities['Case'])) {
      $this->hasCase = TRUE;
    }

    parent::preProcess();
  }

  /**
   * Method to get message templates
   *
   * @return array
   * @access protected
   */

  protected function getMessageTemplates() {
    $return = array('' => ts('-- please select --'));
    try {
      $messageTemplates = civicrm_api3('MessageTemplate', 'get', array(
        'return' => array("id", "msg_title"),
        'is_active' => 1,
        'workflow_id' => array('IS NULL' => 1),
        'options' => array('limit' => 0, 'sort' => "msg_title"),
      ));
      foreach ($messageTemplates['values'] as $templateId => $template) {
        $return[$templateId] = $template['msg_title'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return $return;
  }

  /**
   * Method to get location types
   *
   * @return array
   * @access protected
   */

  protected function getLocationTypes() {
    $return = array('' => ts('-- please select --'));
    try {
      $locationTypes = civicrm_api3('LocationType', 'get', array(
        'return' => array("id", "display_name"),
        'is_active' => 1,
        'options' => array('limit' => 0, 'sort' => "display_name"),
      ));
      foreach ($locationTypes['values'] as $locationTypeId => $locationType) {
        $return[$locationTypeId] = $locationType['display_name'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return $return;
  }

  function buildQuickForm() {

    $this->setFormTitle();
		$this->registerRule('emailList', 'callback', 'emailList', 'CRM_Utils_Rule');
    $this->add('hidden', 'rule_action_id');
    $this->add('text', 'from_name', ts('From Name'), TRUE);
    $this->add('text', 'from_email', ts('From Email'), TRUE);
    $this->addRule("from_email", ts('Email is not valid.'), 'email');
    $this->add('checkbox','alternative_receiver', ts('Send to Alternative Email Address'));
    $this->add('text', 'alternative_receiver_address', ts('Alternative Email Address'));
    $this->addRule("alternative_receiver_address", ts('Email is not valid.'), 'email');
		$this->add('text', 'cc', ts('Cc to'));
    $this->addRule("cc", ts('Email is not valid.'), 'emailList');
		$this->add('text', 'bcc', ts('Bcc to'));
    $this->addRule("bcc", ts('Email is not valid.'), 'emailList');
    $this->add('select', 'template_id', ts('Message Template'), $this->getMessageTemplates(), TRUE);
    $this->add('select', 'location_type_id', ts('Location Type (if you do not want primary e-mail address)'), $this->getLocationTypes(), FALSE);
    if ($this->hasCase) {
      $this->add('checkbox','file_on_case', ts('File Email on Case'));
    }
    $this->assign('has_case', $this->hasCase);
    // add buttons
    $this->addButtons(array(
      array('type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE,),
      array('type' => 'cancel', 'name' => ts('Cancel'))));
  }

  /**
   * Overridden parent method to set default values
   *
   * @return array $defaultValues
   * @access public
   */
  public function setDefaultValues() {
    $data = array();
    $defaultValues = array();
    $defaultValues['rule_action_id'] = $this->ruleActionId;
    if (!empty($this->ruleAction->action_params)) {
      $data = unserialize($this->ruleAction->action_params);
    }
    if (!empty($data['from_name'])) {
      $defaultValues['from_name'] = $data['from_name'];
    }
    if (!empty($data['from_email'])) {
      $defaultValues['from_email'] = $data['from_email'];
    }
    if (!empty($data['template_id'])) {
      $defaultValues['template_id'] = $data['template_id'];
    }
    if (!empty($data['location_type_id'])) {
      $defaultValues['location_type_id'] = $data['location_type_id'];
    }
    if (!empty($data['alternative_receiver_address'])) {
      $defaultValues['alternative_receiver_address'] = $data['alternative_receiver_address'];
      $defaultValues['alternative_receiver'] = TRUE;
    }
		if (!empty($data['cc'])) {
      $defaultValues['cc'] = $data['cc'];
    }
		if (!empty($data['bcc'])) {
      $defaultValues['bcc'] = $data['bcc'];
    }
    $defaultValues['file_on_case'] = FALSE;
    if (!empty($data['file_on_case'])) {
      $defaultValues['file_on_case'] = TRUE;
    }
    return $defaultValues;
  }

  /**
   * Overridden parent method to process form data after submitting
   *
   * @access public
   */
  public function postProcess() {
    $data['from_name'] = $this->_submitValues['from_name'];
    $data['from_email'] = $this->_submitValues['from_email'];
    $data['template_id'] = $this->_submitValues['template_id'];
    $data['location_type_id'] = $this->_submitValues['location_type_id'];
    if (!empty($this->_submitValues['location_type_id'])) {
      $data['alternative_receiver_address'] = '';
    }
    else {
      $data['alternative_receiver_address'] = '';
      if (!empty($this->_submitValues['alternative_receiver_address'])) {
        $data['alternative_receiver_address'] = $this->_submitValues['alternative_receiver_address'];
      }
    }
		$data['cc'] = '';
    if (!empty($this->_submitValues['cc'])) {
      $data['cc'] = $this->_submitValues['cc'];
    }
		$data['bcc'] = '';
    if (!empty($this->_submitValues['bcc'])) {
      $data['bcc'] = $this->_submitValues['bcc'];
    }
    $data['file_on_case'] = FALSE;
    if (!empty($this->_submitValues['file_on_case'])) {
      $data['file_on_case'] = TRUE;
    }

    $ruleAction = new CRM_Civirules_BAO_RuleAction();
    $ruleAction->id = $this->ruleActionId;
    $ruleAction->action_params = serialize($data);
    $ruleAction->save();

    $session = CRM_Core_Session::singleton();
    $session->setStatus('Action '.$this->action->label.' parameters updated to CiviRule '.CRM_Civirules_BAO_Rule::getRuleLabelWithId($this->ruleAction->rule_id),
      'Action parameters updated', 'success');

    $redirectUrl = CRM_Utils_System::url('civicrm/civirule/form/rule', 'action=update&id='.$this->ruleAction->rule_id, TRUE);
    CRM_Utils_System::redirect($redirectUrl);
  }

  /**
   * Method to set the form title
   *
   * @access protected
   */
  protected function setFormTitle() {
    $title = 'CiviRules Edit Action parameters';
    $this->assign('ruleActionHeader', 'Edit action '.$this->action->label.' of CiviRule '.CRM_Civirules_BAO_Rule::getRuleLabelWithId($this->ruleAction->rule_id));
    CRM_Utils_System::setTitle($title);
  }
}
