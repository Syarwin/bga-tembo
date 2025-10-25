<?php

namespace Bga\Games\Tembo\Models;

use Bga\Games\Tembo\Helpers\DB_Model;

require_once dirname(__FILE__) . "/../Materials/Cards.php";

class Card extends DB_Model
{
  protected string $table = 'cards';
  protected string $primary = 'card_id';
  protected array $attributes = [
    'id' => 'card_id',
    'location' => 'card_location',
    'state' => 'card_state',
    'rotation' => ['rotation', 'int'],
    'internal_id' => ['internal_id', 'int'],
  ];
  protected int $id;
  protected string $location;
  protected int $state;
  protected ?array $pattern;
  protected ?Spaces $spaces;
  protected string $type;
  protected int $internal_id;
  protected ?int $rotation;

  public function __construct($row)
  {
    parent::__construct($row);
    $cardInfo = CARDS[$this->internal_id];
    $this->pattern = $cardInfo['pattern'] ?? null;
    $this->spaces = empty($cardInfo['spaces']) ? null : new Spaces($cardInfo['spaces']);
    $this->type = isset($cardInfo['type']) ? "{$cardInfo['type']}_{$cardInfo['deck']}" :
      CARD_TYPE_SAVANNA . "_" . $this->internal_id;
  }

  public function getId()
  {
    return $this->id;
  }

  public function getSpaces(): Spaces
  {
    return $this->spaces;
  }

  public function jsonSerialize(): array
  {
    return ['id' => $this->id, 'type' => $this->type];
  }
}
