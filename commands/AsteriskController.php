<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use app\models\PhpARI;
use PAMI\Client\Impl\ClientImpl as PamiClient;
use PAMI\Message\Event\EventMessage;
use PAMI\Listener\IEventListener;
use PAMI\Message\Event\DialEvent;
use PAMI\Message\Action\OriginateAction;
use PAMI\Message\Action\BridgeAction;
use PAMI\Message\Action\SIPPeersAction;
use Yii;
use app\models\CallManager;
// include('phpagi.php');
/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AsteriskController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     * @return int Exit code
     */
    public function actionIndex()
    {

        // echo "Starting ARI Connection\n";
        // $ariConnector = new \phpari();
        // echo "Active Channels: \n";
        // var_dump($ariConnector->channels()->channel_list());
        // echo "Ending ARI Connection\n";
        $basicAriClient = new PhpAri("hello-world");

        $basicAriClient->stasisLogger->info("Starting Stasis Program... Waiting for handshake...");
        $basicAriClient->StasisAppEventHandler();

        $basicAriClient->stasisLogger->info("Initializing Handlers... Waiting for handshake...");
        $basicAriClient->StasisAppConnectionHandlers();
        $basicAriClient->stasisLogger->info("Connecting... Waiting for handshake...");

        $basicAriClient->execute();
        return ExitCode::OK;
    }
    public function actionPami()
    {
        $options = array(
            'host' => '10.1.3.110',
            'scheme' => 'tcp://',
            'port' => 5038,
            'username' => 'test',
            'secret' => 'fuck',
            'connect_timeout' => 10,
            'read_timeout' => 300000 // 5 min
        );
        $client = new PamiClient($options);
        $client->open();
        $client->registerEventListener(
            function (EventMessage $event) {
                static $i = 1;
                if ($event->getName() != 'VarSet' && $event->getName() != 'Newexten') {
                    echo "\n\n\n\n";
                    echo "$i";
                    print_r($event);
                    $i++;
                }
            }
        );

        $originateMsg = new OriginateAction('SIP/101'); // Звоним на номер 101
        $originateMsg->setContext('from-internal');
        $originateMsg->setPriority('1');
        $originateMsg->setExtension('102'); // После того как 101 берет трубку он набирает на 102
        // $originateMsg->setCallerId('fuck');ы
        // $originateMsg->setTimeout(5000);
        $response = $client->send($originateMsg);
        if (!$response->isSuccess()) {
            var_dump($response->getMessage()); // Ваш телефон не подключен, либо вы отклонили вызов!
        }
        var_dump($response);

        // var_dump($client->send(new SIPPeersAction()));

        // var_dump($client->send(new BridgeAction('SIP/101', 'SIP/102', true)));
        while (true) {
            $client->process();
            usleep(50000);
        }
        $client->close();
    }
    public function actionCallerManager()
    {
        $asterisk_options = require Yii::getAlias('@app') . '/config/asterisk_ami.php';
        $cm = new CallManager($asterisk_options);
        $cm->connect();
        $cm->call([
            'from' => 'SIP/101',
            'to' => '102',
            'context' => 'from-internal',
            'priority' => '1'
        ]);
        $cm->loop();
    }
}
