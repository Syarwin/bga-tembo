<?php

namespace Bga\Games\Tembo\Core;

use Bga\Games\Tembo\Core\Engine\AbstractNode;
use Bga\Games\Tembo\Core\Engine\SeqNode;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Managers\Actions;
use Bga\Games\Tembo\Helpers\Log;
use Bga\Games\Tembo\Helpers\UserException;
use Bga\Games\Tembo\Models\Player;
use Bga\Games\Tembo\Game;

require_once __DIR__ . "/../constants.inc.php";

/*
 * Engine: a class that allows to handle complex flow
 */

class Engine
{
  public static ?AbstractNode $tree = null;

  public static function boot(): void
  {
    $t = Globals::getEngine();
    self::$tree = self::buildTree($t);
    self::ensureSeqRootNode();
  }

  /**
   * Save current tree into Globals table
   */

  public static function save(): void
  {
    $t = self::$tree->toArray();
    Globals::setEngine($t);
  }

  /**
   * Ensure the root is a SEQ node to be able to insert easily in the current flow
   */
  protected static function ensureSeqRootNode(): void
  {
    if (!self::$tree instanceof SeqNode) {
      self::$tree = new SeqNode([], [self::$tree]);
      self::save();
    }
  }

  /**
   * Setup the engine, given an array representing a tree
   * @param array $t
   */
  public static function setup(array $t, array $callback): void
  {
    self::$tree = self::buildTree($t);
    self::save();
    Globals::setCallbackEngineResolved($callback);
    Globals::setEngineChoices(0);
    Log::startEngine();
  }

  /**
   * Convert an array into a tree
   * @param array $t
   */
  public static function buildTree(array $t): AbstractNode
  {
    $t['childs'] = $t['childs'] ?? [];
    $type = $t['type'] ?? (empty($t['childs']) ? \NODE_LEAF : NODE_SEQ);

    $childs = [];
    foreach ($t['childs'] as $child) {
      $childs[] = self::buildTree($child);
    }

    $className = 'Bga\Games\Tembo\Core\Engine\\' . ucfirst($type) . 'Node';
    unset($t['childs']);
    return new $className($t, $childs);
  }

  /**
   * Recursively compute the next unresolved node we are going to address
   */
  public static function getNextUnresolved(): ?AbstractNode
  {
    return self::$tree->getNextUnresolved();
  }

  /**
   * Recursively compute the next undoable mandatory node, if any
   */
  public static function getUndoableMandatoryNode(Player $player): ?AbstractNode
  {
    return self::$tree->getUndoableMandatoryNode($player);
  }

  /**
   * Proceed to next unresolved part of tree
   */
  public static function proceed(bool $confirmedPartial = false, bool $isUndo = false): void
  {
    $node = self::$tree->getNextUnresolved();

    // Are we done ?
    if ($node == null) {
      if (Globals::getEngineChoices() == 0) {
        self::confirm(); // No choices were made => auto confirm
      } else {
        // Confirm/restart
        Game::get()->gamestate->jumpToState(ST_CONFIRM_TURN);
      }
      return;
    }

    $oldPId = Game::get()->getActivePlayerId();
    $pId = $node->getPId();

    // Multi active node
    if (is_array($pId) || $pId == 'all') {
      if (!$confirmedPartial) {
        Game::get()->gamestate->jumpToState(ST_CONFIRM_PARTIAL_TURN);
        return;
      }

      Game::get()->gamestate->jumpToState(ST_RESOLVE_STACK);
      if ($pId == 'all') {
        Game::get()->gamestate->setAllPlayersMultiactive();
        foreach (Players::getAll() as $pId => $player) {
          Game::get()->giveExtraTime($pId);
        }
      } else {
        $pIds = Players::getMany($pId)->getIds();
        Game::get()->gamestate->setPlayersMultiactive($pIds, '', true);
        foreach ($pIds as $pId) {
          Game::get()->giveExtraTime($pId);
        }
      }

      // Ensure no undo
      Log::checkpoint();
      Globals::setEngineChoices(0);

      // Proceed to do the action
      self::proceedToState($node, $isUndo);
      return;
    }

    if (
      $pId != null &&
      $oldPId != $pId &&
      !$node->isIndependent(Players::get($pId)) &&
      Globals::getEngineChoices() > 0 &&
      !$confirmedPartial
    ) {
      Game::get()->gamestate->jumpToState(ST_CONFIRM_PARTIAL_TURN);
      return;
    }

    $player = Players::get($pId);

    // Jump to resolveStack state to ensure we can change active pId
    if ($pId != null && $oldPId != $pId) {
      Game::get()->gamestate->jumpToState(ST_RESOLVE_STACK);
      Game::get()->gamestate->changeActivePlayer($pId);
    }

    if ($confirmedPartial) {
      Log::checkpoint();
      Globals::setEngineChoices(0);
    }

    // If node with choice, switch to choice state
    $choices = $node->getChoices($player);
    $allChoices = $node->getChoices($player, true);
    if (!empty($allChoices) && $node->getType() != NODE_LEAF) {
      // Only one choice : auto choose
      $id = array_keys($choices)[0] ?? null;
      if (
        count($choices) == 1 &&
        count($allChoices) == 1 &&
        array_keys($allChoices) == array_keys($choices) &&
        !$choices[$id]['irreversibleAction']
      ) {
        self::chooseNode($player, $id, true);
      } else {
        // Otherwise, go in the RESOLVE_CHOICE state
        Game::get()->gamestate->jumpToState(ST_RESOLVE_CHOICE);
      }
    } else {
      // No choice => proceed to do the action IF DOABLE
      if ($node->isDoable($player)) {
        self::proceedToState($node, $isUndo);
      }
      // Otherwise, skip it
      else {
        $node->resolveAction([PASS]);
        $node->resolve([PASS]);
        self::save();
        self::proceed();
      }
    }
  }

