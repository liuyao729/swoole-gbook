<?php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$server = new swoole_websocket_server('0.0.0.0', 9501);

$server->on('open', function (swoole_websocket_server $server, $request) use ($redis) {
	echo "server: handshake success with fd{$request->fd}\n";
	
	$redis->lPush('members', $request->fd);
});

$server->on('message', function (swoole_websocket_server $server, $frame) use ($redis) {
	
	echo "receive from {$frame->fd}:{$frame->data}, opcode:{$frame->opcode}, fin:{$frame->finish}\n";
	//$server->push($frame->fd, "this is server");
	$fd = $redis->lRange('members', 0, -1);
	if ($fd) {
		foreach ($fd AS $value) {
			$server->push($value, json_encode($fd)."{$value}:{$frame->data} online;\n");
		}
	}
	
});

$server->on('close', function ($serv, $fd) use ($redis) {
	echo "client {$fd} closed\n";
	$redis->lRem('members', $fd, 0);
});

$server->start();