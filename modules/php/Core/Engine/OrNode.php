<?php

namespace Bga\Games\Tembo\Core\Engine;

use Bga\Games\Tembo\Models\Player;

/*
 * OrNode: a class that represent an Node with a choice (parallel)
 */

class OrNode extends AbstractNode
{
  public function __construct(array $infos = [], array $childs = [])
  {
    parent::__construct($infos, $childs);
    $this->infos['type'] = NODE_OR;
  }

  /**
   * The description of the node is the sequence of description of its children
   */
  public function getDescriptionSeparator(): string
  {
    return ' + ';
  }

  /**
   * An OR node is doable if at least one of its child is doable (or if the OR node itself is optional)
   */
  public function isDoable(Player $player): bool
  {
    return $this->isOptional() ||
      $this->childsReduceOr(function ($child) use ($player) {
        return $child->isDoable($player);
      });
  }

  /**
   * An OR node become optional as soon as one child is resolved
   */
  public function isOptional(): bool
  {
    return parent::isOptional() ||
      $this->childsReduceOr(function ($child) {
        return $child->isResolved() && $child->getResolutionArgs() != PASS;
      });
  }

  /**
   * If at least one child was resolved already, other become optional
   */
  public function areChildrenOptional(): bool
  {
    return $this->childsReduceOr(function ($child) {
      return $child->isResolved() && $child->getResolutionArgs() != PASS;
    });
  }

  /**
   * An OR node is resolved either when marked as resolved, either when all children are resolved already
   */
  public function isResolved(): bool
  {
    return parent::isResolved() ||
      $this->childsReduceAnd(function ($child) {
        return $child->isResolved();
      });
  }
}
