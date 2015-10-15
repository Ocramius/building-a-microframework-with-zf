<?php

declare(strict_types=1);

namespace Poker;

use Poker\Game\Bet;
use Poker\Game\Card;
use Poker\Game\Player;
use Poker\Game\PlayerToken;

/**
 * Represents a poker game.
 * Each game is a sequence of very precise and ordered operations that may happen.
 *
 * Note that subsequent games with the same players need new Game instances.
 */
final class Game
{
    const STAGE_SMALL_BLIND        = 'small_blind';
    const STAGE_BIG_BLIND          = 'big_blind';
    const STAGE_FOLD               = 'fold';
    const STAGE_CALL_UNDER_THE_GUN = 'call_under_the_gun';
    const STAGE_FLOP               = 'flop';
    const STAGE_TURN               = 'turn';
    const STAGE_RIVER              = 'river';
    const STAGE_SHOWDOWN           = 'showdown';

    /**
     * @var string
     */
    private $stage = self::STAGE_SMALL_BLIND;

    /**
     * @var Player[] indexed by player token ID
     */
    private $playerTokens = [];

    /**
     * @var Card[][], indexed by player token
     */
    private $playerCards = [];

    /**
     * @var Card[]
     */
    private $communityCards = [];

    /**
     * @var Player[]
     */
    private $players;

    /**
     * @var Player[]
     */
    private $foldedPlayers = [];

    /**
     * A list of exactly 2 blinds players that are expected to post the blind bets
     *
     * @var Bet[]
     */
    private $blindPlayers = [];

    /**
     * A list of all current bets in the game
     *
     * @var Bet[]
     */
    private $bets = [];

    /**
     * @var Player[]
     */
    private $checkedPlayers = [];

    private function __construct()
    {
    }

    /**
     * @param Player ...$players
     *
     * @return self[]|PlayerToken[] (ordered list)
     *
     * @throws \InvalidArgumentException
     */
    public static function fromPlayers(Player ...$players)
    {
        /* @var $players Player[] */
        if (count($players) < 2) {
            throw new \InvalidArgumentException('Need at least 2 players for a game!');
        }

        $instance = new self();

        $instance->players       = $players;
        $instance->blindPlayers  = array_slice($players, 0, 2);
        $instance->playerTokens  = array_combine(
            array_map('strval', array_map([PlayerToken::class, 'newToken'], $players)),
            $players
        );

        // @todo assign player cards
        // @todo assign community cards

        return [
            $instance,
            array_map([PlayerToken::class, 'fromString'], array_keys($instance->playerTokens)),
        ];
    }

    /**
     * Two players have to "blind" the first bet at the beginning of each round
     *
     * Note: this logic currently always picks the first two players in the list
     *
     * @param PlayerToken $playerToken
     * @param int         $amount
     *
     * @return void
     *
     * @throws \LogicException
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     */
    public function postBlind(PlayerToken $playerToken, int $amount)
    {
        $this->expectStage(self::STAGE_SMALL_BLIND, self::STAGE_BIG_BLIND);

        $player = $this->getPlayerFromToken($playerToken);

        if (count($this->bets) >= 2) {
            throw new \LogicException('The blind has already taken place');
        }

        if (! (in_array($player, $this->blindPlayers, true) && $player === $this->blindPlayers[count($this->bets)])) {
            throw new \BadMethodCallException('Player is not the current blind player');
        }

        if ($this->bets && $this->bets[0]->getAmount() > $amount) {
            throw new \LogicException(sprintf(
                'A previous bet of %s was made, a bet of %s was attempted, but is too low',
                $previousAmount,
                $amount
            ));
        }

        $this->bets[] = Bet::fromPlayerAndCash($player, $amount);
        $this->stage  = self::STAGE_BIG_BLIND;

        if (count($this->bets) >= 2) {
            $this->stage = self::STAGE_FOLD;
        }
    }

    /**
     * A player can see his cards right after the big blind, and until the end of the game
     *
     * @param PlayerToken $playerToken
     *
     * @return Card[]
     *
     * @throws \LogicException
     */
    public function seePlayerCards(PlayerToken $playerToken)
    {
        $this->expectStage(
            self::STAGE_FOLD,
            self::STAGE_CALL_UNDER_THE_GUN,
            self::STAGE_FLOP,
            self::STAGE_TURN,
            self::STAGE_RIVER,
            self::STAGE_SHOWDOWN
        );

        return $this->playerCards[array_search($this->getPlayerFromToken($playerToken), $this->players, true)];
    }

