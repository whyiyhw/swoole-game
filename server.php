<?php

use App\Manage\DataCenter;
use App\Model\Player;

require_once __DIR__ . '/vendor/autoload.php';

//$redId = "red_player";
//$blueId = "blue_player";
//创建游戏控制器
//$game = new \App\Manage\Game();
//添加玩家
//$game->createPlayer($redId, 6, 1);
//添加玩家
//$game->createPlayer($blueId, 6, 10);
//移动坐标
//$game->playerMove($redId, 'up');
//$game->playerMove($redId, 'up');
//$game->playerMove($redId, 'up');
//$game->playerMove($redId, 'up');
//打印地图
//$game->printGameMap();
//
//for ($i = 0; $i <= 300; $i++) {
//    $direct = mt_rand(0, 3);
//    $game->playerMove($redId, Player::DIRECTION[$direct]);
//    if ($game->isGameOver()) {
//        $game->printGameMap();
//        echo "game_over" . PHP_EOL;
//        break;
//    }
//    $direct = mt_rand(0, 3);
//    $game->playerMove($blueId, Player::DIRECTION[$direct]);
//    if ($game->isGameOver()) {
//        $game->printGameMap();
//        echo "game_over" . PHP_EOL;
//        break;
//    }
////打印移动后战局
//    echo PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL;
//    $game->printGameMap();
//    usleep(400000);
//}

class Server
{
    const HOST = '0.0.0.0';
    const PORT = 9501;
    const FRONT_PORT = 9502;

    const CLIENT_CODE_MATCH_PLAYER = 600;
    const CLIENT_CODE_START_ROOM = 601;
    const CLIENT_CODE_MOVE_PLAYER = 602;

    const CONFIG = [
        'worker_num' => 4,
        'enable_static_handler' => true,
        'task_worker_num' => 4,
        'dispatch_mode' => 5,
        'document_root' =>
            '/var/www/html/public',
    ];

    private $ws;
    private $logic;

    public function __construct()
    {
        $this->logic = new \App\Manage\Logic();

        $this->ws = new \Swoole\WebSocket\Server(self::HOST, self::PORT);
        $this->ws->set(self::CONFIG);
        $this->ws->listen(self::HOST, self::FRONT_PORT, SWOOLE_SOCK_TCP);
        $this->ws->on('start', [$this, 'onStart']);
        $this->ws->on('workerStart', [$this, 'onWorkerStart']);
        $this->ws->on('open', [$this, 'onOpen']);
        $this->ws->on('message', [$this, 'onMessage']);
        $this->ws->on('close', [$this, 'onClose']);
        $this->ws->on('task', [$this, 'onTask']);
        $this->ws->on('finish', [$this, 'onFinish']);
        $this->ws->start();
    }

    public function onStart($server)
    {
        swoole_set_process_name('hide-and-seek');
        DataCenter::initDataCenter();
        echo sprintf("master start (listening on %s:%d)\n",
            self::HOST, self::PORT);
    }

    public function onWorkerStart(swoole_websocket_server $server, $workerId)
    {
        DataCenter::$server = $server;
//        echo "server: onWorkStart,worker_id:{$server->worker_id}\n";
        echo "server: onWorkStart,worker_id:{$workerId}\n";
    }

    public function onOpen(swoole_websocket_server $server, swoole_http_request $request)
    {
        $playerId = $request->get['player_id'];
        DataCenter::setPlayerInfo($playerId, $request->fd);
        DataCenter::log("server: onOpen,worker_id:{$server->worker_id}, fd:{$request->fd}",);
    }

    public function onClose(swoole_websocket_server $server, $fd)
    {
        DataCenter::log("server: onClose,worker_id:{$server->worker_id} ,fd:{$fd}");
        DataCenter::delPlayerInfo($fd);
    }

    public function onMessage(swoole_websocket_server $server, swoole_websocket_frame $frame)
    {
        DataCenter::log("server: onMessage,worker_id:{$server->worker_id} ,fd:{$frame->fd}", $frame->data);
        $data = json_decode($frame->data, true);
        $playerId = DataCenter::getPlayerID($frame->fd);
        switch ($data['code']) {
            case self::CLIENT_CODE_MATCH_PLAYER:
                $this->logic->matchPlayer($playerId);
                break;
            case self::CLIENT_CODE_START_ROOM:
                $this->logic->startRoom($data['room_id'], $playerId);
                break;
            case self::CLIENT_CODE_MOVE_PLAYER:
                $this->logic->movePlayer($data['direction'], $playerId);
                break;
        }
//        $server->push($frame->fd,"we can get your message , your message is {$frame->data}");
    }

    public function onTask($server, $taskId, $srcWorkerId, $data)
    {
        DataCenter::log("onTask", $data);
        $result = [];
        switch ($data['code']) {
            //执行task方法
            case \App\Manage\TaskManager::TASK_CODE_FIND_PLAYER :
                $res = \App\Manage\TaskManager::findPlayer();
                if (!empty($res)) {
                    $result['data'] = $res;
                }
                break;
        }
        if (!empty($result)) {
            $result['code'] = $data['code'];
            return $result;
        }

    }

    public function onFinish($server, $taskId, $data)
    {
        DataCenter::log("onFinish", $data);
        switch ($data['code']) {
            case \App\Manage\TaskManager::TASK_CODE_FIND_PLAYER:
                $this->logic->createRoom($data['data']['red_player'],
                    $data['data']['blue_player']);
                break;
        }
    }
}

new Server();