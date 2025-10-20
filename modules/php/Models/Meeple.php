<?php

namespace Bga\Games\Tembo\Models;

use Bga\Games\Tembo\Helpers\DB_Model;

class Meeple extends DB_Model
{
  protected string $table = 'meeples';
  protected string $primary = 'meeple_id';
  protected array $attributes = [
    'id' => 'meeple_id',
    'location' => 'meeple_location',
    'state' => 'meeple_state',
    'type' => ['type', 'str'],
    'pId' => ['player_id', 'int'],
    'x' => ['x', 'int'],
    'y' => ['y', 'int'],
  ];
  protected int $id;
  protected string $location;
  protected int $state;
  protected string $type;
  protected ?int $pId;
  protected int $x;
  protected int $y;

  public function getId()
  {
    return $this->id;
  }

  public function getNotifCoords(): string
  {
    return chr(ord('A') + $this->getX()) . ($this->getY() + 1);
  }

  public function getType()
  {
    return $this->type;
  }

  public function getCoords()
  {
    return ['x' => $this->x, 'y' => $this->y];
  }
}
