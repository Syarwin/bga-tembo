<?php

namespace Bga\Games\Tembo\Models;

use Bga\Games\Tembo\Helpers\DB_Model;

/*
 * EventTile
 */

class EventTile extends DB_Model
{
  protected string $table = 'event_tiles';
  protected string $primary = 'card_id';

  // Dynamic attributes
  protected array $attributes = [
    'id' => 'card_id',
    'location' => 'card_location',
    'state' => ['card_state', 'int'],
  ];
  protected ?string $id;
  protected ?string $location;
  protected ?int $state;

  public function getId(): string
  {
    return $this->id;
  }

  public function getLocation(): string
  {
    return $this->location;
  }

  public function getState(): int
  {
    return $this->state;
  }

  // Static UI attributes
  protected array $staticAttributes = [];

  // Other static attributes
}
