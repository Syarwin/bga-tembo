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
    'x' => ['x', 'int'],
    'y' => ['y', 'int'],
    'rotation' => ['rotation', 'int'],
  ];
  protected int $id;
  protected string $location;
  protected int $state;
  protected ?int $x;
  protected ?int $y;
  protected ?int $rotation;

  protected ?array $pattern;
  protected ?Spaces $spaces;
  protected string $type;
  protected string $typeUi;

  protected array $staticAttributes = [
    'type'
  ];

  public function __construct($row)
  {
    parent::__construct($row);
    $cardInfo = CARDS[$this->id];
    $this->pattern = $cardInfo['pattern'] ?? null;
    $this->spaces = empty($cardInfo['spaces']) ? null : new Spaces($cardInfo['spaces']);
    $this->type = $cardInfo['type'] ?? CARD_TYPE_SAVANNA;
    $this->typeUi = isset($cardInfo['type']) ? "{$cardInfo['type']}_{$cardInfo['deck']}" :
      CARD_TYPE_SAVANNA . "_" . $this->id;;
  }

  public function getId()
  {
    return $this->id;
  }

  public function getSpaces(): ?Spaces
  {
    return $this->spaces;
  }

  public function isMatriarch(): bool
  {
    return $this->getType() === CARD_TYPE_MATRIARCH;
  }

  public function isLion(): bool
  {
    return $this->getType() === CARD_TYPE_LION;
  }

  public function getPattern(): ?array
  {
    return $this->pattern;
  }

  public function canBeRotated(): bool
  {
    return $this->pattern['canBeRotated'] ?? false;
  }

  public function getUiData()
  {
    return [...parent::getUiData(), 'type' => $this->typeUi];
  }
}
