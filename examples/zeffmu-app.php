<?php

// Note: we assume that everyone got their player token already!

use Poker\Game\Player;
use Poker\Game;
use Poker\Game\PlayerToken;
use Ramsey\Uuid\Uuid;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;

require_once __DIR__ . '/vendor/autoload.php';

// Note: ZeffMu is incomplete, as it doesn't match HTTP Methods
// This example also doesn't include route parameters (not really supported by ZeffMu)
// Also missing: JSON strategy, default modules enabled, error handler

final class GameHelper extends AbstractPlugin
{
    public function __invoke()
    {
        $gameUuid = Uuid::fromString($this->controller->params('gameId', ''));
        $filePath = __DIR__ . '/data/poker-games/' . (string) $gameUuid;

        if (! file_exists($filePath)) {
            throw new \UnexpectedValueException(sprintf('Game "%s" does not exist', $gameUuid));
        }

        return unserialize(file_get_contents($filePath));
    }
}

final class PlayerTokenHelper extends AbstractPlugin
{
    public function __invoke()
    {
        return PlayerToken::fromString($this->controller->params('player_token', ''));
    }
}

final class AmountHelper extends AbstractPlugin
{
    public function __invoke()
    {
        return (int) $this->controller->params('amount', 0);
    }
}

final class SaveGameHelper extends AbstractPlugin
{
    public function __invoke(Game $game, Uuid $gameId = null)
    {
        file_put_contents(
            __DIR__ . '/data/poker-games/' . (string) ($gameId ?? Uuid::fromString($_GET['game_id'] ?? '')),
            serialize($game)
        );
    }
}

$app = \ZeffMu\App::init()
    ->route('/create-game', function () {
        list($game, $playerTokens) = Game::fromPlayers(
            Player::fromCash(100),
            Player::fromCash(150),
            Player::fromCash(125),
            Player::fromCash(95)
        );

        $gameId = (string) Uuid::uuid4();

        $this->saveGame($game, $gameId);

        return new JsonModel([
            'game-id'       => $gameId,
            'player-tokens' => array_map('strval', $playerTokens)
        ]);
    })
    ->route('/post-blind/:gameId/:playerToken/:amount', function () {
        /* @var $game Game */
        $game = $this->game();

        $game->postBlind($this->playerToken(), $this->amount());
        $this->saveGame($game);
    })
    ->route('/check/:gameId/:playerToken', function () {
        /* @var $game Game */
        $game = $this->game();

        $game->check($this->playerToken());
        $this->saveGame($game);
    })
    ->route('/tap/:gameId/:playerToken', function () {
        /* @var $game Game */
        $game = $this->game();

        $game->tap($this->playerToken());
        $this->saveGame($game);
    })
    ->route('/call/:gameId/:playerToken', function () {
        /* @var $game Game */
        $game = $this->game();

        $game->call($this->playerToken());
        $this->saveGame($game);
    })
    ->route('/bet/:gameId/:playerToken/:amount', function () {
        /* @var $game Game */
        $game = $this->game();

        $game->bet($this->playerToken(), $this->amount());
        $this->saveGame($game);
    })
    ->route('/see-player-cards/:gameId/:playerToken', function () {
        /* @var $game Game */
        $game = $this->game();

        return new JsonModel(['player-cards' => $game->seePlayerCards($this->playerToken())]);
    })
    ->route('/see-community-cards/:gameId', function () {
        /* @var $game Game */
        $game = $this->game();

        return new JsonModel(['player-cards' => $game->seeCommunityCards()]);
    });

// controller helpers setup
/* @var $controllerPlugins \Zend\ServiceManager\AbstractPluginManager */
$controllerPlugins = $app->getServiceManager()->get('ControllerPluginManager');

$controllerPlugins->setInvokableClass('game', GameHelper::class);
$controllerPlugins->setInvokableClass('playerToken', PlayerTokenHelper::class);
$controllerPlugins->setInvokableClass('saveGame', PlayerTokenHelper::class);
$controllerPlugins->setInvokableClass('amount', AmountHelper::class);

// cast "null" responses to a "success" response
$app->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, function (MvcEvent $event) {
    if (null === $event->getResult()) {
        $event->setResult(new JsonModel(['success' => true]));
    }
}, -1000);

$app->run();
