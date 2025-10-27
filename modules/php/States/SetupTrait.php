<?php

namespace Bga\Games\Tembo\States;

use Bga\GameFramework\Actions\CheckAction;
use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Board;

trait SetupTrait
{
  /*
   * setupNewGame:
   */
  protected function setupNewGame($players, $options = [])
  {
    Globals::setupNewGame($players, $options);
    Players::setupNewGame($players, $options);
    Board::setupNewGame();
    // Stats::checkExistence();

    $this->activeNextPlayer();
  }

  public function stSetupBranch()
  {
    $this->gamestate->nextState(ST_SITTING_AROUND_TABLE);
  }

  public function stSetupCards()
  {
    Cards::setupNewGame();
    $this->gamestate->nextState('debug');
  }

  #[CheckAction(false)]
  public function actChangedMind(): void
  {
    $this->gamestate->checkPossibleAction('actChangedMind');
    $this->gamestate->setPlayersMultiactive([Players::getCurrentId()], '');
  }

  public function actSittingAroundTable(?int $rotation): void
  {
    $player = Players::getCurrent();
    $player->setRotation($rotation ?? 0);
    $this->gamestate->setPlayerNonMultiactive($player->getId(), '');
  }
}