    public function seeCommunityCards()
    {
        $this->expectStage(
            self::STAGE_FOLD,
            self::STAGE_CALL_UNDER_THE_GUN,
            self::STAGE_FLOP,
            self::STAGE_TURN,
            self::STAGE_RIVER,
            self::STAGE_SHOWDOWN
        );

        if ($this->stage === self::STAGE_SHOWDOWN) {
            return $this->communityCards;
        }

        if ($this->stage === self::STAGE_RIVER) {
            return array_slice($this->communityCards, 0, 4);
        }

        return array_slice($this->communityCards, 0, 3);
    }

    public function fold(PlayerToken $playerToken)
    {
        $this->expectStage(
            self::STAGE_FOLD,
            self::STAGE_CALL_UNDER_THE_GUN,
            self::STAGE_FLOP,
            self::STAGE_TURN,
            self::STAGE_RIVER
        );

        $this->foldedPlayers[] = $this->getActivePlayerFromToken($playerToken);
    }

    /**
     * Basically a bet, but with the amount specified by the previous bet
     *
     * @param PlayerToken $playerToken
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function call(PlayerToken $playerToken)
    {
        $this->bet($playerToken, $this->getLastBet()->getAmount());
    }

    public function bet(PlayerToken $playerToken, int $amount)
    {
        $player         = $this->getActivePlayerFromToken($playerToken);
        $previousAmount = $this->getLastBet()->getAmount();

        if ($amount < $previousAmount) {
            throw new \LogicException(sprintf(
                'A previous bet of %s was made, a bet of %s was attempted, but is too low',
                $previousAmount,
                $amount
            ));
        }

        if ($previousAmount < $amount) {
            // everyone else must check again (except the betting player)
            $this->checkedPlayers = [];
        }

        $this->bets[] = Bet::fromPlayerAndCash($player, $amount);

        $this->check($playerToken);

    }

    public function check(PlayerToken $playerToken)
    {
        $this->expectStage(
            self::STAGE_FOLD,
            self::STAGE_CALL_UNDER_THE_GUN,
            self::STAGE_FLOP,
            self::STAGE_TURN,
            self::STAGE_RIVER
        );

        $this->checkedPlayers[] = $this->getActivePlayerFromToken($playerToken);

        $this->tryProceedingToNextStep();
    }

    /**
     * Alias for "check"
     *
     * @return void
     */
    public function tap(PlayerToken $playerToken)
    {
        $this->check($playerToken);
    }

    /**
     * @param PlayerToken $playerToken
     *
     * @return Player
     */
    private function getActivePlayerFromToken(PlayerToken $playerToken)
    {
        $player  = $this->getPlayerFromToken($playerToken);
        $players = $this->getPlayersRequiredToAction();

        if (! in_array($player, $players, true)) {
            throw new \LogicException('This player is not active, and is not allowed to play right now');
        }

        return $player;
    }

    /**
     * @param PlayerToken $token
     *
     * @return Player
     *
     * @throws \InvalidArgumentException
     */
    private function getPlayerFromToken(PlayerToken $token)
    {
        $tokenString = (string) $token;

        if (! isset($this->playerTokens[$tokenString])) {
            throw new \InvalidArgumentException(sprintf('Player by token "%s" could not be found', $tokenString));
        }

        return $this->playerTokens[$tokenString];
    }

    /**
     * Expect the game to be in one of the given stages
     *
     * @param string ...$stages
     *
     * @return void
     *
     * @throws \LogicException
     */
    private function expectStage(string ...$stages)
    {
        if (! in_array($this->stage, $stages, true)) {
            throw new \LogicException(sprintf(
                'Game should be in one of "%s", current stage is %s',
                implode('", "', $stages),
                $this->stage
            ));
        }
    }

    /**
     * @return Bet
     */
    private function getLastBet()
    {
        return end($this->bets);
    }

    /**
     * @return Player[]
     */
    private function getPlayersRequiredToAction()
    {
        return array_diff(
            $this->players,
            $this->foldedPlayers,
            $this->checkedPlayers
        );
    }

    private function tryProceedingToNextStep()
    {
        if ($this->getPlayersRequiredToAction()) {
            // not proceeding, as some players still need to take action
            return;
        }

        switch ($this->stage) {
            case self::STAGE_FOLD;
            case self::STAGE_CALL_UNDER_THE_GUN;
                $this->stage = self::STAGE_FLOP;

                return;
            case self::STAGE_FLOP;
                $this->stage = self::STAGE_TURN;

                return;
            case self::STAGE_TURN;
                $this->stage = self::STAGE_RIVER;

                return;
            case self::STAGE_RIVER;
                $this->stage = self::STAGE_SHOWDOWN;

                return;
        }
    }
}
