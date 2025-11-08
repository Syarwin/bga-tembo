<?php

namespace Bga\Games\Tembo\Managers;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Core\Notifications;

class Energy
{
  public static function get()
  {
    return Globals::getEnergy();
  }

  public static function increase(int $amount): void
  {
    $current = self::get();
    if ($current + $amount > MAX_ENERGY) {
      $amount = MAX_ENERGY - $current;
    }
    Globals::incEnergy($amount);
    Notifications::energyIncreased($current + $amount, $amount);
  }

  public static function decrease(int $amount): void
  {
    // TODO: Add losing the game if 0
    $current = self::get();
    $amount = $current - $amount;
    Globals::incEnergy(-$amount);
    Notifications::energyDecreased($current - $amount, $amount);
  }
}
