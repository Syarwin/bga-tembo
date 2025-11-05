<?php

namespace Bga\Games\Tembo\Actions;

use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Action;

class PlayerGainLoseElephants extends Action
{
  public function getState(): int
  {
    return ST_PLAYER_GAIN_LOSE_ELEPHANTS;
  }

  public function argsPlayerGainLoseElephants()
  {
    $player = Players::getActive();
    $amount = $this->getCtxArg('amount');
    return [
      'amount' => $amount,
      'descSuffix' => $amount > 0 ? '' : 'lose',
      'players' => Players::getAll($player->getId())->toArray(),
    ];
  }

  public function actChoosePlayerGainLoseElephants(int $pId)
  {
    if (!in_array($pId, Players::getAll(Players::getActiveId())->toArray())) {
      throw new \BgaVisibleSystemException("actChoosePlayerGainLoseElephants: Incorrect player with id {$pId}");
    }
    Players::get($pId)->gainElephants($this->getCtxArg('amount'));
  }
}
