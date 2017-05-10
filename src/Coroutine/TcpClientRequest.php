<?php

/**
 * @desc: 协程Tcp客户端
 * @author: leandre <niulingyun@camera360.com>
 * @date: 2017/3/21
 * @copyright All rights reserved.
 */

namespace PG\MSF\Coroutine;

use PG\MSF\Client\Tcp\TcpClient;

class TcpClientRequest extends Base
{
    public $tcpClient;
    public $data;

    public function __construct(TcpClient $tcpClient, string $data, string $path, int $timeout)
    {
        parent::__construct($timeout);
        $this->tcpClient = $tcpClient;
        $this->data = $data;

        $profileName = mt_rand(1, 9) . mt_rand(1, 9) . mt_rand(1, 9) . '#api-tcp:' . $path;
        $this->tcpClient->context->PGLog->profileStart($profileName);
        getInstance()->coroutine->IOCallBack[$this->tcpClient->context->PGLog->logId][] = $this;
        $this->send(function ($cli, $recData) use ($profileName) {
            $this->result = $recData;
            $this->responseTime = microtime(true);
            $cli->close();
            if (!empty($this->tcpClient->context->PGLog)) {
                $this->tcpClient->context->PGLog->profileEnd($profileName);
                $this->ioBack = true;
                $this->nextRun($this->tcpClient->context->PGLog->logId);
            }
        });
    }

    public function send($callback)
    {
        $this->tcpClient->send($this->data, $callback);
    }

    public function destroy()
    {
        $this->tcpClient->client->close();
        $this->tcpClient->context = null;
        unset($this->tcpClient->client);
        unset($this->tcpClient);
    }
}