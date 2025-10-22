<?php

namespace Bga\Games\Tembo\Managers;

use Bga\Games\Tembo\Game;
use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Core\Engine\AbstractNode;
use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Helpers\Log;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Action;
use Bga\Games\Tembo\Models\Player;

class Actions
{
  static array $classes = [
    CHOOSE_ACTION
  ];

  public static function get(string $actionId, null|AbstractNode|array &$ctx = null): Action
  {
    if (!in_array($actionId, self::$classes)) {
      throw new \BgaVisibleSystemException('Trying to get an atomic action not defined in Actions.php : ' . $actionId);
    }
    $name = 'Bga\Games\Tembo\Actions\\' . $actionId;
    return new $name($ctx);
  }

  public static function isDoable(string $actionId, array|AbstractNode $ctx, Player $player): bool
  {
    $res = self::get($actionId, $ctx)->isDoable($player);
    return $res;
  }

  public static function getErrorMessage(string $actionId): string
  {
    $actionId = ucfirst(mb_strtolower($actionId));
    $msg = sprintf(
      Game::get()::translate(
        'Attempting to take an action (%s) that is not possible. Either another card erroneously flagged this action as possible, or this action was possible until another card interfered.'
      ),
      $actionId
    );
    return $msg;
  }

  public static function getState(string $actionId, array|AbstractNode $ctx): int
  {
    return self::get($actionId, $ctx)->getState();
  }

  public static function getArgs(string $actionId, array|AbstractNode $ctx): array
  {
    $action = self::get($actionId, $ctx);
    $args = $action->getArgs();
    return array_merge($args, ['optionalAction' => $ctx->isOptional()]);
  }

  public static function takeAction(string $actionId, string $actionName, array $args, AbstractNode &$ctx, bool $automatic = false): void
  {
    $player = Players::getActive();
    if (!self::isDoable($actionId, $ctx, $player)) {
      throw new \BgaUserException(self::getErrorMessage($actionId));
    }

    $action = self::get($actionId, $ctx);
    $isMultiactive = $action->isMultiactive();

    // Check action
    if (!$automatic && !$isMultiactive) {
      if (Game::get()->checkAction($actionName, false) === false) {
        throw new \feException('Impossible action ' . $actionName);
      }
      $stepId = Log::step();
      Notifications::newUndoableStep($player, $stepId);
    } else {
      if (Game::get()->gamestate->checkPossibleAction($actionName, false) === false) {
        throw new \feException('Impossible possible action ' . $actionName);
      }
    }

    // Run action
    $methodName = $actionName; //'act' . self::$classes[$actionId];
    $checkpoint = $action->$methodName(...$args) ?? false;

    // Resolve action
    if ($isMultiactive) return;
    $ctx = $action->getCtx();
    Engine::resolveAction(['actionName' => $actionName, 'args' => $args], $checkpoint, $ctx, $automatic);
    Engine::proceed();
  }

  public static function stAction(string $actionId, AbstractNode $ctx): bool
  {
    $player = Players::getActive();
    if (!self::isDoable($actionId, $ctx, $player)) {
      if (!$ctx->isOptional()) {
        if (self::isDoable($actionId, $ctx, $player, true)) {
          Game::get()->gamestate->jumpToState(ST_IMPOSSIBLE_MANDATORY_ACTION);
          return false;
        } else {
          throw new \BgaUserException(self::getErrorMessage($actionId));
        }
      } else {
        // Auto pass if optional and not doable
        Game::get()->actPassOptionalAction(true);
        return false;
      }
    }

    $action = self::get($actionId, $ctx);
    $methodName = $ctx->getArgs()['method'] ?? 'st' . $action->getClassName();
    if (\method_exists($action, $methodName)) {
      $result = $action->$methodName();
      if (!is_null($result)) {
        if (is_array($result)) {
          $actionName = 'act' . $action->getClassName();
          self::takeAction($actionId, $actionName, $result, $ctx, true);
        } else {
          $ctx = $action->getCtx();
          Engine::resolveAction([], $result, $ctx, true);
          Engine::proceed();
        }
        return true; // We are changing state
      }
    }

    return false;
  }

  public static function stPreAction(string $actionId, AbstractNode $ctx): void
  {
    $action = self::get($actionId, $ctx);
    $methodName = 'stPre' . $action->getClassName();
    if (\method_exists($action, $methodName)) {
      $action->$methodName();
      if ($ctx->isIrreversible(Players::get($ctx->getPId()))) {
        Engine::checkpoint();
      }
    }
  }

  public static function pass(string $actionId, AbstractNode $ctx): void
  {
    if (!$ctx->isOptional()) {
      var_dump($ctx->toArray());
      throw new \BgaVisibleSystemException('This action is not optional');
    }

    $action = self::get($actionId, $ctx);
    $methodName = 'actPass' . $action->getClassName();
    if (\method_exists($action, $methodName)) {
      $action->$methodName();
    } else {
      Engine::resolveAction([PASS], false, $ctx);
    }

    Engine::proceed();
  }
}
