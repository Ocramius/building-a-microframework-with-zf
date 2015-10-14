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
            switch ($_GET['action'] ?? null) {
                case 'see-player-cards':
                    /* @var $game Game */
                    $game = $getGame();

                    echo json_encode([
                        // different serialization format needed.
                        'player-cards' => $game->seePlayerCards($getToken()),
                    ]);

                    return;
                case 'see-community-cards':
                    /* @var $game Game */
                    $game = $getGame();

                    echo json_encode([
                        // different serialization format needed.
                        'community-cards' => $game->seeCommunityCards(),
                    ]);

                    return;
            }
            return;
        case 'POST':
            switch ($_GET['action'] ?? null) {
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

                    $game->postBlind($getToken(), (int) ($_GET['amount'] ?? 0));
                    $saveGame($game);

                    echo json_encode(true);

                    return;
                case 'check':
                case 'tap':
                    /* @var $game Game */
                    $game = $getGame();

                    $game->fold($getToken());
                    $saveGame($game);

                    echo json_encode(true);

                    return;
                case 'call':
                    /* @var $game Game */
                    $game = $getGame();

                    $game->call($getToken());
                    $saveGame($game);

                    echo json_encode(true);

                    return;
                case 'bet':
                    /* @var $game Game */
                    $game = $getGame();

                    $game->bet($getToken(), (int) ($_GET['amount'] ?? 0));
                    $saveGame($game);

                    echo json_encode(true);

                    return;
            }

            return;
    }
})();
