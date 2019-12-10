<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Swoole\Http\Request;
use Swoole\Server;
use Swoole\Websocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;

class WebSocketController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    public function onMessage(WebSocketServer $server, Frame $frame): void
    {

         $data = json_decode($frame->data, true);
         switch ($data['from']){
             case 'client':
                 if($data['act'] == 'open'){
                     $server->push($frame->fd, (string)json_encode(array('data' => $frame->fd, 'act'=> 'open')));
                 }
                 break;
             case 'server':
                 $server->push((int)$data['toFrameId'], (string)json_encode(array('data' => $data['msg'], 'act'=> 'notice')));
                 break;
             default:
                 break;
         }

    }

    public function onClose(Server $server, int $fd, int $reactorId): void
    {
//        var_dump('closed');
    }

    public function onOpen(WebSocketServer $server, Request $request): void
    {
        $server->push($request->fd, (string)json_encode(array('data' => $request->fd, 'act'=> 'open')));
    }
}
