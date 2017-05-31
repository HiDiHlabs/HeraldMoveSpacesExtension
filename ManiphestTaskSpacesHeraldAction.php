<?php

final class HeraldManiphestMoveSpaceAction extends HeraldAction {

  // ACTIONCONST: internal ID, unique (assumably for mapping objects)
  const ACTIONCONST = 'space.move';
  // supposably key for mapping history entries
  const DO_MOVE_SPACE = 'do.move.space';

  // entry in Herald action selection drop down menu when configuring a rule
  public function getHeraldActionName() {
    return pht('Move to space');
  }

  // section in Herald action selection drop down menu
  public function getActionGroupKey() {
    return HeraldSupportActionGroup::ACTIONGROUPKEY;
  }

  // source for input field
  protected function getDatasource() {
    return new PhabricatorSpacesNamespaceDatasource();
  }

  // which UI element to show when configuring the action
  public function getHeraldActionStandardType() {
    return self::STANDARD_PHID_LIST;
  }

  // allowed applicable objects
  public function supportsObject($object) {
    return ($object instanceof PhabricatorProjectInterface);
  }

  // permitted user roles (globally or locally)
  public function supportsRuleType($rule_type) {
    return ($rule_type == HeraldRuleTypeConfig::RULE_TYPE_GLOBAL);
  }

  // appearance in transcript
  protected function getActionEffectMap() {
    return array(
      self::DO_MOVE_SPACE => array(
        'icon' => 'fa-diamond',
        'color' => 'green',
        'name' => pht('Moved to space'),
      ),
    );
  }

  // description of action that will be taken (present tense)
  public function renderActionDescription($value) {
    return pht('Move to space: %s.', $this->renderHandleList($value));
  }

  // description of action that has been taken (past tense, for history view etc.)
  protected function renderActionEffectDescription($type, $data) {
        switch ($type) {
      case self::DO_MOVE_SPACE:
        return pht(
          'Moved to %s space: %s.',
          phutil_count($data),
          $this->renderHandleList($data));
    }
  }

  // executed by Herald rules on objects that match condition (calls function applySpace)
  public function applyEffect($object, HeraldEffect $effect) {
    $current_space = array($object->getSpacePHID());

    // allowed objects for transaction
    $allowed_types = array(
      PhabricatorSpacesNamespacePHIDType::TYPECONST,
    );

    // loadStandardTargets() figures out the to-set spaces from the Phabricator IDs ($phids)
    // and excludes $current_space from this list, potentially resulting in an empty list (NULL).
    // Misconfigured Herald action may result in an empty $phids.
    $new_phids = $effect->getTarget();
    $new_spaces = $this->loadStandardTargets($new_phids, $allowed_types, $current_space);

    // if no spaces need to be set (either because of bad rule (see above comment), or space already manually set), avoid doing work
    if(!$new_spaces) {
      return;
    } else {
      // One object can only be at one space at a time. This silently fixes if one misconfigured Herald rule tries to move one object into different spaces. 
      $phid = head_key($new_spaces);

      $adapter = $this->getAdapter();
      $xaction = $adapter->newTransaction()
        ->setTransactionType(PhabricatorTransactions::TYPE_SPACE)
        ->setNewValue($phid);

      $adapter->queueTransaction($xaction);

      $this->logEffect(self::DO_MOVE_SPACE, array($phid));
    }
  }
}
