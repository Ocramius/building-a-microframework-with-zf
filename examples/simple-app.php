<?php

// Note: we assume that everyone got their player token already!

use Poker\Game\Player;
use Poker\Game;
use Poker\Game\PlayerToken;
use Ramsey\Uuid\Uuid;

(function () {
    require_once __DIR__ . '/vendor/autoload.php';

    set_error_handler(function () {
        http_response_code(500);

        json_encode(['error' => 'something went wrong']);
    });

    $game        = $_GET['game_id'] ?? null;
    $playerToken = $_GET['player'] ?? null;
    $action      = $_GET['action'] ?? null;
    $amount      = $_GET['amount'] ?? null;

    /**
     * @return Game
     */
    $getGame = function () : Game {
        $gameUuid = Uuid::fromString($_GET['game_id'] ?? '');
        $filePath = __DIR__ . '/data/poker-games/' . (string) $gameUuid;

        if (! file_exists($filePath)) {
            throw new \UnexpectedValueException(sprintf('Game "%s" does not exist', $gameUuid));
        }

        return unserialize(file_get_contents($filePath));
    };

    $saveGame = function (Game $game, Uuid $gameId = null) {
        file_put_contents(
            __DIR__ . '/data/poker-games/' . (string) ($gameId ?? Uuid::fromString($_GET['game_id'] ?? '')),
            serialize($game)
        );
    };

    $getToken = function () : PlayerToken {
        return PlayerToken::fromString($_GET['player_token'] ?? '');
    };

    switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
        case 'GET':
            return;
        case 'POST':
            switch ($action) {
                case 'create-game':
                    // @TODO hardcoded (for now)
                    list($game, $playerTokens) = Game::fromPlayers(
                        Player::fromCash(100),
                        Player::fromCash(150),
                        Player::fromCash(125),
                        Player::fromCash(95)
                    );

                    $gameId = (string) Uuid::uuid4();

                    $saveGame($game, $gameId);

                    echo json_encode([
                        'game-id'       => $gameId,
                        'player-tokens' => array_map('strval', $playerTokens)
                    ]);

                    return;
                case 'post-blind':
                    /* @var $game Game */
                    $game = $getGame();

                    $game->postBlind($getToken(), $_GET['amount'] ?? 0);

                    echo json_encode(true);

                    return;
            }

            return;
    }
})();
