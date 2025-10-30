<?php

namespace Bga\Games\Tembo\States;

use Bga\GameFramework\Actions\CheckAction;
use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Managers\Meeples;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Board;

trait SetupTrait
{
  /*
   * setupNewGame:
   */
  protected function setupNewGame($players, $options = [])
  {
    $journey = Globals::getJourney();
    Globals::setupNewGame($players);
    Players::setupNewGame($players, $options);
    $board = Board::setupNewGame($journey);
    Meeples::setupNewGame($journey, $board);
    Cards::setupNewGame($options);
    // Stats::checkExistence();

    $this->activeNextPlayer();
  }

  public function stSetupBranch()
  {
    $this->gamestate->nextState('');
  }

  public function stSetupCards()
  {
    Cards::setupPlayersDecks();
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

  public function actLeaveBoardTiles(): void
  {
    $this->gamestate->setPlayerNonMultiactive(Players::getCurrentId(), '');
  }

  /**
   * @throws \BgaVisibleSystemException
   */
  public function actReorientBoardTile(int $id, int $rotation): void
  {
    $board = Globals::getBoard();
    $tileToRotate = array_filter($board, function ($tile) use ($id) {
      return $tile['id'] == $id;
    });
    if (empty($tileToRotate)) {
      throw new \BgaVisibleSystemException("Tile with id $id not found");
    }
    $index = array_keys($tileToRotate)[0];
    unset($board[$index]);
    $tileToRotate = array_shift($tileToRotate);
    $tileToRotate['rotation'] = $rotation;
    $board[$index] = $tileToRotate;
    Globals::setBoard($board);
    Notifications::boardTileRotated($id, $rotation);
    $energy = Globals::getEnergy() - 2;
    Globals::setEnergy($energy);
    Notifications::energyDecreased($energy, 2);
    foreach (Players::getAll() as $player) {
      $this->gamestate->setPlayerNonMultiactive($player->getId(), '');
    }
  }
}
