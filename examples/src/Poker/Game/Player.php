<?php

declare(strict_types=1);

namespace Poker\Game;

final class Player
{
    private $initialCashAmount;

    private function __construct()
    {
    }

    public static function fromCash(int $amount)
    {
        $instance = new self();

        $instance->initialCashAmount = $amount;

        return $instance;
    }
}
