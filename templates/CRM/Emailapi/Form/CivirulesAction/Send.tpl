<h3>{$ruleActionHeader}</h3>
<div class="crm-block crm-form-block crm-civirule-rule_action-block-email-send">
  <div class="help-block" id="help">
    {ts}<p>This is the form where you can set what is going to happen with the email.</p>
    <p>The first few fields are relatively straightforward: the <strong>From Name</strong> is the name the email will be sent from and the <strong>From Email</strong> is the email address the email will be sent from.</p>
    <p>The <strong>Message Template</strong> is where you select which CiviCRM message template will be used to compose the mail. You can create and edit them in <strong>Administer>Communications>Message Templates</strong></p>
    <p>The next section allows you to manipulate where the email will be sent to.<br/>
    By <strong>default</strong> the email will be sent to the <strong>primary email address of the contact</strong> in question.<br/>
    You have the option to deviate from that behaviour in 2 ways:
    <ol>
      <li>You can select to use an email address of a <strong>specific location type</strong>, for example <em>Work</em>. You do that by selecting the location type. The mail will be sent to the newest email address of that location type (if there are more than one for the contact). If no email address of the specified location type is found, the email will still be sent to the primary email address of the contact</li>
      <li>If you do not select a location type you can <strong>tick</strong> the <strong>Send to Alternative Email Address</strong> box. You will then get the option to enter an alternative email address. Obviously you have to enter a valid email address here.</li>
    </ol>
    </p>
      <p>Finally you can specify an emailaddress for the <strong>CC to</strong> (a copy of the email will be sent to this email address and the email address will be visible to the recipient of the email too) or the <strong>BCC to</strong> (a copy of the email will be sent to this email address and the email address will NOT be visible to the recipient of the email too).</p>
      <p>The sending of the email will also lead to an activity (type <em>Email</em>) being recorded for the contact in question, whatever email address will be used.</p>
    {/ts}
  </div>
  <div class="crm-section">
    <div class="label">{$form.from_name.label}</div>
    <div class="content">{$form.from_name.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.from_email.label}</div>
    <div class="content">{$form.from_email.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.template_id.label}</div>
    <div class="content">{$form.template_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.location_type_id.label}</div>
    <div class="content">{$form.location_type_id.html}</div>
    <div class="content" id="location_note">{ts}Note: primary e-mailaddress will be used if location type e-mailaddress not found{/ts}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section hiddenElement alternative_receiver">
    <div class="label">{$form.alternative_receiver.label}</div>
    <div class="content">{$form.alternative_receiver.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section hiddenElement alternative_receiver_address">
    <div class="label">{$form.alternative_receiver_address.label}</div>
    <div class="content">{$form.alternative_receiver_address.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section cc">
    <div class="label">{$form.cc.label}</div>
    <div class="content">{$form.cc.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section bcc">
    <div class="label">{$form.bcc.label}</div>
    <div class="content">{$form.bcc.html}</div>
    <div class="clear"></div>
  </div>

  {if ($has_case)}
    <div class="crm-section">
      <div class="label">{$form.file_on_case.label}</div>
      <div class="content">{$form.file_on_case.html}</div>
      <div class="clear"></div>
    </div>
  {/if}
</div>
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{literal}
  <script type="text/javascript">
    cj(function() {
      cj('#alternative_receiver').change(triggerAlternativeReceiverChange);
      triggerAlternativeReceiverChange();
      cj('#location_type_id').change(function() {
        triggerFallBackPrimary();
      });
      triggerFallBackPrimary();
    });
  function triggerAlternativeReceiverChange() {
    cj('.crm-section.alternative_receiver_address').addClass('hiddenElement');
    var val = cj('#alternative_receiver').prop('checked');
    if (val) {
      cj('.crm-section.alternative_receiver_address').removeClass('hiddenElement');
    }
  }
  function triggerFallBackPrimary() {
    var locType = cj('#location_type_id').val();
    cj('.crm-section.alternative_receiver').removeClass('hiddenElement');
    cj('#location_note').hide();
    triggerAlternativeReceiverChange();
    if (locType) {
      cj('#location_note').show();
      cj('.crm-section.alternative_receiver_address').addClass('hiddenElement');
      cj('.crm-section.alternative_receiver').addClass('hiddenElement');
    }
  }
  </script>
{/literal}