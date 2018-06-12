<h3>{$ruleActionHeader}</h3>
<div class="crm-block crm-form-block crm-civirule-rule_action-block-email-send">
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