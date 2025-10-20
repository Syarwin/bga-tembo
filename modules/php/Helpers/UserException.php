<?php

namespace Bga\Games\Tembo\Helpers;

use Bga\Games\Tembo\Game;

class UserException extends \BgaUserException
{
  public function __construct($str)
  {
    parent::__construct(Game::get()::translate($str));
  }
}
