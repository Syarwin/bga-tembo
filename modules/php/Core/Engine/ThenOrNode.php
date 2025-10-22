<?php

namespace Bga\Games\Tembo\Core\Engine;

/*
 * ThenOrNode: a class that represent an Node with a choice with one extra constraints : nodes can always be taken in order
 */

class ThenOrNode extends OrNode
{
  public function __construct(array $infos = [], array $childs = [])
  {
    parent::__construct($infos, $childs);
    $this->infos['type'] = NODE_THEN_OR;
  }

  /**
   * An OR node is resolved either when marked as resolved, either when all children are resolved already
   */
  public function isResolved(): bool
  {
    if (parent::isResolved()) {
      return true;
    }

    // THEN_OR node is also resolved if the last childs is resolved (since nothing can be done after that)
    return $this->childs[count($this->childs) - 1]->isResolved();
  }
}
