<?php

declare(strict_types=1);

namespace Poker\Game;

final class Bet
{
    /**
     * @var Player
     */
    private $player;

    /**
     * @var int
     */
    private $amount;

    private function __construct()
    {
    }

    /**
     * @param Player $player
     * @param int    $amount
     *
     * @return self
     */
    public static function fromPlayerAndCash(Player $player, int $amount)
    {
        $instance = new self();

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Negative bets are not allowed');
        }

        $instance->player = $player;
        $instance->amount = $amount;

        return $instance;
    }

    /**
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }
}
