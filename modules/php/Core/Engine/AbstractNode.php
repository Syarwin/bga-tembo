<?php

namespace Bga\Games\Tembo\Core\Engine;

use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Player;

/*
 * AbstractNode: a class that represent an abstract Node
 */

class AbstractNode
{
  protected array $childs = [];
  protected ?AbstractNode $parent = null;
  protected array $infos = [];

  public function __construct(array $infos = [], array $childs = [])
  {
    $this->infos = $infos;
    $this->childs = $childs;

    foreach ($this->childs as $child) {
      $child->attach($this);
    }
  }

  /**********************
   *** Tree utilities ***
   **********************/
  public function attach(AbstractNode $parent): void
  {
    $this->parent = $parent;
  }

  public function replaceAtPos(AbstractNode $node, int $index): AbstractNode
  {
    $this->childs[$index] = $node;
    $node->attach($this);
    return $node;
  }

  public function getIndex(): ?int
  {
    if ($this->parent == null) {
      return null;
    }

    foreach ($this->parent->getChilds() as $i => $child) {
      if ($child === $this) {
        return $i;
      }
    }
    throw new \BgaVisibleSystemException("Can't find index of a child");
  }

  public function replace(AbstractNode $newNode): AbstractNode
  {
    $index = $this->getIndex();
    if (is_null($index)) {
      throw new \BgaVisibleSystemException('Trying to replace the root');
    }
    return $this->parent->replaceAtPos($newNode, $index);
  }

  public function pushChild(AbstractNode $child): void
  {
    array_push($this->childs, $child);
    $child->attach($this);
  }

  public function insertAsBrother(AbstractNode $newNode): AbstractNode
  {
    $index = $this->getIndex();
    if (is_null($index)) {
      throw new \BgaVisibleSystemException('Trying to insert a brother of the root');
    }
    // Ensure parent is a seq node
    if (!$this->parent instanceof SeqNode) {
      $newParent = new SeqNode([], []);
      $newParent = $this->parent->replaceAtPos($newParent, $index);
      $newParent->pushChild($this);
    }

    return $this->parent->insertChildAtPos($newNode, $index);
  }

  public function insertChildAtPos(AbstractNode $node, int $index): AbstractNode
  {
    array_splice($this->childs, $index + 1, 0, [$node]);
    $node->attach($this);
    return $node;
  }

  public function unshiftChild(AbstractNode $child): void
  {
    array_unshift($this->childs, $child);
    $child->attach($this);
  }

  public function getParent(): ?AbstractNode
  {
    return $this->parent;
  }

  public function getChilds(): array
  {
    return $this->childs;
  }

  public function countChilds(): int
  {
    return count($this->childs);
  }

  public function toArray(): array
  {
    return array_merge($this->infos, [
      'childs' => \array_map(function ($child) {
        return $child->toArray();
      }, $this->childs),
    ]);
  }

  protected function childsReduceAnd(callable $callable): bool
  {
    return \array_reduce(
      $this->childs,
      function ($acc, $child) use ($callable) {
        return $acc && $callable($child);
      },
      true
    );
  }

  protected function childsReduceOr(callable $callable): bool
  {
    return \array_reduce(
      $this->childs,
      function ($acc, $child) use ($callable) {
        return $acc || $callable($child);
      },
      false
    );
  }

  /**
   * The description of the node is the sequence of description of its children, separated by a separator
   */
  public function getDescription(): string|array
  {
    $i = 0;
    $desc = [];
    $args = [];

    if (isset($this->infos['customDescription'])) {
      return $this->infos['customDescription'];
    }

    foreach ($this->childs as $child) {
      $name = 'action' . $i++;
      $tmp = $child->getDescription();
      if ($tmp != '') {
        $args[$name] = $tmp;
        $args['i18n'][] = $name;

        if ($child->forceConfirmation()) {
          $tmp = [
            'log' => clienttranslate('Allow ${player_name} to take a triggered action'),
            'args' => [
              'player_name' => Players::get($child->getPId())->getName(),
            ],
          ];
        }
        $args[$name] = $tmp;
        $args['i18n'][] = $name;
        $desc[] = '${' . $name . '}';
      }
    }

    return [
      'log' => \implode($this->getDescriptionSeparator(), $desc),
      'args' => $args,
    ];
  }

  public function getDescriptionSeparator(): string
  {
    return '';
  }

  public function getStateDescription(): string|array
  {
    return $this->infos['stateDescription'] ?? "";
  }


  /***********************
   *** Getters (sugar) ***
   ***********************/
  public function getState(): ?int
  {
    return $this->infos['state'] ?? null;
  }

  public function getPId(): null|int|array
  {
    return $this->infos['pId'] ?? null;
  }

  public function getType(): string
  {
    return $this->infos['type'] ?? NODE_LEAF;
  }

  public function getFlag()
  {
    return $this->infos['flag'] ?? null;
  }

  public function getArgs(): ?array
  {
    return $this->infos['args'] ?? null;
  }

  public function getCardId(): null|string|int
  {
    return $this->infos['cardId'] ?? null;
  }

  public function getSource(): ?string
  {
    return $this->infos['source'] ?? null;
  }

  public function getSourceId(): null|string|int
  {
    return $this->infos['sourceId'] ?? null;
  }

  public function doNotDisplayIfNotDoable(): bool
  {
    return false;
  }
  public function isDoable(Player $player): bool
  {
    return true;
  }
  public function getUndoableMandatoryNode(Player $player): ?AbstractNode
  {
    if (count($this->childs) == 1) {
      return $this->childs[0]->getUndoableMandatoryNode($player);
    }

    if (!$this->isResolved() && !$this->isDoable($player) && ($this->isMandatory() || !$this->isOptional())) {
      return $this;
    }
    return null;
  }

