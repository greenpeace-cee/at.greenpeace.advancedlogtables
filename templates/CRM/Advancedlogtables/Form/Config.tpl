<h3>{ts domain='at.greenpeace.advancedlogtables'}Configuration settings{/ts}</h3>
<div class="crm-section">
  <div class="label {$form.negateexclusion.name}" style="width:30%;padding-right:20px;">{$form.negateexclusion.label}</div>
  <div class="content">{$form.negateexclusion.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label {$form.excludedtables.name}" style="width:30%;padding-right:20px;">{$tablesLabel.normal}</div>
  <div class="content">{$form.excludedtables.html}</div>
  <div class="clear"></div>
</div>
<div class="help">
Important bits:
  <ul>
    <li>{ts domain='at.greenpeace.advancedlogtables' 1="https://docs.civicrm.org/sysadmin/en/latest/troubleshooting/#trigger-rebuild"}After saving this form, you'll need to <a href="%1">rebuild</a> the database triggers so as to apply the changes.{/ts}</li>
    <li>{ts domain='at.greenpeace.advancedlogtables'}Some important tables have been removed from the selector as they are being considered as <strong>mandatory</strong> for the correct operation of advanced logging.{/ts}</li>
  </ul>
</div>

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
{literal}
<script type="text/javascript">
  cj(document).ready(function(){
    var $TablesElement = cj('form.CRM_Advancedlogtables_Form_Config .label.excludedtables');
    var $negatedOp = cj('form.CRM_Advancedlogtables_Form_Config input#negateexclusion');
    if (cj($negatedOp).is(':checked')) {
      CRM.$($TablesElement).html("{/literal}{$tablesLabel.negated}{literal}");
    } else {
      CRM.$($TablesElement).html("{/literal}{$tablesLabel.normal}{literal}");
    }
    cj($negatedOp).change(function() {
      if (this.checked) {
        CRM.$($TablesElement).html("{/literal}{$tablesLabel.negated}{literal}");
      } else {
        CRM.$($TablesElement).html("{/literal}{$tablesLabel.normal}{literal}");
      }
    });
  });
</script>
{/literal}
