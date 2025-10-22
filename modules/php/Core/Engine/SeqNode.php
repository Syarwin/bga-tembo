<?php

namespace Bga\Games\Tembo\Core\Engine;

use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Player;

/*
 * SeqNode: a class that represent a sequence of actions
 */

class SeqNode extends AbstractNode
{
  public function __construct(array $infos = [], array $childs = [])
  {
    parent::__construct($infos, $childs);
    $this->infos['type'] = NODE_SEQ;
  }

  /**
   * The description of the node is the sequence of description of its children
   */
  public function getDescriptionSeparator(): string
  {
    return ', ';
  }

  /**
   * A SEQ node is doable if all its children are doable (or if the SEQ node itself is optional)
   * WARNING: this is a very basic check that does not cover the case where the first action might make the second one doable
   *  -> maybe it would make more sense to only check first action ?
   */
  public function isDoable(Player $player): bool
  {
    return $this->childsReduceAnd(function ($child) use ($player) {
      return $child->isDoable($player) || $child->isOptional();
    });
  }

  public function getUndoableMandatoryNode(Player $player): ?AbstractNode
  {
    if ($this->isOptional()) {
      return null;
    }
    foreach ($this->childs as $child) {
      $node = $child->getUndoableMandatoryNode($player);
      if (!is_null($node)) {
        return $node;
      }
    }
    return null;
  }

  /**
   * An SEQ node is resolved either when marked as resolved, either when all children are resolved already
   *  => if the node was actually an action node and is not resolved fully yet => go back to him
   */
  public function isResolved(): bool
  {
    return parent::isResolved() ||
      $this->childsReduceAnd(function ($child) {
        return $child->isResolved();
      });
  }

  /**
   * Just return the first unresolved children, unless the node itself is optional
   */
  public function getNextUnresolved(): ?AbstractNode
  {
    if ($this->isResolved()) {
      return null;
    }

    if ($this->isOptional()) {
      return $this;
    }

    foreach ($this->childs as $child) {
      if (!$child->isResolved()) {
        return $child->getNextUnresolved();
      }
    }

    return null;
  }

  /**
   * We only enter this function if the user decide to enter the SEQ (in the case where the node is optional)
   */
  public function choose(int $childIndex, bool $auto = false): void
  {
    if ($childIndex != 0) {
      throw new \BgaVisibleSystemException('SEQ Choice shouldnt happen with $childIndex different from 0');
    }
    $this->infos['optional'] = false; // Mark the node as mandatory
  }
}
