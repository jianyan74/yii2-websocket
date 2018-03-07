<?php
namespace jianyan\websocket;

use Yii;
use swoole_table;
use swoole_websocket_server;

/**
 * 长连接
 *
 * Class WebSocketServer
 * @package console\controllers
 */
class WebSocketServer
{
    protected $_host;

    protected $_port;

    protected $_mode;

    protected $_socketType;

    protected $_config;

    /**
     * 服务
     *
     * @var
     */
    protected $_server;

    /**
     * 基于共享内存和锁实现的超高性能，并发数据结构
     *
     * @var
     */
    protected $_table;

    /**
     * WebSocket constructor.
     * @param $host
     * @param $port
     * @param $config
     */
    public function __construct($host, $port, $mode, $socketType, $config)
    {
        $this->_host = $host;
        $this->_port = $port;
        $this->_mode = $mode;
        $this->_socketType = $socketType;

        $this->_config = $config;

        // 创建内存表
        $this->createTable();
    }

    public function run()
    {
        // 启动进程
        $this->_server = new swoole_websocket_server($this->_host, $this->_port, $this->_mode, $this->_socketType | SWOOLE_SSL);
        $this->_server->set($this->_config);
        $this->_server->on('open', [$this, 'onOpen']);
        $this->_server->on('message', [$this, 'onMessage']);
        $this->_server->on('task', [$this, 'onTask']);
        $this->_server->on('finish', [$this, 'onFinish']);
        $this->_server->on('close', [$this, 'onClose']);
        $this->_server->start();
    }

    /**
     * 开启连接
     *
     * @param $server
     * @param $frame
     */
    public function onOpen($server, $frame)
    {
        echo "server: handshake success with fd{$frame->fd}\n";
        echo "server: {$frame->data}\n";

        $this->_table->set($frame->fd, ['fd'=>$frame->fd]);
    }

    /**
     * 消息
     * @param $server
     * @param $frame
     * @throws \Exception
     */
    public function onMessage($server, $frame)
    {
        // 调试信息
        echo $frame->data . "\n";
        //echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";

        $message = json_decode($frame->data, true);
        if (!$message)
        {
            echo "没有消息内容";
            return true;
        }

        // 业务逻辑
        switch ($message['type'])
        {
            // 心跳
            case 'pong':
                return true;
                break;

            // 进入房间(登录)
            case 'login':
                // 判断是否有房间号
                if(!isset($message['room_id']))
                {
                    throw new \Exception("\$message['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }

                $_SESSION['room_id'] = $message['room_id'];
                $_SESSION['client_name'] = $message['client_name'];

                // 转播给当前房间的所有客户端，xx进入聊天室 message {type:login, client_id:xx, name:xx}
                $new_message = [
                    'type' => $message['type'],
                    'client_id' => $frame->fd,
                    'name' => $message['client_name'],
                    'time' => date('Y-m-d H:i:s'),
                ];

                //投递到task广播消息
                $server->task(json_encode($new_message));
                break;

            // 评论消息
            case 'say':
                // 非法请求
                if(!isset($_SESSION['room_id']))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }

                $room_id = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];
                $message['emoji_id'] = isset($message['emoji_id']) ?? '';

                // 私聊
                if($message['to_client_id'] != 'all')
                {
                    $new_message = [
                        'type' => $message['type'],
                        'from_client_id'=> $frame->fd,
                        'from_client_name' =>$client_name,
                        'to_client_id' => $message['to_client_id'],
                        'emoji_id' => $message['emoji_id'],
                        'content' => nl2br(htmlspecialchars($message['content'])),
                        'time' => date('Y-m-d H:i:s'),
                    ];

                    // 私发
                    $server->push($frame->fd, json_encode($new_message));
                }

                $new_message = [
                    'type' => $message['type'],
                    'from_client_id'=> $frame->fd,
                    'from_client_name' =>$client_name,
                    'to_client_id' => 'all',
                    'emoji_id' => $message['emoji_id'],
                    'content' => nl2br(htmlspecialchars($message['content'])),
                    'time'=> date('Y-m-d H:i:s'),
                ];

                // 广播消息
                $server->task(json_encode($new_message));
                break;

            // 礼物
            case 'gift':

                $client_name = $_SESSION['client_name'];
                $new_message = [
                    'type' => $message['type'],
                    'from_client_id'=> $frame->fd,
                    'from_client_name' => $client_name,
                    'to_client_id' => 'all',
                    'gift_id' => $message['gift_id'],
                    'time'=> date('Y-m-d H:i:s'),
                ];

                // 广播消息
                $server->task(json_encode($new_message));
                break;
        }

        return true;
    }

    /**
     * 关闭连接
     *
     * @param $server
     * @param $fd
     */
    public function onClose($server, $fd)
    {
        echo "client {$fd} closed\n";
        // 删除
        $this->_table->del($fd);
    }

    /**
     * 处理异步任务
     *
     * @param $server
     * @param $task_id
     * @param $from_id
     * @param $data
     */
    public function onTask($server, $task_id, $from_id, $data)
    {
        echo "新 AsyncTask[id=$task_id]" . PHP_EOL;

        $server->finish($data);
    }

    /**
     * 处理异步任务的结果
     *
     * @param $server
     * @param $task_id
     * @param $data
     */
    public function onFinish($server, $task_id, $data)
    {
        //广播
        foreach ($this->_table as $cid => $info)
        {
            $server->push($cid, $data);
        }

        echo "AsyncTask[$task_id] 完成: $data" . PHP_EOL;
    }
}