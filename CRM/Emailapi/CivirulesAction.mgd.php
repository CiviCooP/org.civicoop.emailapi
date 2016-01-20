<?php

if (_emailapi_is_civirules_installed()) {
  return array (
    0 =>
      array (
        'name' => 'Civirules:Action.Emailapi',
        'entity' => 'CiviRuleAction',
        'params' =>
          array (
            'version' => 3,
            'name' => 'emailapi_send',
            'label' => 'Send e-mail',
            'class_name' => 'CRM_Emailapi_CivirulesAction',
            'is_active' => 1
          ),
      ),
  );
}