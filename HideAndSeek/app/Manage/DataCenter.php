<?php


namespace App\Manage;


use App\Lib\Redis;
use swoole_websocket_server;

class DataCenter
{
    const PREFIX_KEY = "game";

    public static $global; // 全局数据访问

    /**
     * @var swoole_websocket_server $server
     */
    public static $server; // 全局服务访问

    public static function log($info, $context = [], $level = 'INFO')
    {
        if ($context) {
            echo sprintf("[%s][%s]: %s %s\n", date('Y-m-d H:i:s'), $level, $info,
                json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            echo sprintf("[%s][%s]: %s\n", date('Y-m-d H:i:s'), $level, $info);
        }
    }

    public static function redis()
    {
        return Redis::getInstance();
    }

    public static function getPlayerWaitListLen()
    {
        $key = self::PREFIX_KEY . ":player_wait_list";
        return self::redis()->lLen($key);
    }

    public static function pushPlayerToWaitList($playerId)
    {
        $key = self::PREFIX_KEY . ":player_wait_list";
        self::redis()->lPush($key, $playerId);
    }

    public static function popPlayerFromWaitList()
    {
        $key = self::PREFIX_KEY . ":player_wait_list";
        return self::redis()->rPop($key);
    }

    public static function getPlayerFd($playerId)
    {
        $key = self::PREFIX_KEY . ":player_fd:" . $playerId;
        return self::redis()->get($key);
    }

    public static function setPlayerFd($playerId, $playerFd)
    {
        $key = self::PREFIX_KEY . ":player_fd:" . $playerId;
        self::redis()->set($key, $playerFd);
    }

    public static function delPlayerFd($playerId)
    {
        $key = self::PREFIX_KEY . ":player_fd:" . $playerId;
        self::redis()->del($key);
    }

    public static function getPlayerId($playerFd)
    {
        $key = self::PREFIX_KEY . ":player_id:" . $playerFd;
        return self::redis()->get($key);
    }

    public static function setPlayerId($playerFd, $playerId)
    {
        $key = self::PREFIX_KEY . ":player_id:" . $playerFd;
        self::redis()->set($key, $playerId);
    }

    public static function delPlayerId($playerFd)
    {
        $key = self::PREFIX_KEY . ":player_id:" . $playerFd;
        self::redis()->del($key);
    }

    public static function setPlayerInfo($playerId, $playerFd)
    {
        self::setPlayerId($playerFd, $playerId);
        self::setPlayerFd($playerId, $playerFd);
    }

    public static function delPlayerInfo($playerFd)
    {
        $playerId = self::getPlayerId($playerFd);
        self::delPlayerFd($playerId);
        self::delPlayerId($playerFd);
    }

    public static function initDataCenter()
    {
        //清空匹配队列
        $key = self::PREFIX_KEY . ':player_wait_list';
        self::redis()->del($key);
        //清空玩家ID
        $key = self::PREFIX_KEY . ':player_id*';
        $values = self::redis()->keys($key);
        foreach ($values as $value) {
            self::redis()->del($value);
        }
        //清空玩家FD
        $key = self::PREFIX_KEY . ':player_fd*';
        $values = self::redis()->keys($key);
        foreach ($values as $value) {
            self::redis()->del($value);
        }
        //清空玩家房间ID
        $key = self::PREFIX_KEY . ':player_room_id*';
        $values = self::redis()->keys($key);
        foreach ($values as $value) {
            self::redis()->del($value);
        }
    }

    public static function setPlayerRoomId($playerId, $roomId)
    {
        $key = self::PREFIX_KEY . ':player_room_id:' . $playerId;
        self::redis()->set($key, $roomId);
    }

    public static function getPlayerRoomId($playerId)
    {
        $key = self::PREFIX_KEY . ':player_room_id:' . $playerId;
        return self::redis()->get($key);
    }

    public static function delPlayerRoomId($playerId)
    {
        $key = self::PREFIX_KEY . ':player_room_id:' . $playerId;
        self::redis()->del($key);
    }
}