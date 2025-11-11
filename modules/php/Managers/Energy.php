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

  public static function increase(int $amount, string $msg = null, array $args = []): void
  {
    $current = self::get();
    if ($current + $amount > MAX_ENERGY) {
      $amount = MAX_ENERGY - $current;
    }
    Globals::incEnergy($amount);
    Notifications::energyIncreased($current + $amount, $amount, $msg, $args);
  }

  public static function decrease(int $amount): void
  {
    // TODO: Add losing the game if 0
    $current = self::get();
    if ($current - $amount < 0) {
      $amount = $current;
    }
    Globals::incEnergy(-$amount);
    Notifications::energyDecreased($current - $amount, $amount);
  }
}
