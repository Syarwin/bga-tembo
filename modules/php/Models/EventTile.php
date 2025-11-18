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
  protected int $id;
  protected ?string $location;
  protected ?int $state;
  protected int $type;
  protected int $effect;
  protected mixed $arg;

  public function __construct($row, array $staticData)
  {
    parent::__construct([...$row, $row['card_id'] => (int) $row['card_id']]);
    $this->type = $staticData['type'];
    $this->effect = $staticData['effect'];
    $this->arg = $staticData['arg'] ?? null;
  }

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

  public function getType(): int
  {
    return $this->type;
  }

  public function getEffect(): int
  {
    return $this->effect;
  }

  public function getArg(): mixed
  {
    return $this->arg;
  }

  // Static UI attributes
  protected array $staticAttributes = [];

  // Other static attributes
}
