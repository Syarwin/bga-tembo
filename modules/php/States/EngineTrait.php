<?php

namespace Bga\Games\Tembo\States;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Core\Engine\AbstractNode;
use Bga\Games\Tembo\Core\Engine\XorNode;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Managers\Actions;
use Bga\Games\Tembo\Helpers\Log;

use const Bga\Games\Tembo\OPTION_CONFIRM;
use const Bga\Games\Tembo\OPTION_CONFIRM_DISABLED;

trait EngineTrait
{
  function addCommonArgs(array &$args): void
  {
    $player = Players::getActive();
    $args['previousEngineChoices'] = Globals::getEngineChoices();
    $args['previousSteps'] = Log::getUndoableSteps($player->getId());
  }

  /**
   * Ask the corresponding atomic action for its args
   */
  function argsAtomicAction(): array
  {
    $player = Players::getActive();
    $node = Engine::getNextUnresolved();
    $action = $node->getAction();
    $args = Actions::getArgs($action, $node);
    $args['automaticAction'] = Actions::get($action, $node)->isAutomatic($player);
    $this->addArgsAnytimeAction($args, $action, $node);

    $source = $node->getSource() ?? null;
    if (!isset($args['source']) && !is_null($source)) {
      $args['source'] = $source;
    }

    return $args;
  }

  /**
   * Add anytime actions
   */
  function addArgsAnytimeAction(&$args, string $action, ?AbstractNode $node): void
  {
    $this->addCommonArgs($args);

    // If the action is auto => don't display anytime buttons
    if ($args['automaticAction'] ?? false) {
      return;
    }

    $player = Players::getActive();
    $actions = [];

    // Keep only doable actions
    $anytimeActions = [];
    foreach ($actions as $flow) {
      $tree = Engine::buildTree($flow);
      if ($tree->isDoable($player)) {
        $anytimeActions[] = [
          'flow' => $flow,
          'desc' => $flow['desc'] ?? $tree->getDescription(true),
          'optionalAction' => $tree->isOptional(),
          'independentAction' => $tree->isIndependent($player),
        ];
      }
    }
    $args['anytimeActions'] = $anytimeActions;
  }


  function actAnytimeAction(int $choiceId, bool $auto = false): void
  {
    $args = $this->gamestate->state()['args'];
    if (!isset($args['anytimeActions'][$choiceId])) {
      throw new \BgaVisibleSystemException('You can\'t take this anytime action');
    }

    $flow = $args['anytimeActions'][$choiceId]['flow'];
    if (!$auto) {
      Globals::incEngineChoices();
    }
    Engine::insertAtRoot($flow, false);
    Engine::proceed();
  }


  /**
   * Pass the argument of the action to the atomic action
   */
  function actTakeAtomicAction(string $actionName, array $args): void
  {
    $node = Engine::getNextUnresolved();
    $action = $node->getAction();
    Actions::takeAction($action, $actionName, $args, $node);
  }

  /**
   * To pass if the action is an optional one
   */
  function actPassOptionalAction(bool $auto = false): void
  {
    if ($auto) {
      $this->gamestate->checkPossibleAction('actPassOptionalAction');
    } else {
      self::checkAction('actPassOptionalAction');
    }

    $node = Engine::getNextUnresolved();
    Actions::pass($node->getAction(), $node);
  }

  /**
   * Pass the argument of the action to the atomic action
   */
  function stAtomicAction(): void
  {
    $node = Engine::getNextUnresolved();
    Actions::stAction($node->getAction(), $node);
  }

  /********************************
   ********************************
   ********** FLOW CHOICE *********
   ********************************
   ********************************/
  function argsResolveChoice(): array
  {
    $player = Players::getActive();
    $node = Engine::getNextUnresolved();
    $args = array_merge($node->getArgs() ?? [], [
      'choices' => Engine::getNextChoice($player),
      'allChoices' => Engine::getNextChoice($player, true),
    ]);
    if ($node instanceof XorNode) {
      $args['descSuffix'] = 'xor';
    }
    // $sourceId = $node->getSourceId() ?? null;
    // if (!isset($args['source']) && !is_null($sourceId)) {
    //   $args['sourceId'] = $sourceId;
    //   $args['source'] = ZooCards::get($sourceId)->getName();
    // }
    $this->addArgsAnytimeAction($args, 'resolveChoice', $node);
    return $args;
  }

  function actChooseAction(int $choiceId): void
  {
    $player = Players::getActive();
    Engine::chooseNode($player, $choiceId);
  }

  public function stResolveStack() {}

  public function stResolveChoice() {}

  function argsImpossibleAction(): array
  {
    $node = Engine::getNextUnresolved();

    $args = [
      'desc' => $node->getDescription(),
    ];
    $this->addArgsAnytimeAction($args, 'impossibleAction', $node);
    return $args;
  }

  /*******************************
   ******* CONFIRM / RESTART ******
   ********************************/
  public function argsConfirmTurn(): array
  {
    $player = Players::getActive();
    $data = [
      'previousEngineChoices' => Globals::getEngineChoices(),
      'previousSteps' => Log::getUndoableSteps($player->getId()),
      'automaticAction' => false,
    ];
    $this->addArgsAnytimeAction($data, 'confirmTurn', null);
    return $data;
  }

  public function stConfirmTurn(): void
  {
    // Check user preference to bypass if DISABLED is picked
    $pref = Players::getActive()->getPlayer()->getPref(OPTION_CONFIRM);
    if ($pref == OPTION_CONFIRM_DISABLED || Globals::getEngineChoices() == 0) {
      $this->actConfirmTurn(true);
    }
  }

  public function actConfirmTurn(bool $auto = false): void
  {
    if (!$auto) {
      self::checkAction('actConfirmTurn');
    }
    Engine::confirm();
  }

  public function stConfirmPartialTurn(): void
  {
    if (Globals::getEngineChoices() == 0) {
      $this->actConfirmPartialTurn(true);
    }
  }

  public function actConfirmPartialTurn(bool $auto = false): void
  {
    if (!$auto) {
      self::checkAction('actConfirmPartialTurn');
    }
    Engine::confirmPartialTurn();
  }

  public function actRestart(): void
  {
    self::checkAction('actRestart');
    if (Globals::getEngineChoices() < 1) {
      throw new \BgaVisibleSystemException('No choice to undo');
    }

    $player = Players::getActive();
    Engine::restart($player->getId());
  }

  public function actUndoToStep(int $stepId)
  {
    self::checkAction('actRestart');
    $player = Players::getActive();
    Engine::undoToStep($player->getId(), $stepId);
  }
}
