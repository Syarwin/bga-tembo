<?php

namespace Bga\Games\Tembo\Models;

use Bga\Games\Tembo\Helpers\DB_Model;

class Card extends DB_Model
{
  protected string $table = 'cards';
  protected string $primary = 'card_id';
  protected array $attributes = [
    'id' => 'card_id',
    'location' => 'card_location',
    'state' => 'card_state',
  ];
  protected int $id;
  protected string $location;
  protected int $state;

  public function getId()
  {
    return $this->id;
  }
}
