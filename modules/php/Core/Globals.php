<?php

namespace Bga\Games\Tembo\Core;

use Bga\Games\Tembo\Helpers\DB_Manager;
use Bga\Games\Tembo\Managers\Players;

use const Bga\Games\Tembo\OPTION_DIFFICULTY;
use const Bga\Games\Tembo\OPTION_DISABLED;
use const Bga\Games\Tembo\OPTION_EVENTS;
use const Bga\Games\Tembo\OPTION_FIRST_GAME;
use const Bga\Games\Tembo\OPTION_JOURNEY;

/*
 * Globals
 */

class Globals extends DB_Manager
{
  protected static bool $initialized = false;
  protected static array $variables = [
    'engine' => 'obj', // DO NOT MODIFY, USED IN ENGINE MODULE
    'engineChoices' => 'int', // DO NOT MODIFY, USED IN ENGINE MODULE
    'callbackEngineResolved' => 'obj', // DO NOT MODIFY, USED IN ENGINE MODULE
    'anytimeRecursion' => 'int', // DO NOT MODIFY, USED IN ENGINE MODULE
    'customTurnOrders' => 'obj', // DO NOT MODIFY, USED FOR CUSTOM TURN ORDER FEATURE
    'firstPlayer' => 'int',

    'journey' => 'int',
    'board' => 'obj',
    'energy' => 'int',
    'supportTokens' => 'int',
    'destinationUnlocked' => 'bool',
    'endGame' => 'bool',
    'soloDiscardedSecondMatriarch' => 'bool',
  ];

  /*
   * Setup new game
   */
  public static function setupNewGame(array $players, array &$options): void
  {
    if ($options[OPTION_FIRST_GAME] == 0) {
      $options[OPTION_JOURNEY] = 13;    // 13 = journy A
      $options[OPTION_DIFFICULTY] = 0;
      $options[OPTION_EVENTS] = OPTION_DISABLED;
    }

    static::setJourney($options[OPTION_JOURNEY]);
    $playersCount = count($players);
    $energy = [1 => 9, 2 => 9, 3 => 7, 4 => 6][count($players)];
    static::setEnergy($energy);
    static::setDestinationUnlocked(false);
    static::setEndGame(false);
    if ($playersCount === 1) {
      static::setSoloDiscardedSecondMatriarch(false);
    }

    static::setFirstPlayer(array_keys($players)[0]);
  }


  protected static string $table = 'global_variables';
  protected static string $primary = 'name';

  protected static function cast(array $row): mixed
  {
    $val = json_decode(\stripslashes($row['value']), true);
    if (!isset(self::$variables[$row['name']])) return null;
    return self::$variables[$row['name']] == 'int' ? ((int) $val) : $val;
  }

  /*
   * Fetch all existings variables from DB
   */
  protected static $data = [];

  public static function fetch()
  {
    // Turn of LOG to avoid infinite loop (Globals::isLogging() calling itself for fetching)
    $tmp = self::$log;
    self::$log = false;

    foreach (
      self::DB()
        ->select(['value', 'name'])
        ->get(false)
      as $name => $variable
    ) {
      if (\array_key_exists($name, self::$variables)) {
        self::$data[$name] = $variable;
      }
    }
    self::$initialized = true;
    self::$log = $tmp;
  }

  /*
   * Create and store a global variable declared in this file but not present in DB yet
   *  (only happens when adding globals while a game is running)
   */
  public static function create($name)
  {
    if (!\array_key_exists($name, self::$variables)) {
      return;
    }

    $default = [
      'int' => 0,
      'obj' => [],
      'bool' => false,
      'str' => '',
    ];
    $val = $default[self::$variables[$name]];
    self::DB()->insert(
      [
        'name' => $name,
        'value' => \json_encode($val),
      ],
      true
    );
    self::$data[$name] = $val;
  }

  /*
   * Magic method that intercept not defined static method and do the appropriate stuff
   */
  public static function __callStatic($method, $args)
  {
    if (!self::$initialized) {
      self::fetch();
    }

    if (preg_match('/^([gs]et|inc|is)([A-Z])(.*)$/', $method, $match)) {
      // Sanity check : does the name correspond to a declared variable ?
      $name = mb_strtolower($match[2]) . $match[3];
      if (!\array_key_exists($name, self::$variables)) {
        throw new \InvalidArgumentException("Property {$name} doesn't exist");
      }

      // Create in DB if don't exist yet
      if (!\array_key_exists($name, self::$data)) {
        self::create($name);
      }

      if ($match[1] == 'get') {
        // Basic getters
        return self::$data[$name];
      } elseif ($match[1] == 'is') {
        // Boolean getter
        if (self::$variables[$name] != 'bool') {
          throw new \InvalidArgumentException("Property {$name} is not of type bool");
        }
        return (bool) self::$data[$name];
      } elseif ($match[1] == 'set') {
        // Setters in DB and update cache
        $value = $args[0];
        if (self::$variables[$name] == 'int') {
          $value = (int) $value;
        }
        if (self::$variables[$name] == 'bool') {
          $value = (bool) $value;
        }

        self::$data[$name] = $value;
        self::DB()->update(['value' => \addslashes(\json_encode($value))], $name);
        return $value;
      } elseif ($match[1] == 'inc') {
        if (self::$variables[$name] != 'int') {
          throw new \InvalidArgumentException("Trying to increase {$name} which is not an int");
        }

        $getter = 'get' . $match[2] . $match[3];
        $setter = 'set' . $match[2] . $match[3];
        return self::$setter(self::$getter() + (empty($args) ? 1 : $args[0]));
      }
    }
    throw new \feException(print_r(debug_print_backtrace()));
    return null;
  }

  public static function getCardsHandLimit(): int
  {
    return Players::isSolo() ? 4 : 3;
  }
}
