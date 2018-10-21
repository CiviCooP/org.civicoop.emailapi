<?php

/**
 * Email.Send API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_email_send_spec(&$spec) {
  $spec['contact_id'] = array(
  	'title' => 'Contact ID',
    'api.required' => 1,
	);
  $spec['template_id'] = array(
  	'title' => 'Template ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
	);
	$spec['case_id'] = array(
		'title' => 'Case ID',
    'type' => CRM_Utils_Type::T_INT,
	);
	$spec['contribution_id'] = array(
		'title' => 'Contribution ID',
    'type' => CRM_Utils_Type::T_INT,
	);
	$spec['alternative_receiver_address'] = array(
		'title' => 'Alternative receiver address',
    'type' => CRM_Utils_Type::T_STRING,
	);
	$spec['cc'] = array(
		'title' => 'Cc',
    'type' => CRM_Utils_Type::T_STRING,
	);
	$spec['bcc'] = array(
		'title' => 'Bcc',
    'type' => CRM_Utils_Type::T_STRING,
	);
}

/**
 * Email.Send API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_email_send($params) {
  $version = CRM_Core_BAO_Domain::version();
  if (!preg_match('/[0-9]+(,[0-9]+)*/i', $params['contact_id'])) {
    throw new API_Exception('Parameter contact_id must be a unique id or a list of ids separated by comma');
  }
  $contactIds = explode(",", $params['contact_id']);
  $alternativeEmailAddress = !empty($params['alternative_receiver_address']) ? $params['alternative_receiver_address'] : false;

  $case_id = false;
  if (isset($params['case_id'])) {
    $case_id = $params['case_id'];
  }
	$contribution_id = false;
	if (isset($params['contribution_id'])) {
		$contribution_id = $params['contribution_id'];
	}

  // Compatibility with CiviCRM > 4.3
  if($version >= 4.4) {
    $messageTemplates = new CRM_Core_DAO_MessageTemplate();
  } else {
    $messageTemplates = new CRM_Core_DAO_MessageTemplates();
  }
  $messageTemplates->id = $params['template_id'];

  $from = CRM_Core_BAO_Domain::getNameAndEmail();
  $from = "$from[0] <$from[1]>";
  if (isset($params['from_email']) && isset($params['from_name'])) {
    $from = $params['from_name']."<".$params['from_email'].">";
  } elseif (isset($params['from_email']) || isset($params['from_name'])) {
    throw new API_Exception('You have to provide both from_name and from_email');
  }

  $domain     = CRM_Core_BAO_Domain::getDomain();
  $result     = NULL;
  $hookTokens = array();

  if (!$messageTemplates->find(TRUE)) {
    throw new API_Exception('Could not find template with ID: '.$params['template_id']);
  }

  $body_text    = $messageTemplates->msg_text;
  $body_html    = $messageTemplates->msg_html;
  $body_subject = $messageTemplates->msg_subject;
  if (!$body_text) {
    $body_text = CRM_Utils_String::htmlToText($body_html);
  }

  $returnValues = array();
  foreach($contactIds as $contactId) {
    $contact_params = array(array('contact_id', '=', $contactId, 0, 0));
    list($contact, $_) = CRM_Contact_BAO_Query::apiQuery($contact_params);

    //CRM-4524
    $contact = reset($contact);

    if (!$contact || is_a($contact, 'CRM_Core_Error')) {
      throw new API_Exception('Could not find contact with ID: ' . $contact_params['contact_id']);
    }

    //CRM-5734

    // get tokens to be replaced
    $tokens = array_merge(CRM_Utils_Token::getTokens($body_text),
        CRM_Utils_Token::getTokens($body_html),
        CRM_Utils_Token::getTokens($body_subject));

    list($details) = CRM_Utils_Token::getTokenDetails($contactIds, $returnProperties, false, false, null, $tokens);
    // get replacement text for these tokens
    $returnProperties = array(
        'sort_name' => 1,
        'email' => 1,
        'do_not_email' => 1,
        'is_deceased' => 1,
        'on_hold' => 1,
        'display_name' => 1,
        'preferred_mail_format' => 1,
    );
    if (isset($tokens['contact'])) {
      foreach ($tokens['contact'] as $key => $value) {
        $returnProperties[$value] = 1;
      }
    }
    if ($case_id) {
      $contact['case.id'] = $case_id;
    }
		if ($contribution_id) {
			$contact['contribution_id'] = $contribution_id;
		}

    if ($alternativeEmailAddress) {
      /**
       * If an alternative reciepient address is given
       * then send e-mail to that address rather than to
       * the e-mail address of the contact
       *
       */
      $toName = '';
      $email = $alternativeEmailAddress;
    } elseif ($contact['do_not_email'] || empty($contact['email']) || CRM_Utils_Array::value('is_deceased', $contact) || $contact['on_hold']) {
      /**
       * Contact is decaused or has opted out from mailings so do not send the e-mail
       */
      continue;
    } else {
      /**
       * Send e-mail to the contact
       */
      $email = $contact['email'];
      $toName = $contact['display_name'];
    }

    // do replacements in text and html body
    $type = array('html', 'text');
    foreach ($type as $key => $value) {
      $bodyType = "body_{$value}";
      if ($$bodyType) {
      	if ($contribution_id) {
            try {
                $contribution = civicrm_api3('Contribution', 'getsingle', array('id' => $contribution_id));
                $$bodyType = CRM_Utils_Token::replaceContributionTokens($$bodyType, $contribution, true, $tokens);
            } catch (Exception $e) {
                echo $e->getMessage(); exit();
            }
				}

        foreach($tokens as $type => $tokenValue) {
            foreach($tokenValue as $var) {
                $contactKey = null;
                if ($type === 'contact') {
                    $contactKey = "$var";
                }
                else {
                    $contactKey = "$type.$var";
                }
                CRM_Utils_Token::token_replace($type, $var, $contact[$contactKey], $$bodyType);
            }
        }
      }
    }
    $html = $body_html;
    $text = $body_text;
    if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
      $smarty = CRM_Core_Smarty::singleton();
      foreach ($type as $elem) {
        $$elem = $smarty->fetch("string:{$$elem}");
      }
    }

    // do replacements in message subject
    $messageSubject = CRM_Utils_Token::replaceContactTokens($body_subject, $contact, false, $tokens);
    $messageSubject = CRM_Utils_Token::replaceDomainTokens($messageSubject, $domain, true, $tokens);
    $messageSubject = CRM_Utils_Token::replaceComponentTokens($messageSubject, $contact, $tokens, true);
    $messageSubject = CRM_Utils_Token::replaceHookTokens($messageSubject, $contact, $categories, true);

    if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
      $messageSubject = $smarty->fetch("string:{$messageSubject}");
    }

    // set up the parameters for CRM_Utils_Mail::send
    $mailParams = array(
        'groupName' => 'E-mail from API',
        'from' => $from,
        'toName' => $toName,
        'toEmail' => $email,
        'subject' => $messageSubject,
        'messageTemplateID' => $messageTemplates->id,
    );

    if (!$html || $contact['preferred_mail_format'] == 'Text' || $contact['preferred_mail_format'] == 'Both') {
      // render the &amp; entities in text mode, so that the links work
      $mailParams['text'] = str_replace('&amp;', '&', $text);
    }
    if ($html && ($contact['preferred_mail_format'] == 'HTML' || $contact['preferred_mail_format'] == 'Both')) {
      $mailParams['html'] = $html;
    }
		if (isset($params['cc']) && !empty($params['cc'])) {
			$mailParams['cc'] = $params['cc'];
		}
		if (isset($params['bcc']) && !empty($params['bcc'])) {
			$mailParams['bcc'] = $params['bcc'];
		}
    $result = CRM_Utils_Mail::send($mailParams);
    if (!$result) {
      throw new API_Exception('Error sending e-mail to ' . $contact['display_name'] . ' <' . $email . '> ');
    }

    //create activity for sending e-mail.
    $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type', 'Email', 'name');

    // CRM-6265: save both text and HTML parts in details (if present)
    if ($html and $text) {
      $details = "-ALTERNATIVE ITEM 0-\n$html\n-ALTERNATIVE ITEM 1-\n$text\n-ALTERNATIVE END-\n";
    }
    else {
      $details = $html ? $html : $text;
    }

    $activityParams = array(
      'source_contact_id' => $contactId,
      'activity_type_id' => $activityTypeID,
      'activity_date_time' => date('YmdHis'),
      'subject' => $messageSubject,
      'details' => $details,
      // FIXME: check for name Completed and get ID from that lookup
      'status_id' => 2,
    );

    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    // Compatibility with CiviCRM >= 4.4
    if ($version >= 4.4) {
      $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
      $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

      $activityTargetParams = array(
        'activity_id' => $activity->id,
        'contact_id' => $contactId,
        'record_type_id' => $targetID
      );
      CRM_Activity_BAO_ActivityContact::create($activityTargetParams);
    }
    else {
      $activityTargetParams = array(
        'activity_id' => $activity->id,
        'target_contact_id' => $contactId,
      );
      CRM_Activity_BAO_Activity::createActivityTarget($activityTargetParams);
    }

    if (!empty($case_id)) {
      $caseActivity = array(
        'activity_id' => $activity->id,
        'case_id' => $case_id,
      );
      CRM_Case_BAO_Case::processCaseActivity($caseActivity);
    }

    $returnValues[$contactId] = array(
      'contact_id' => $contactId,
      'send' => 1,
      'status_msg' => 'Succesfully send e-mail to ' . ' <' . $email . '> ',
    );
  }


  return civicrm_api3_create_success($returnValues, $params, 'Email', 'Send');
  //throw new API_Exception(/*errorMessage*/ 'Everyone knows that the magicword is "sesame"', /*errorCode*/ 1234);
}
