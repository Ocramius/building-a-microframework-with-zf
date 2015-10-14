<?php

declare(strict_types=1);

namespace Poker\Game;

use Ramsey\Uuid\Uuid;

final class PlayerToken
{
    /**
     * @var Uuid
     */
    private $id;

    private function __construct()
    {
    }

    public static function newToken()
    {
        $instance = new self();

        $instance->id = Uuid::uuid4();

        return $instance;
    }

    public static function fromString(string $tokenString)
    {
        $instance = new self();

        $instance->id = Uuid::fromString($tokenString);

        return $instance;
    }

    public function __toString()
    {
        return (string) $this->id;
    }
}
