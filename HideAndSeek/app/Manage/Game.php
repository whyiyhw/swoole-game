<?php


namespace App\Manage;


use App\Model\Map;
use App\Model\Player;

class Game
{
    private $gameMap = [];
    private $players = [];

    public function __construct()
    {
        $this->gameMap = new Map(12, 12);
    }

    public function createPlayer($playerId, int $x, int $y)
    {
        $player = new Player($playerId, $x, $y);
        if (!empty($this->players)) {
            $player->setType(Player::PLAYER_TYPE_HIDE);
        }
        $this->players[$playerId] = $player;
    }

    public function getPlayers()
    {
        return $this->players;
    }

    public function getMapData()
    {
        return $this->gameMap->getMapData();
    }

    public function playerMove($playerId, $direction)
    {
        $player = $this->players[$playerId];
        if ($this->canMoveToDirection($player, $direction)) {
            $this->players[$playerId]->{$direction}();
        }
    }

    public function printGameMap()
    {
        $mapData = $this->gameMap->getMapData();
        $font = ["墙，", "    ", '追，', '躲，'];
        /* @var Player $player */
        foreach ($this->players as $player) {
            $mapData[$player->getX()][$player->getY()] = $player->getType() + 1;
        }
        foreach ($mapData as $line) {
            foreach ($line as $item) {
                echo $font[$item];
            }
            echo PHP_EOL;
        }
    }

    public function canMoveToDirection(Player $player, $direction)
    {
        $mapData = $this->gameMap->getMapData();
        $list = $this->getPerMoveAddress($player->getX(), $player->getY(), $direction);
        return $mapData[$list[0]][$list[1]] === 1;
    }

    private function getPerMoveAddress($x, $y, $direction)
    {
        switch ($direction) {
            case Player::UP:
                return [--$x, $y];
            case Player::DOWN:
                return [++$x, $y];
            case Player::LEFT:
                return [$x, --$y];
            case Player::RIGHT:
                return [$x, ++$y];
        }
        return [$x, $y];
    }

    public function isGameOver()
    {
        $result = false;
        $x = -1;
        $y = -1;
        $players = array_values($this->players);
        /* @var Player $player */
        foreach ($players as $key => $player) {
            if ($key == 0) {
                $x = $player->getX();
                $y = $player->getY();
            } elseif ($x == $player->getX() && $y == $player->getY()) {
                $result = true;
            }
        }
        return $result;
    }
}