<?php

namespace Bga\Games\Tembo\Models;

use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Core\Engine\AbstractNode;
use Bga\Games\Tembo\Managers\Players;

/*
 * Action: base class to handle atomic action
 */

class Action
{
  protected null|array|AbstractNode $ctx = null; // Contain ctx information : current node of flow tree
  protected ?array $args = null;
  protected string $description = '';
  public function __construct(null|array|AbstractNode $ctx)
  {
    $this->ctx = $ctx;
  }

  public function getCtx(): null|array|AbstractNode
  {
    return $this->ctx;
  }

  public function getArgs(): array
  {
    if (is_null($this->args)) {
      $methodName = 'args' . $this->getClassName();
      $this->args = \method_exists($this, $methodName) ? $this->$methodName() : [];
    }
    return $this->args;
  }

  public function isDoable(Player $player): bool
  {
    return true;
  }

  public function isOptional(): bool
  {
    return false;
  }

  public function isIndependent(?Player $player = null): bool
  {
    return false;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return false;
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return false;
  }

  public function isMultiactive(): bool
  {
    return false;
  }

  public function getDescription(): string|array
  {
    return $this->description;
  }

  public function getPlayer(): Player
  {
    $pId = $this->ctx->getPId() ?? Players::getActiveId();
    return Players::get($pId);
  }

  public function getState(): int
  {
    return 0;
  }

  /**
   * Syntaxic sugar
   */
  public function getCtxArgs(): array
  {
    if ($this->ctx == null) {
      return [];
    } elseif (is_array($this->ctx)) {
      return $this->ctx;
    } else {
      return $this->ctx->getArgs() ?? [];
    }
  }
  public function getCtxArg(string $v): mixed
  {
    return $this->getCtxArgs()[$v] ?? null;
  }

  /**
   * Insert flow as child of current node
   */
  public function insertAsChild($flow): void
  {
    Engine::insertAsChild($flow, $this->ctx);
  }

  /**
   * Insert childs as parallel node childs
   */
  public function pushParallelChild(array $node): void
  {
    $this->pushParallelChilds([$node]);
  }

  public function pushParallelChilds(array $childs): void
  {
    Engine::insertOrUpdateParallelChilds($childs, $this->ctx);
  }

  public function resolveAction(array $args = [], bool $checkpoint = false, bool $proceed = true): void
  {
    $ctx = $this->getCtx();
    Engine::resolveAction($args, $checkpoint, $ctx);
    if ($proceed) {
      Engine::proceed();
    }
  }


  /**
   * Update the args of current node
   * @param array $args : the keys/values that needs to get updated
   * Warning: resolve action must be call on the side
   */
  public function duplicateAction(array $args = [], bool $checkpoint = false): void
  {
    // Duplicate the node and update the args
    $node = $this->ctx->toArray();
    $node['type'] = NODE_LEAF;
    $node['childs'] = [];
    $node['args'] = array_merge($node['args'] ?? [], $args);
    $node['duplicate'] = true;
    unset($node['mandatory']); // Weird edge case
    $node = Engine::buildTree($node);
    // Insert it as a brother of current node and proceed
    $this->ctx->insertAsBrother($node);
    Engine::save();

    if ($checkpoint) {
      Engine::checkpoint();
    }
  }

  public function getClassName(): string
  {
    $classname = get_class($this);
    if ($pos = strrpos($classname, '\\')) {
      return substr($classname, $pos + 1);
    }
    return $classname;
  }
}
