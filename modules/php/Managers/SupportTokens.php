<?php

namespace Bga\Games\Tembo\Managers;

use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Models\Player;
use const Bga\Games\Tembo\OPTION_DIFFICULTY;

class SupportTokens
{
  public static function setupNewGame(array $options): void
  {
    $difficulty = (int) $options[OPTION_DIFFICULTY];
    $supportTokensAmount = [0 => 5, 1 => 4, 2 => 3, 3 => 2, 4 => 1, 5 => 0][$difficulty];
    Globals::setSupportTokens($supportTokensAmount);
  }

  public static function get()
  {
    return Globals::getSupportTokens();
  }

  public static function spend(Player $player, int $option): void
  {
    if (static::get() === 0) {
      throw new \BgaVisibleSystemException("spend SupportToken: 0 support tokens left");
    }
    switch ($option) {
      case SUPPORT_ENERGY:
        $msg = clienttranslate('${player_name} spends a support token to gain 2 elephants');
        Energy::increase(1, $msg, ['player' => $player]);
        break;
      case SUPPORT_ELEPHANTS:
        $msg = clienttranslate('${player_name} spends a support token to gain 2 elephants');
        $player->gainElephants(2, $msg);
        break;
      case SUPPORT_ROTATE:
        // TODO: Requires some additional frontend work
        break;
      case SUPPORT_PLACE_ELEPHANT_IGNORE_TERRAIN:
        Engine::insertAsChild(['action' => PLACE_SINGLE_ELEPHANT, 'args' => ['ignoreRough' => true]]);
        $msg = clienttranslate('${player_name} spends a support token to place a single elephant');
        Notifications::message($msg, ['player' => $player]);
        break;
      default:
        throw new \BgaVisibleSystemException("spend SupportToken: Unknown option $option. Should not happen");
    }
    Globals::incSupportTokens(-1);
  }
}
