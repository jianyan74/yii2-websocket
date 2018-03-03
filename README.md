## Yii2 WebSocket

即时通讯,直播间demo

### 前提安装swoole

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
 'web-socket' => [
     'class' => 'jianyan\websocket\WebSocketController',
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
  php ./yii web-socket/start
  # 停止 
  php ./yii web-socket/stop
  # 重启 
  php ./yii web-socket/restart
   ```