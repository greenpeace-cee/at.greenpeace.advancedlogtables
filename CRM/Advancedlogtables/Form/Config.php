<?php

use CRM_Advancedlogtables_ExtensionUtil as E;
use CRM_Advancedlogtables_Config as C;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Advancedlogtables_Form_Config extends CRM_Core_Form {

  public function buildQuickForm() {

    $pseudovars = C::singleton()->getParams();
    $TablesLabel = [
      'normal' => E::ts('Tables to <strong>exclude</strong> from logging', ['domain' => 'at.greenpeace.advancedlogtabled']),
      'negated' => E::ts('Tables to <strong>include</strong> in logging', ['domain' => 'at.greenpeace.advancedlogtabled']),
    ];
    $this->addElement('select', 'excludedtables', $TablesLabel['normal'], $pseudovars['tables'],
      [
        'multiple' => 'multiple',
        'class' => 'crm-select2',
      ]
    );
    $this->addElement('checkbox', 'negateexclusion', E::ts('Convert the exclusion into inclusion (negate)', ['domain' => 'at.greenpeace.advancedlogtabled']));

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    $this->assign('tablesLabel', $TablesLabel);
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    Civi::settings()->set('advancedlogtables_excludetables', $values['excludedtables']);

    if ($values['negateexclusion']) {
      Civi::settings()->set('advancedlogtables_negate_exclusion', 1);
    }
    else {
      Civi::settings()->set('advancedlogtables_negate_exclusion', 0);
    }
    CRM_Core_Session::setStatus(E::ts('Configuration settings have been saved'));
    parent::postProcess();
  }

  public function setDefaultValues() {
    parent::setDefaultValues();
    $pseudovars = C::singleton()->getParams();
    $this->_defaults['excludedtables'] = $pseudovars['excludedtables'];
    $this->_defaults['negateexclusion'] = $pseudovars['negateexclusion'];
    return $this->_defaults;
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
