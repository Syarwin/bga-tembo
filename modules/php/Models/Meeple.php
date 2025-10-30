<?php

namespace Bga\Games\Tembo\Models;

use Bga\Games\Tembo\Helpers\DB_Model;

/*
 * Meeple
 */

class Meeple extends DB_Model
{
  protected string $table = 'meeples';
  protected string $primary = 'meeple_id';
  protected array $attributes = [
    'id' => ['meeple_id', 'int'],
    'location' => 'meeple_location',
    'state' => ['meeple_state', 'int'],
    'type' => 'type',
    'pId' => ['player_id', 'int'],
    'x' => ['x', 'int'],
    'y' => ['y', 'int'],
  ];

  public function getPos()
  {
    return ['x' => (int) $this->getX(), 'y' => (int) $this->getY()];
  }

  // public function getPosId()
  // {
  //   return Board::getCellId($this->getPos());
  // }

  public function getName()
  {
    $names = [];

    return $names[$this->getType()] ?? $this->getType();
  }
}