  public function forceConfirmation(): bool
  {
    return $this->infos['forceConfirmation'] ?? false;
  }

  public function isReUsable(): bool
  {
    return $this->infos['reusable'] ?? false;
  }

  public function isResolvingParent(): bool
  {
    return $this->infos['resolveParent'] ?? false;
  }

  /***********************
   *** Node resolution ***
   ***********************/
  public function isResolved(): bool
  {
    return isset($this->infos['resolved']) && $this->infos['resolved'];
  }

  public function getResolutionArgs(): ?array
  {
    return $this->infos['resolutionArgs'] ?? null;
  }

  public function getNextUnresolved(): ?AbstractNode
  {
    if ($this->isResolved()) {
      return null;
    }

    // var_dump($this->infos['choice'], $this->childs[$this->infos['choice']]->isResolved());
    if (!isset($this->infos['choice']) || $this->childs[$this->infos['choice']]->isResolved()) {
      return $this;
    } else {
      return $this->childs[$this->infos['choice']]->getNextUnresolved();
    }
  }

  public function resolve(array $args): void
  {
    $this->infos['resolved'] = true;
    $this->infos['resolutionArgs'] = $args;
  }

  // Useful for zombie players
  public function clearZombieNodes(int $pId): void
  {
    foreach ($this->childs as $child) {
      $child->clearZombieNodes($pId);
    }

    if ($this->getPId() == $pId) {
      $this->resolve([ZOMBIE]);
    }
  }

  /********************
   *** Node choices ***
   ********************/
  public function areChildrenOptional(): bool
  {
    return false;
  }

  public function isOptional(): bool
  {
    if ($this->isMandatory()) return false;

    return $this->infos['optional'] ?? $this->parent != null && $this->parent->areChildrenOptional();
  }

  public function isAutomatic(?Player $player = null): bool
  {
    $choices = $this->getChoices($player);
    return count($choices) < 2;
  }

  // Allow for automatic resolution in parallel node
  public function isIndependent(?Player $player = null): bool
  {
    return $this->isAutomatic($player) &&
      $this->childsReduceAnd(function ($child) use ($player) {
        return $child->isIndependent($player);
      });
  }

  public function getChoices(?Player $player = null, bool $displayAllChoices = false): array
  {
    $choice = null;
    $choices = [];
    $childs = $this->getType() == NODE_SEQ && !empty($this->childs) ? [0 => $this->childs[0]] : $this->childs;

    foreach ($childs as $id => $child) {
      $isDisplayed = true;
      $isDoable = $child->isDoable($player);
      if (!$isDoable) {
        $isDisplayed = $displayAllChoices && !$child->doNotDisplayIfNotDoable();
      }

      if (!$child->isResolved() && $isDisplayed) {
        $choice = [
          'id' => $id,
          'description' => $this->getType() == NODE_SEQ ? $this->getDescription() : $child->getDescription(),
          'args' => $child->getArgs(),
          'optionalAction' => $child->isOptional(),
          'automaticAction' => $child->isAutomatic($player),
          'independentAction' => $child->isIndependent($player),
          'irreversibleAction' => $child->isIrreversible($player),
          'source' => $child->getSource(),
          'sourceId' => $child->getSourceId(),
        ];
        $choices[$id] = $choice;
      }
      // **** ENFORCE ORDER FOR THEN_OR NODE ****
      elseif ($child->isResolved() && $this->getType() == \NODE_THEN_OR) {
        $choices = [];
        $choice = null;
      }
    }

    if ($this->isOptional()) {
      if (count($choices) != 1 || !$choice['optionalAction'] || $choice['automaticAction']) {
        $choices[PASS] = [
          'id' => PASS,
          'description' => clienttranslate('Pass'),
          'irreversibleAction' => false,
          'args' => [],
        ];
      }
    }

    return $choices;
  }

  public function choose(int $childIndex, bool $auto = false): void
  {
    $this->infos['choice'] = $childIndex;
    $child = $this->childs[$this->infos['choice']];
    if (!$auto && !($child instanceof LeafNode)) {
      $child->enforceMandatory();
    }
  }

  public function unchoose(): void
  {
    unset($this->infos['choice']);
  }

  /************************
   ***** Reversibility *****
   ************************/
  public function isIrreversible(?Player $player = null): bool
  {
    return false;
  }

  public function flagStPreAction(): void
  {
    $this->infos['flag'] = PRE_ACTION_DONE;
  }

  /************************
   *** Action resolution ***
   ************************/
  // Declared here because some action leafs can become SEQ nodes once triggered
  // -> we need to distinguish the action resolution from the node resolution
  public function getAction(): ?string
  {
    return $this->infos['action'] ?? null;
  }

  public function isActionResolved(): bool
  {
    return $this->infos['actionResolved'] ?? false;
  }

  public function getActionResolutionArgs(): ?array
  {
    return $this->infos['actionResolutionArgs'] ?? null;
  }

  public function resolveAction(array $args): void
  {
    $this->infos['actionResolved'] = true;
    $this->infos['actionResolutionArgs'] = $args;
    $this->infos['optional'] = false;
  }

  public function enforceMandatory(): void
  {
    $this->infos['mandatory'] = true;
  }

  public function isMandatory(): bool
  {
    return $this->infos['mandatory'] ?? false;
  }
}
