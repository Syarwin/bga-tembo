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

  public static function increase(int $amount = 1): bool
  {
    $current = self::get();
    if ($current + $amount > MAX_ENERGY) {
      $amount = MAX_ENERGY - $current;
    }
    if ($amount > 0) {
      Globals::incEnergy($amount);
      return true;
    }
    return false;
  }

  public static function decrease(int $amount): void
  {
    $current = self::get();
    if ($current - $amount < 0) {
      $amount = $current;
      Globals::setEndGame(true);
    }
    Globals::incEnergy(-$amount);
    Notifications::energyDecreased($current - $amount, $amount);
  }
}
