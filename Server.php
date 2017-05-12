<?php

/**
 * Created by PhpStorm.
 * User: MIOJI
 * Date: 2017/5/11
 * Time: 下午3:14
 */
class Server
{
    private $serv;

    public function __construct()
    {
        $this->serv = new swoole_server('0.0.0.0', 9501);
        $this->serv->set([
            'worker_num' => 1,
            'daemonize' => false,
            'backlog' => 128,
            'max_request' => 4,
            'log_file' => 'log/swoole.log',
        ]);
        $server = $this->serv;
        $process = new swoole_process(function ($process) use ($server) {
            while (true) {
                $msg = $process->read();
                foreach ($server->connections as $conn) {
                    $server->send($conn, $msg);
                }
            }
        });

        $this->serv->addProcess($process);

        $this->serv->on('Start', [$this, 'onStart']);
        $this->serv->on('Connect', [$this, 'onConnect']);
        $this->serv->on('Receive', [$this, 'onReceive']);
        $this->serv->on('Close', [$this, 'onClose']);

        $this->serv->start();
    }

    public function onStart($serv)
    {
        echo 'Start' . PHP_EOL;
    }

    public function onConnect($serv, $fd, $from_id)
    {
        $serv->send($fd, 'hello{' . $from_id . '}');
    }

    public function onReceive(swoole_server $serv, $fd, $from_id, $data)
    {
        echo 'Get Message From Client {' . $fd . '}:{' . $data . '}' . PHP_EOL;
        $serv->send($fd, $data);
    }

    public function onClose(swoole_server $serv, $fd, $from_id)
    {
        echo 'Client {' . $fd . '} close connection' . PHP_EOL;
        $serv->shutdown();
    }
}

$server = new Server();