  public static function proceedToState(AbstractNode $node, bool $isUndo = false): void
  {
    $state = $node->getState();
    $actionId = $node->getAction();
    $player = Players::get($node->getPId());

    // Do some pre-action code if needed and if we are not undoing to an irreversible node
    if (!$isUndo || !$node->isIrreversible($player) && $node->getFlag() != PRE_ACTION_DONE) {
      $node->flagStPreAction();
      self::save();
      Actions::stPreAction($actionId, $node);
    }
    Game::get()->gamestate->jumpToState($state);
  }

  /**
   * Get the list of choices of current node
   */
  public static function getNextChoice(?Player $player = null, bool $displayAllChoices = false): array
  {
    return self::$tree->getNextUnresolved()->getChoices($player, $displayAllChoices);
  }

  /**
   * Choose one option
   */
  public static function chooseNode(Player $player, int $nodeId, bool $auto = false): void
  {
    $node = self::$tree->getNextUnresolved();
    $args = $node->getChoices($player);
    if (!isset($args[$nodeId])) {
      throw new \BgaVisibleSystemException('This choice is not possible');
    }

    if (!$auto) {
      Globals::incEngineChoices();
      Log::step();
    }

    if ($nodeId == PASS) {
      self::resolve([PASS]);
      self::proceed();
      return;
    }

    if ($node->getChilds()[$nodeId]->isResolved()) {
      throw new \BgaVisibleSystemException('Node is already resolved');
    }
    $node->choose($nodeId, $auto);
    self::save();
    self::proceed();
  }

  /**
   * Resolve the current unresolved node
   * @param array $args : store informations about the resolution (choices made by Players)
   */
  public static function resolve(array $args = []): void
  {
    $node = self::$tree->getNextUnresolved();
    $node->resolve($args);
    self::save();
  }

  /**
   * Resolve action : resolve the action of a leaf action node
   */
  public static function resolveAction(array $args = [], bool $checkpoint = false, ?AbstractNode &$node = null, bool $automatic = false): void
  {
    if (is_null($node)) {
      $node = self::$tree->getNextUnresolved();
    }
    if (!$node->isReUsable()) {
      $node->resolveAction($args);
      if ($node->isResolvingParent()) {
        $node->getParent()->resolve([]);
      }
    }

    self::save();

    if (!$automatic && (!isset($args['automatic']) || $args['automatic'] === false)) {
      Globals::incEngineChoices();
    }
    if ($checkpoint) {
      self::checkpoint();
    }
  }

  public static function checkpoint(): void
  {
    $player = Players::getActive();
    $node = self::getUndoableMandatoryNode($player);
    if (!is_null($node) && $node->getPId() == $player->getId()) {
      throw new UserException(
        clienttranslate(
          "You can't take an irreversible action if there is a mandatory undoable action pending"
        )
      );
    }

    Globals::setEngineChoices(0);
    Log::checkpoint();
  }

