<?php

// Note: we assume that everyone got their player token already!

use Poker\Game\Player;
use Poker\Game;
use Poker\Game\PlayerToken;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\AppFactory;
use Zend\Http\Response;

(function () {
    require_once __DIR__ . '/vendor/autoload.php';

    $app = AppFactory::create();

    $uuidRegex          = '[0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12}';
    $gameIdOptions      = ['gameId' => $uuidRegex];
    $playerTokenOptions = ['playerToken' => $uuidRegex];
    $amountOptions      = ['amount' => '[1-9]\d*'];

    // Note: we are not doing any particular DI here, because we want to keep it minimal.
    $getGame = function (Request $request) : Game {
        $gameUuid = Uuid::fromString($request->getAttribute('gameId'));
        $filePath = __DIR__ . '/data/poker-games/' . (string) $gameUuid;

        if (! file_exists($filePath)) {
            throw new \UnexpectedValueException(sprintf('Game "%s" does not exist', $gameUuid));
        }

        return unserialize(file_get_contents($filePath));
    };

    $saveGame = function (Request $request, Game $game, Uuid $gameId = null) {
        file_put_contents(
            __DIR__ . '/data/poker-games/' . (string) ($gameId ?? Uuid::fromString($request->getAttribute('gameId'))),
            serialize($game)
        );
    };

    $getToken = function (Request $request) : PlayerToken {
        return PlayerToken::fromString($request->getAttribute('playerToken'));
    };

    $app->post('/create-game', function () {
        list($game, $playerTokens) = Game::fromPlayers(
            Player::fromCash(100),
            Player::fromCash(150),
            Player::fromCash(125),
            Player::fromCash(95)
        );

        $gameId = Uuid::uuid4();

        $this->saveGame($game, $gameId);

        return new JsonResponse([
            'game-id'       => (string) $gameId,
            'player-tokens' => array_map('strval', $playerTokens)
        ]);
    });

    $app
        ->post(
            '/post-blind/{gameId}/{playerToken}/{amount}',
            function (Request $request) use ($getGame, $getToken, $saveGame) {
                /* @var $game Game */
                $game = $getGame($request);

                $game->postBlind($getToken($request), (int) $request->getAttribute('amount'));
                $saveGame($game);

                return new JsonResponse(true);
            }
        )
        ->setOptions(['tokens' => array_merge($gameIdOptions, $playerTokenOptions, $amountOptions)]);

    $app
        ->post('/check/{gameId}/{playerToken}', function (Request $request) use ($getGame, $getToken, $saveGame) {
            /* @var $game Game */
            $game = $getGame($request);

            $game->check($getToken($request));
            $saveGame($game);

            return new JsonResponse(true);
        })
        ->setOptions(['tokens' => array_merge($gameIdOptions, $playerTokenOptions)]);

    $app
        ->post('/tap/{gameId}/{playerToken}', function (Request $request) use ($getGame, $getToken, $saveGame) {
            /* @var $game Game */
            $game = $getGame($request);

            $game->tap($getToken($request));
            $saveGame($game);

            return new JsonResponse(true);
        })
        ->setOptions(['tokens' => array_merge($gameIdOptions, $playerTokenOptions)]);

    $app
        ->post('/call/{gameId}/{playerToken}', function (Request $request) use ($getGame, $getToken, $saveGame) {
            /* @var $game Game */
            $game = $getGame($request);

            $game->call($getToken($request));
            $saveGame($game);

            return new JsonResponse(true);
        })
        ->setOptions(['tokens' => array_merge($gameIdOptions, $playerTokenOptions)]);

    $app
        ->post(
            '/bet/{gameId}/{playerToken}/{amount}',
            function (Request $request) use ($getGame, $getToken, $saveGame) {
                /* @var $game Game */
                $game = $getGame($request);

                $game->bet($getToken($request), (int) $request->getAttribute('amount'));
                $saveGame($game);

                return new JsonResponse(true);
            }
        )
        ->setOptions(['tokens' => array_merge($gameIdOptions, $playerTokenOptions, $amountOptions)]);

    $app
        ->get(
            '/see-player-cards/{gameId}/{playerToken}',
            function (Request $request) use ($getGame, $getToken, $saveGame) {
                /* @var $game Game */
                $game = $getGame($request);

                return new JsonResponse(['player-cards' => $game->seePlayerCards($getToken($request))]);
            }
        )
        ->setOptions(['tokens' => array_merge($gameIdOptions, $playerTokenOptions)]);

    $app
        ->get('/see-community-cards/{gameId}', function (Request $request) use ($getGame, $saveGame) {
            /* @var $game Game */
            $game = $getGame($request);

            return new JsonResponse(['player-cards' => $game->seeCommunityCards()]);
        })
        ->setOptions(['tokens' => array_merge($gameIdOptions)]);

    $app->run();
})();
