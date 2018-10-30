<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'language'=>'ru-RU',
    'defaultRoute'=>'bt',
    'TimeZone'=>'Europe/Moscow',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'on '.\yii\web\Application::EVENT_BEFORE_ACTION=>['app\models\BtUser','todoactions'],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'iRhKVdMA_YWJrNFKriPaYSZ4Q2-jUXRB',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\BtUser',
            'enableAutoLogin' => true,
            'on '.\yii\web\User::EVENT_AFTER_LOGIN=>['app\models\BtUser','userloginupd'],
        ],
        'formatter'=>[
            'class'=>'app\components\BtFormatter',//::className(),
        ],
        'errorHandler' => [
            //'errorAction' => 'site/error',
            'errorAction' => 'bt/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'session'=>[
            'class'=>'yii\web\DbSession',
            'sessionTable'=>'sessi',
            'timeout'=>3600,
        ],
        
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing'=>true,
            'rules' => [
                // редактирование юзера 
                ['pattern'=>'users/<uid:\d+>','route'=>'bt/user-edit'],
                // добавление юзера 
                ['pattern'=>'user-add','route'=>'bt/user-edit'],
                // полчить данные майнеров в json 
                'users/<uid:\d+>/miners/json'=>'miner/user-minerslist-getdata',
                // файнеры онлайн
                'users/<uid:\d+>/miners/online'=>'miner/user-minerslist-online',
                // обновление даных майнера .. 
                'users/<uid:\d+>/miners/<mid:\d+>/up'=>'miner/user-miner-statup',
                // просмотр данных майнера .. 
                'users/<uid:\d+>/miners/<mid:\d+>/view'=>'miner/user-miner-view',
                // перезапуск майнера ..
                'users/<uid:\d+>/miners/<mid:\d+>/reboot'=>'miner/user-miner-toreboot',
                // редактирование майнера . 
                'users/<uid:\d+>/miners/<mid:\d+>'=>'miner/user-miner-edit',
                // список майнеров юзера . 
                'users/<uid:\d+>/miners'=>'miner/user-minerslist',
                
                // добавление майнера . 
                'users/<uid:\d+>/miner-add'=>'miner/user-miner-edit',
                // добавление майнера по ip Диапазну  
                'users/<uid:\d+>/miner-addperip'=>'miner/user-miner-addperiprange',
                // пакетное добавление майнеров ... 
                'users/<uid:\d+>/miner-multiaddminers'=>'miner/add-miners-per-ip-todo',
                // массвоеобновление пулов .. 
                'users/<uid:\d+>/miners-upd-pools'=>'miner/user-miner-pools-upd',
                // список пользователей .. 
                'users/<role>'=>'bt/users',
                // синоним списка пользователей . 
                'users'=>'bt/users',

                

                ''=>'bt',


                
            ],
        ],
        
    ],
    'params' => $params,
];

if (YII_DEBUG) {

    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '109.191.205.45'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
