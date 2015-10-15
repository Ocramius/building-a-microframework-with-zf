<?php

// Note: we assume that everyone got their player token already!

use Poker\Game\Player;
use Poker\Game;
use Poker\Game\PlayerToken;
use Ramsey\Uuid\Uuid;
use Zend\Expressive\AppFactory;
use Zend\Http\Response;
use Zend\View\Model\JsonModel;

require_once __DIR__ . '/vendor/autoload.php';

(function () {
    $app = AppFactory::create();

    $uuidRegex          = '[0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12}';
    $gameIdOptions      = ['gameId' => $uuidRegex];
    $playerTokenOptions = ['playerToken' => $uuidRegex];
    $amountOptions      = ['amount' => $uuidRegex];
    // 5357280a-6542-4b25-a560-e8db4f995407

    $app->post('/create-game', function () {
        list($game, $playerTokens) = Game::fromPlayers(
            Player::fromCash(100),
            Player::fromCash(150),
            Player::fromCash(125),
            Player::fromCash(95)
        );

        $gameId = Uuid::uuid4();

        $this->saveGame($game, $gameId);

        return new JsonModel([
            'game-id'       => (string) $gameId,
            'player-tokens' => array_map('strval', $playerTokens)
        ]);
    });

    $app
        ->post('/post-blind/{gameId}/{playerToken}/{amount}', function () {
            /* @var $game Game */
            $game = $this->game();

            $game->postBlind($this->playerToken(), $this->amount());
            $this->saveGame($game);
        })
        ->setOptions(['tokens' => array_merge($gameIdOptions, $playerTokenOptions, $amountOptions)]);

    $app
        ->post('/check/{gameId}/{playerToken}', function () {
            /* @var $game Game */
            $game = $this->game();

            $game->check($this->playerToken());
            $this->saveGame($game);
        })
        ->setOptions(['tokens' => array_merge($gameIdOptions, $playerTokenOptions)]);

    $app
        ->post('/tap/{gameId}/{playerToken}', function () {
            /* @var $game Game */
            $game = $this->game();

            $game->tap($this->playerToken());
            $this->saveGame($game);
        })
        ->setOptions(['tokens' => array_merge($gameIdOptions, $playerTokenOptions)]);

    $app
        ->post('/call/{gameId}/{playerToken}', function () {
            /* @var $game Game */
            $game = $this->game();

            $game->call($this->playerToken());
            $this->saveGame($game);
        })
        ->setOptions(['tokens' => array_merge($gameIdOptions, $playerTokenOptions)]);

    $app
        ->post('/bet/{gameId}/{playerToken}/{amount}', function () {
            /* @var $game Game */
            $game = $this->game();

            $game->bet($this->playerToken(), $this->amount());
            $this->saveGame($game);
        })
        ->setOptions(['tokens' => array_merge($gameIdOptions, $playerTokenOptions, $amountOptions)]);

    $app
        ->get('/see-player-cards/{gameId}/{playerToken}', function () {
            /* @var $game Game */
            $game = $this->game();

            return new JsonModel(['player-cards' => $game->seePlayerCards($this->playerToken())]);
        })
        ->setOptions(['tokens' => array_merge($gameIdOptions, $playerTokenOptions)]);

    $app
        ->get('/see-community-cards/{gameId}', function () {
            /* @var $game Game */
            $game = $this->game();

            return new JsonModel(['player-cards' => $game->seeCommunityCards()]);
        })
        ->setOptions(['tokens' => array_merge($gameIdOptions)]);

    $app->run();
})();
