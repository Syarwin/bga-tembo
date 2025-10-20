<?php

namespace Bga\Games\Tembo\States;

use \Bga\GameFramework\Actions\CheckAction;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Managers\FlowerCards;
use Bga\Games\Tembo\Managers\Players;
use Bga\GameFramework\Actions\Types\JsonParam;
use Bga\GameFramework\Notify;
use Bga\Games\Tembo\Managers\Meeples;

trait TurnTrait
{
  public function stPrepareMarket()
  {
    $turn = Globals::incTurn();
    $cards = FlowerCards::moveDeckToBoard(Globals::getTurn());
    Notifications::newTurn($turn, $cards);
    if (!Globals::isSolo()) {
      $pangolinHolder = Globals::getPangolinLocation();
      Globals::setPangolinPlayedThisTurn(false);
      $this->gamestate->changeActivePlayer($pangolinHolder);
    }
    $this->gamestate->nextState('');
  }

  public function stEndOfTurnCleanup()
  {
    $player = Players::getActive();

    // Move pangolin to market if needed
    if (Globals::getPangolinLocation() === $player->getId() && !Globals::isPangolinPlayedThisTurn()) {
      Globals::setPangolinLocation(LOCATION_TABLE);
      Notifications::pangolinMovedToMarket($player);
    }

    // Is round over ?
    $remainingPIds = $this->getRemainingPlayersToPlay();
    if (empty($remainingPIds)) {
      // In solo => clear remaining flower cards
      if (Globals::isSolo()) {
        FlowerCards::moveAllInLocation(LOCATION_TABLE, LOCATION_DISCARD);
        Notifications::discardLeftoverFlowerCards();
      }

      // Is end of game ?
      if (Globals::getTurn() === Globals::getMaxTurn()) {
        // Solo EOG
        if (Globals::isSolo()) {
          $player = Players::getActive();
          $score = $player->getScore();
          $isStandard = empty(Globals::getEcosystems());
          $msg = '';
          $stars = 0;

          // < 80 is a LOSS
          if ($score < ($isStandard ? 50 : 80)) {
            $player->setScore(-1);
          } // Otherwise, it's a win
          else {
            $msgs = $isStandard ? [
              50 => ['text' => clienttranslate('A great start!'), 'stars' => 1],
              60 => ['text' => clienttranslate('Well done!'), 'stars' => 2],
              70 => ['text' => clienttranslate('Outstanding!'), 'stars' => 3],
              80 => ['text' => clienttranslate('Expert!'), 'stars' => 4],
              90 => ['text' => clienttranslate('Almost unbelievable!'), 'stars' => 5],
            ] : [
              80 => ['text' => clienttranslate('A great start!'), 'stars' => 1],
              100 => ['text' => clienttranslate('Well done!'), 'stars' => 2],
              120 => ['text' => clienttranslate('Outstanding!'), 'stars' => 3],
              135 => ['text' => clienttranslate('Expert!'), 'stars' => 4],
              150 => ['text' => clienttranslate('Almost unbelievable!'), 'stars' => 5],
            ];
            foreach ($msgs as $threshold => $messageAndStars) {
              if ($score >= $threshold) {
                $msg = $messageAndStars['text'];
                $stars = $messageAndStars['stars'];
              };
            }
            Globals::setEndGameText($msg);
            Globals::setEndGameStars($stars);
            Notifications::endGameScores($player, $msg, $stars);
          }
        } // Tie breaker
        else {
          $pangolinHolder = Globals::getPangolinLocation();
          $turnOrder = Players::getTurnOrder($pangolinHolder);
          foreach ($turnOrder as $i => $pId) {
            Players::get($pId)->setScoreAux(count($turnOrder) - $i);
          }
        }

        $this->gamestate->jumpToState(ST_END_GAME);
      } else {
        $this->gamestate->jumpToState(ST_PREPARE_MARKET);
      }
    } // Still playing
    else {
      $this->activeNextPlayer();
      $pId = $this->getActivePlayerId();
      $this->giveExtraTime($pId);
      $this->gamestate->jumpToState(ST_TURN);
    }
  }

  /**
   * Return the list of player ids that need to take a turn this round
   */
  public function getRemainingPlayersToPlay(): array
  {
    $nLeft = FlowerCards::getInLocation(LOCATION_TABLE)->count();
    if (Globals::isSolo()) {
      $nLeft = $nLeft == 3 ? 1 : 0;
    } else {
      $nLeft += Globals::isPangolinPlayedThisTurn() ? 0 : 1;
    }

    $pIds = [];
    $turnOrder = Players::getTurnOrder(Players::getActiveId());
    for ($i = 0; $i < $nLeft; $i++) {
      $pIds[] = $turnOrder[$i % count($turnOrder)];
    }

    return $pIds;
  }


  ////////////////////////////////////////////////////////////////
  //  ____  _             _                   _   _             
  // / ___|(_)_ __   __ _| | ___    __ _  ___| |_(_) ___  _ __  
  // \___ \| | '_ \ / _` | |/ _ \  / _` |/ __| __| |/ _ \| '_ \ 
  //  ___) | | | | | (_| | |  __/ | (_| | (__| |_| | (_) | | | |
  // |____/|_|_| |_|\__, |_|\___|  \__,_|\___|\__|_|\___/|_| |_|
  //                |___/                                       
  ////////////////////////////////////////////////////////////////

  public function argsTurn()
  {
    $cards = FlowerCards::getInLocation(LOCATION_TABLE);
    $playableCards = [];
    foreach (Players::getAll() as $pId => $player) {
      $playableCards[$pId] = $cards->filter(fn($card) => $player->canPlayCard($card))->getIds();
    }

    $args = [
      'cards' => $playableCards,
      'remainingPIds' => $this->getRemainingPlayersToPlay(),
      '_private' => Globals::getPlannedTurns()
    ];
    if (!Globals::isSolo()) {
      $args['pangolin'] = Globals::getPangolinLocation();
      $args['pangolinPlayed'] = Globals::isPangolinPlayedThisTurn();
    }

    return $args;
  }

