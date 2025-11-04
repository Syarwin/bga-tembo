<?php

namespace Bga\Games\Tembo\Actions;

use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Action;
use Bga\Games\Tembo\Models\Board;

class ChooseAction extends Action
{
  public function getState(): int
  {
    return ST_CHOOSE_ACTION;
  }

  public function argsChooseAction()
  {
    $player = Players::getActive();
    $args = [];
    if (count((new Board())->getEmptySquares()) > 0) {
      $args['options'][] = [
        'option' => 'placeCard',
        'description' => clienttranslate('Place a card'),
      ];
    }
    return $args;
  }

  public function actChooseAction(string $option)
  {
    $player = Players::getActive();
    $args = $this->getArgs();
    if (!in_array($option, array_map(fn($arg) => $arg['option'], $args))) {
      throw new \BgaVisibleSystemException("actChooseAction: Incorrect option $option");
    }
  }
}
