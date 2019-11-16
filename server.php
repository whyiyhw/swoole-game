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
    const CONFIG = [
        'worker_num' => 4,
        'enable_static_handler' => true,
        'document_root' =>
            '/var/www/html/public',
    ];

    private $ws;

    public function __construct()
    {
        $this->ws = new \Swoole\WebSocket\Server(self::HOST, self::PORT);
        $this->ws->set(self::CONFIG);
        $this->ws->listen(self::HOST, self::FRONT_PORT, SWOOLE_SOCK_TCP);
        $this->ws->on('start', [$this, 'onStart']);
        $this->ws->on('workerStart', [$this, 'onWorkerStart']);
        $this->ws->on('open', [$this, 'onOpen']);
        $this->ws->on('message', [$this, 'onMessage']);
        $this->ws->on('close', [$this, 'onClose']);
        $this->ws->start();
    }

    public function onStart($server)
    {
        swoole_set_process_name('hide-and-seek');
        echo sprintf("master start (listening on %s:%d)\n",
            self::HOST, self::PORT);
    }

    public function onWorkerStart(swoole_websocket_server $server, $workerId)
    {
        echo "server: onWorkStart,worker_id:{$server->worker_id}\n";
    }

    public function onOpen(swoole_websocket_server $server, swoole_http_request $request)
    {
        DataCenter::log(
            "server: onOpen",
            "server: onOpen,worker_id:{$server->worker_id};server: onOpen,data:{$request->fd}"
        );
    }

    public function onClose(swoole_websocket_server $server, $fd)
    {
        echo "server: onClose,worker_id:{$server->worker_id}\n";
        echo "server: onClose,fd is :{$fd}\n";
        DataCenter::log(sprintf('client close fd：%d', $fd));
    }

    public function onMessage(swoole_websocket_server $server, swoole_websocket_frame $frame)
    {
        echo "server: onMessage,worker_id:{$server->worker_id}\n";
        echo "server: onMessage,massage is :{$frame->data}\n";
        $server->push($frame->fd,"we can get your message , your message is {$frame->data}");
    }
}

new Server();