  public function stTurn()
  {
    $pId = Players::getActiveId();
    $planned = Globals::getPlannedTurns();
    $turn = $planned[$pId] ?? null;
    if (is_null($turn)) return;

    // Is the turn still valid?
    $valid = true;

    // Is the card/pangolin still available?
    $cardId = (int)$turn['cardId'];
    if ($cardId === 0) {
      $valid = Globals::getPangolinLocation() == LOCATION_TABLE;
    } else {
      $valid = FlowerCards::getSingle($cardId)->getLocation() == LOCATION_TABLE;
    }

    // Is the animal still available, if any?
    if ($valid && isset($turn['animal'])) {
      $valid = !is_null(Meeples::getNextAvailableAnimal($turn['animal']));
    }

    // Clear the turn
    unset($planned[$pId]);
    Globals::setPlannedTurns($planned);

    // Try to take the turn
    if ($valid) {
      try {
        $this->actTakeTurn($turn);
      } catch (\BgaVisibleSystemException $exception) {
        $valid = false;
      }
    }

    if (!$valid) {
      // TODO: more gracefully inform the player that their planned turn was invalid??
    }
  }


  #[CheckAction(false)]
  public function actPlanTurn(#[JsonParam] array $turn): void
  {
    $player = Players::getCurrent();
    $planned = Globals::getPlannedTurns();
    $planned[$player->getId()] = $turn;
    Globals::setPlannedTurns($planned);
    Notifications::plannedTurn($player, $turn);
  }

  #[CheckAction(false)]
  public function actCancelPlan(): void
  {
    $player = Players::getCurrent();
    $planned = Globals::getPlannedTurns();
    unset($planned[$player->getId()]);
    Globals::setPlannedTurns($planned);
    Notifications::cancelPlannedTurn($player);
  }

  /**
   * @throws \BgaVisibleSystemException
   */
  public function actTakeTurn(#[JsonParam] array $turn): void
  {
    $player = Players::getActive();
    $args = $this->argsTurn();
    $cardIs = $args['cards'][$player->getId()];

    // Impossible turn
    if ($turn['discard'] ?? false) {
      if (!empty($cardIs)) {
        throw new \BgaVisibleSystemException(
          "You have a valid card to play so you cant discard. That should not be possible"
        );
      }
      $cardId = (int)$turn['cardId'];
      FlowerCards::move($cardId, LOCATION_DISCARD);
      Notifications::flowerCardDiscard($player, $cardId);
      $this->gamestate->nextState('');
      return;
    }

    // Choose card
    $cardId = (int)$turn['cardId'];
    if ($cardId === 0) {
      if (Globals::getPangolinLocation() != LOCATION_TABLE) {
        throw new \BgaVisibleSystemException(
          "You cant take the Pangolin token. That should not be possible"
        );
      }
      Globals::setPangolinLocation($player->getId());
      Globals::setPangolinPlayedThisTurn(true);

      if (count($turn['colors']) > 1) {
        throw new \BgaVisibleSystemException(
          "More than one color is sent for Pangolin. That should not be possible"
        );
      }
    } else {
      if (!in_array($cardId, $cardIs)) {
        throw new \BgaVisibleSystemException("Invalid card to play. That should not be possible");
      }
      FlowerCards::move($cardId, LOCATION_DISCARD);
    }
    $cardFlowers = $turn['colors'];
    Notifications::flowerCardChosen($player, $cardId);

    // Place flowers
    $flowers = [];
    foreach ($turn['flowersOrder'] as $i) {
      $flower = $turn['flowers'][$i];
      $flower['color'] = $cardFlowers[$i];
      $flowers[$i] = $flower;
    }
    $this->verifyTurnParams($flowers, $cardFlowers);

    $finishedZonesIdsBeforePlacing = array_keys($player->board()->getFullyFilledZones());
    foreach ($flowers as $flower) {
      $meeple = $player->board()->addFlower($flower['x'], $flower['y'], $flower['color']);
      Notifications::meeplePlaced($player, $meeple);
    }

    // Animal
    if (isset($turn['animal'])) {
      $this->verifyAnimalParams($player, (int)$turn['animalZone'], $finishedZonesIdsBeforePlacing);

      $i = $turn['animal'];
      [$treeToRemove, $animal] = $player->board()->placeAnimal($flowers[$i]['x'], $flowers[$i]['y']);
      $player->board()->moveTreeToReserve($treeToRemove);
      Notifications::animalPlaced($player, $treeToRemove, $animal);
    }

    // Fertilize
    if (isset($turn['fertilized'])) {
      $this->verifyFertilizedParams($player, $animal, $turn['fertilized']);

      foreach ($turn['fertilized'] as $flower) {
        if (!isset($flower['color'])) {
          // That should be a tree on top of just placed flower. Need to double-check that
          $itemsAtCell = $player->board()->getItemsAt($flower['x'], $flower['y']);
          if (count($itemsAtCell) === 0) {
            throw new \BgaVisibleSystemException(
              "Placed flower color is not set by frontend while cell is empty"
            );
          }
          $flower['color'] = $itemsAtCell[0]->getType();
        }
        $meeple = $player->board()->addFlower($flower['x'], $flower['y'], $flower['color']);
        Notifications::meeplePlaced($player, $meeple, true);
      }
    }
    $newScores = $player->updateScores();
    Notifications::newScores($player, $newScores);
    $this->gamestate->nextState('');
  }
}
