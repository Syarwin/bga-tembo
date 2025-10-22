<?php

namespace Bga\Games\Tembo\Core\Engine;

use Bga\Games\Tembo\Models\Player;

/*
 * ParallelNode: a class that represent an Node with a choice (parallel), some of them being optional, other are mandatory
 */

class ParallelNode extends AbstractNode
{
  public function __construct(array $infos = [], array $childs = [])
  {
    parent::__construct($infos, $childs);
    $this->infos['type'] = NODE_PARALLEL;
  }

  /**
   * The description of the node is the sequence of description of its children
   */
  public function getDescriptionSeparator(): string
  {
    return ' | ';
  }

  /**
   * A PARALLEL node is doable if all its mandatory childs are doable (or if the PARALLEL node itself is optional)
   */
  public function isDoable(Player $player): bool
  {
    return $this->isOptional() ||
      $this->childsReduceAnd(function ($child) use ($player) {
        return $child->isDoable($player) || $child->isOptional();
      });
  }

  /**
   * A PARALLEL node is optional if all its mandatory childs are already done
   */
  public function isOptional(): bool
  {
    return parent::isOptional() ||
      $this->childsReduceAnd(function ($child) {
        return $child->isOptional() || $child->isResolved();
      });
  }

  /**
   * An PARALLEL node is resolved either when marked as resolved, either when all children are resolved already
   */
  public function isResolved(): bool
  {
    return parent::isResolved() ||
      $this->childsReduceAnd(function ($child) {
        return $child->isResolved();
      });
  }

  /**
   * Specific case for parallel node : if a node is mandatory and independant, resolve it right away
   */
  public function getChoices(?Player $player = null, bool $displayAllChoices = false): array
  {
    $choices = parent::getChoices($player, $displayAllChoices);
    $independentChoices = array_values(
      \array_filter($choices, function ($choice) {
        return ($choice['independentAction'] ?? false) && !($choice['optionalAction'] ?? false);
      })
    );
    if (count($independentChoices)) {
      $choice = $independentChoices[0];
      return [$choice['id'] => $choice];
    } else {
      return $choices;
    }
  }
}
