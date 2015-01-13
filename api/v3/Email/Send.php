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
  $spec['contact_id']['api.required'] = 1;
  $spec['template_id']['api.required'] = 1;
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
  $messageTemplates = new CRM_Core_DAO_MessageTemplates();
  $messageTemplates->id = $params['template_id'];
  $contactId = $params['contact_id'];
  
  $from = CRM_Core_BAO_Domain::getNameAndEmail();
  $from = "$from[0] <$from[1]>";
  

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

  $params = array(array('contact_id', '=', $contactId, 0, 0));
  list($contact, $_) = CRM_Contact_BAO_Query::apiQuery($params);

  //CRM-4524
  $contact = reset($contact);

  if (!$contact || is_a($contact, 'CRM_Core_Error')) {
    throw new API_Exception('Could not find contact with ID: '.$params['contact_id']);
  }

  //CRM-5734

  // get tokens to be replaced
  $tokens = array_merge(CRM_Utils_Token::getTokens($body_text),
                        CRM_Utils_Token::getTokens($body_html),
                        CRM_Utils_Token::getTokens($body_subject));

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
  list($details) = CRM_Utils_Token::getTokenDetails(array($contactId), $returnProperties, false, false, null, $tokens);
  $contact = reset( $details );
  
  if ($contact['do_not_email'] || empty($contact['email']) || CRM_Utils_Array::value('is_deceased', $contact) || $contact['on_hold']) {
    throw new API_Exception('Suppressed sending e-mail to: '.$contact['display_name']);
  }
  else {
    $email = $contact['email'];
  }

  // call token hook
  $hookTokens = array();
  CRM_Utils_Hook::tokens($hookTokens);
  $categories = array_keys($hookTokens);

  // do replacements in text and html body
  $type = array('html', 'text');
  foreach ($type as $key => $value) {
    $bodyType = "body_{$value}";
    if ($$bodyType) {
      CRM_Utils_Token::replaceGreetingTokens($$bodyType, NULL, $contact['contact_id']);
      $$bodyType = CRM_Utils_Token::replaceDomainTokens($$bodyType, $domain, true, $tokens, true);
      $$bodyType = CRM_Utils_Token::replaceContactTokens($$bodyType, $contact, false, $tokens, false, true);
      $$bodyType = CRM_Utils_Token::replaceComponentTokens($$bodyType, $contact, $tokens, true);
      $$bodyType = CRM_Utils_Token::replaceHookTokens($$bodyType, $contact , $categories, true);
    }
  }
  $html = $body_html;
  $text = $body_text;

  $smarty = CRM_Core_Smarty::singleton();
  foreach ($type as $elem) {
    $$elem = $smarty->fetch("string:{$$elem}");
  }

  // do replacements in message subject
  $messageSubject = CRM_Utils_Token::replaceContactTokens($body_subject, $contact, false, $tokens);
  $messageSubject = CRM_Utils_Token::replaceDomainTokens($messageSubject, $domain, true, $tokens);
  $messageSubject = CRM_Utils_Token::replaceComponentTokens($messageSubject, $contact, $tokens, true);
  $messageSubject = CRM_Utils_Token::replaceHookTokens($messageSubject, $contact, $categories, true);

  $messageSubject = $smarty->fetch("string:{$messageSubject}");

  // set up the parameters for CRM_Utils_Mail::send
  $mailParams = array(
    'groupName' => 'E-mail from API',
    'from' => $from,
    'toName' => $contact['display_name'],
    'toEmail' => $email,
    'subject' => $messageSubject,
  );
  if (!$html || $contact['preferred_mail_format'] == 'Text' || $contact['preferred_mail_format'] == 'Both') {
    // render the &amp; entities in text mode, so that the links work
    $mailParams['text'] = str_replace('&amp;', '&', $text);
  }
  if ($html && ($contact['preferred_mail_format'] == 'HTML' || $contact['preferred_mail_format'] == 'Both')) {
    $mailParams['html'] = $html;
  }

  $result = CRM_Utils_Mail::send($mailParams);
  if (!$result) {
    throw new API_Exception('Error sending e-mail to '.$contact['display_name'].' <'.$email.'> ');
  }
  
  $returnValues[$contactId] = array(
      'contact_id' => $contactId,
      'send' => 1,
      'status_msg' => 'Succesfully send e-mail to '.$contact['display_name'].' <'.$email.'> ',
  );
  
  
  $returnValues = array();
  return civicrm_api3_create_success($returnValues, $params, 'Email', 'Send');
  //throw new API_Exception(/*errorMessage*/ 'Everyone knows that the magicword is "sesame"', /*errorCode*/ 1234);
}

