<?php

namespace Bga\Games\Tembo\Models;

use Bga\Games\Tembo\Core\Engine\AbstractNode;
use Bga\Games\Tembo\Helpers\Collection;
use Bga\Games\Tembo\Helpers\DB_Model;
use Bga\Games\Tembo\Managers\Actions;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Managers\Players;

/*
 * Player: all utility functions concerning a player
 */

class Player extends DB_Model
{
  protected string $table = 'player';
  protected string $primary = 'player_id';
  protected array $attributes = [
    'id' => ['player_id', 'int'],
    'no' => ['player_no', 'int'],
    'name' => 'player_name',
    'color' => 'player_color',
    'eliminated' => ['player_eliminated', 'bool'],
    'score' => ['player_score', 'int'],
    'scoreAux' => ['player_score_aux', 'int'],
    'zombie' => ['player_zombie', 'bool'],
    'rotation' => ['player_rotation', 'int'],
  ];
  protected int $id;
  protected int $no;
  protected string $name;
  protected string $color;
  protected bool $eliminated;
  protected int $score;
  protected int $scoreAux;
  protected bool $zombie;
  protected int $rotation;

  public function getId(): int
  {
    return $this->id;
  }

  public function getNo(): int
  {
    return $this->no;
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function getScore(): int
  {
    return $this->score;
  }

  public function isEliminated(): bool
  {
    return $this->eliminated;
  }

  public function isZombie(): bool
  {
    return $this->zombie;
  }

  public function getUiData(): array
  {
    $data = parent::getUiData();

    $currentId = Players::getCurrentId();
    $hand = $this->getHand();
    $data['hand'] = ($this->id == $currentId) ? $hand->ui() : [];
    $data['handCount'] = $hand->count();
    $data['matriarchCount'] = $hand->filter(fn(Card $card) => $card->isMatriarch())->count();

    return $data;
  }

  public function getHand(): Collection
  {
    return Cards::getInLocation(LOCATION_HAND . "_" . $this->id);
  }

  public function canTakeAction(string $action, null|array|AbstractNode $ctx): bool
  {
    return Actions::isDoable($action, $ctx, $this);
  }
}
