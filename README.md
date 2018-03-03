## Yii2 WebSocket

可以做即时通讯,小程序的小游戏等等

### 前提 

服务器安装swoole

```
 git clone https://github.com/swoole/swoole-src.git
 cd swoole-src
 phpize
 ./configure --enable-openssl -with-php-config=[PATH] #注意[PATH]为你的php地址 开启ssl用
 make && make install
 ```
### 安装
  
composer执行

```
composer require "jianyan74/yii2-websocket"
```

或者在 `composer.json` 加入

```
"jianyan74/yii2-websocket": "^1.0"
```
### 配置
 
 在 `console/config/main.php` 加入以下配置。（注意：配置在controllerMap里面）
 
 ```
// webSocket
'websocket' => [
    'class' => 'jianyan\websocket\WebSocketController',
    'server' => 'jianyan\websocket\WebSocketServer',
    'host' => '0.0.0.0',// 监听地址
    'port' => 9501,// 监听端口
    'config' => [// 标准的swoole配置项都可以再此加入
        'daemonize' => false,// 守护进程执行
        'ssl_cert_file' => '',
        'ssl_key_file' => '',
        'pid_file' => __DIR__ . '/../../backend/runtime/logs/server.pid',
        'log_file' => __DIR__ . '/../../backend/runtime/logs/swoole.log',
        'log_level' => 0,
    ],
],
 ```
 
 ### 使用
 
  ```
  # 启动 
  php ./yii websocket/start
  # 停止 
  php ./yii websocket/stop
  # 重启 
  php ./yii websocket/restart
   ```
   
### 测试

```
<script>
    var wsl = 'wss://[to your url]:9501';
    ws = new WebSocket(wsl);// 新建立一个连接
    // 如下指定事件处理
    ws.onopen = function () {
        // ws.send('Test!');
    };
    // 接收消息
    ws.onmessage = function (evt) {
        console.log(evt.data);
        /*ws.close();*/
    };
    // 关闭
    ws.onclose = function (evt) {
        console.log('WebSocketClosed!');
    };
    // 报错
    ws.onerror = function (evt) {
        console.log('WebSocketError!');
    };
</script>
```