  /**
   * Insert a new node at root level at the end of seq node
   */
  public static function insertAtRoot(array $t, bool $last = true): AbstractNode
  {
    self::ensureSeqRootNode();
    $node = self::buildTree($t);
    if ($last) {
      self::$tree->pushChild($node);
    } else {
      self::$tree->unshiftChild($node);
    }
    self::save();
    return $node;
  }

  /**
   * insertAsChild: turn the node into a SEQ if needed, then insert the flow tree as a child
   */
  public static function insertAsChild(array $t, ?AbstractNode &$node = null): void
  {
    if (is_null($t)) {
      return;
    }
    if (is_null($node)) {
      $node = self::$tree->getNextUnresolved();
    }

    // If the node is an action leaf, turn it into a SEQ node first
    if ($node->getType() == NODE_LEAF) {
      $newNode = $node->toArray();
      $newNode['type'] = NODE_SEQ;
      $newNode['mandatory'] = true;
      $node = $node->replace(self::buildTree($newNode));
    }

    // Push child
    $node->pushChild(self::buildTree($t));
    self::save();
  }

  /**
   * insertOrUpdateParallelChilds:
   *  - if the node is a parallel node => insert all the nodes as childs
   *  - if one of the child is a parallel node => insert as their childs instead
   *  - otherwise, make the action a parallel node
   */

  public static function insertOrUpdateParallelChilds(array $childs, ?AbstractNode &$node = null): void
  {
    if (empty($childs)) {
      return;
    }
    if (is_null($node)) {
      $node = self::$tree->getNextUnresolved();
    }

    if ($node->getType() == NODE_SEQ) {
      // search if we have children and if so if we have a parallel node
      foreach ($node->getChilds() as $child) {
        if ($child->getType() == NODE_PARALLEL) {
          foreach ($childs as $newChild) {
            $child->pushChild(self::buildTree($newChild));
          }
          self::save();
          return;
        }
      }

      $node->pushChild(
        self::buildTree([
          'type' => \NODE_PARALLEL,
          'childs' => $childs,
        ])
      );
    }
    // Otherwise, turn the node into a PARALLEL node if needed, and then insert the childs
    else {
      // If the node is an action leaf, turn it into a Parallel node first
      if ($node->getType() == NODE_LEAF) {
        $newNode = $node->toArray();
        $newNode['type'] = NODE_PARALLEL;
        $node = $node->replace(self::buildTree($newNode));
      }

      // Push childs
      foreach ($childs as $newChild) {
        $node->pushChild(self::buildTree($newChild));
      }
      self::save();
    }
  }

  /**
   * Confirm the full resolution of current flow
   */
  public static function confirm(): void
  {
    $node = self::$tree->getNextUnresolved();
    // Are we done ?
    if ($node != null) {
      throw new \feException("You can't confirm an ongoing turn");
    }

    // Callback
    $callback = Globals::getCallbackEngineResolved();
    if (isset($callback['state'])) {
      Game::get()->gamestate->jumpToState($callback['state']);
    } elseif (isset($callback['order'])) {
      Game::get()->nextPlayerCustomOrder($callback['order']);
    } elseif (isset($callback['method'])) {
      $name = $callback['method'];
      Game::get()->$name();
    }
  }

  public static function confirmPartialTurn(): void
  {
    $node = self::$tree->getNextUnresolved();

    // Are we done ?
    if ($node == null) {
      throw new \feException("You can't partial confirm an ended turn");
    }

    $oldPId = Game::get()->getActivePlayerId();
    $pId = $node->getPId();

    if ($oldPId == $pId) {
      throw new \feException("You can't partial confirm for the same player");
    }

    // Clear log
    self::checkpoint();
    Engine::proceed(true);
  }

  /**
   * Restart the whole flow
   */
  public static function restart(): void
  {
    Log::undoTurn();

    // Force to clear cached informations
    Globals::fetch();
    self::boot();
    self::proceed(false, true);
  }

  /**
   * Restart at a given step
   */
  public static function undoToStep(int $stepId): void
  {
    Log::undoToStep($stepId);

    // Force to clear cached informations
    Globals::fetch();
    self::boot();
    self::proceed(false, true);
  }

  /**
   * Clear all nodes related to the current active zombie player
   */
  public static function clearZombieNodes(int $pId): void
  {
    self::$tree->clearZombieNodes($pId);
  }
}
