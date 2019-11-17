<?php


namespace App\Manage;


use App\Model\Player;

class Logic
{
    const PLAYER_DISPLAY_LEN = 2;// 玩家能看到周围的地图

    public function matchPlayer($playerId)
    {
        //将用户放入队列中
        DataCenter::pushPlayerToWaitList($playerId);
        //发起一个Task尝试匹配
        DataCenter::$server->task(['code' => TaskManager::TASK_CODE_FIND_PLAYER]);
    }

    public function createRoom($redPlayer, $bluePlayer)
    {
        $roomId = uniqid('room_');
        $this->bindRoomWorker($redPlayer, $roomId);
        $this->bindRoomWorker($bluePlayer, $roomId);
    }

    private function bindRoomWorker($playerId, $roomId)
    {
        $playerFd = DataCenter::getPlayerFd($playerId);
        DataCenter::$server->bind($playerFd, crc32($roomId));
        DataCenter::setPlayerRoomId($playerId, $roomId);
        Sender::sendMessage($playerId, Sender::MSG_ROOM_ID, ['room_id' => $roomId]);
    }

    public static function startRoom($roomId, $playerId)
    {
        if (!isset(DataCenter::$global['rooms'][$roomId])) {
            DataCenter::$global['rooms'][$roomId] = [
                'id' => $roomId,
                'manager' => new Game()
            ];
        }
        /** @var Game $gameManager */
        $gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
        if (empty(count($gameManager->getPlayers()))) {
            //第一个玩家
            $gameManager->createPlayer($playerId, 6, 1);
            Sender::sendMessage($playerId, Sender::MSG_WAIT_PLAYER);
        } else {
            //第二个玩家
            $gameManager->createPlayer($playerId, 6, 10);
            Sender::sendMessage($playerId, Sender::MSG_ROOM_START);
            self::sendGameInfo($roomId);
        }
    }

    private static function sendGameInfo($roomId)
    {
        /**
         * @var Game $gameManager
         * @var Player $player
         */
        $gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
        $players = $gameManager->getPlayers();
        $mapData = $gameManager->getMapData();
//        foreach ($players as $player) {
//            $mapData[$player->getX()][$player->getY()] = $player->getId();
//        }
        foreach (array_reverse($players) as $player) {
            $mapData[$player->getX()][$player->getY()] = $player->getId();
        }
        foreach ($players as $player) {
            $data = [
                'players' => $players,
                'map_data' => self::getNearMap($mapData, $player->getX(), $player->getY()),
            ];
            Sender::sendMessage($player->getId(), Sender::MSG_GAME_INFO, $data);
        }
    }

    public static function getNearMap($mapData, $x, $y)
    {
        $result = [];
        for ($i = -1 * self::PLAYER_DISPLAY_LEN; $i <= self::PLAYER_DISPLAY_LEN; $i++) {
            $tmp = [];
            for ($j = -1 * self::PLAYER_DISPLAY_LEN; $j <= self::PLAYER_DISPLAY_LEN; $j++) {
                $tmp[] = $mapData[$x + $i][$y + $j] ?? 0;
            }
            $result[] = $tmp;
        }
        return $result;
    }

    public function movePlayer($direction, $playerId)
    {
        if (!in_array($direction, Player::DIRECTION)) {
            echo $direction;
            return;
        }
        $roomId = DataCenter::getPlayerRoomId($playerId);
        if (isset(DataCenter::$global['rooms'][$roomId])) {
            /** @var Game $gameManager */
            $gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
            $gameManager->playerMove($playerId, $direction);
            self::sendGameInfo($roomId);
            self::checkGameOver($roomId);
        }
    }

    private static function checkGameOver($roomId)
    {
        /**
         * @var Game $gameManager
         * @var Player $player
         */
        $gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
        if ($gameManager->isGameOver()) {
            $players = $gameManager->getPlayers();
            $winner = current($players)->getId();
            foreach ($players as $player) {
                Sender::sendMessage($player->getId(), Sender::MSG_GAME_OVER, ['winner' => $winner]);
                DataCenter::delPlayerRoomId($player->getId());
            }
            unset(DataCenter::$global['rooms'][$roomId]);
        }
    }
}