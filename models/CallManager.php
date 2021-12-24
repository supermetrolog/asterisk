<?php

namespace app\models;

use app\models\AsteriskEvents\EventDialBegin;
use app\models\AsteriskEvents\EventDialBeginHandler;
use app\models\AsteriskEvents\EventDialEnd;
use Exception;
use yii\base\Model;
use PAMI\Client\Impl\ClientImpl as PamiClient;
use PAMI\Message\Event\EventMessage;
use PAMI\Listener\IEventListener;
use PAMI\Message\Event\DialEvent;
use PAMI\Message\Action\OriginateAction;
use PAMI\Message\Action\BridgeAction;
use PAMI\Message\Action\SIPPeersAction;
use yii\helpers\ArrayHelper;
use app\models\Logger;

class CallManager extends Model
{
    private $client;
    private $logger;
    private $options;
    public function __construct($options)
    {
        $this->logger = new Logger();
        $this->options = $options;
    }
    public function connect()
    {
        $this->client = new PamiClient($this->options);
        $this->client->open();
        $this->client->registerEventListener(
            function (EventMessage $event) {
                $this->eventListenerHandle($event);
            }
        );
    }
    private function eventListenerHandle(EventMessage $event)
    {
        $data = $event->getKeys();

        switch ($event->getName()) {
            case 'Newchannel':
                $this->logger->notice("{$event->getName()} -> Открыт канал для звонка на номер:  {$data['calleridnum']}({$data['calleridname']})");
                break;
            case 'DialBegin':
                EventDialBegin::handler($event)->run();
                break;
            case 'DialEnd':
                EventDialEnd::handler($event)->run();
                break;
            default:
                // $this->logger->notice($event->getName());
                break;
        }
    }

    public function call($options = null)
    {
        if (is_null($options)) {
            throw new Exception('Call fn require $options argv');
        }
        $defaultOptions = [
            'from' => null,
            'to' => null,
            'context' => null,
            'priority' => null,
            'caller_id' => 'server'
        ];
        $options = ArrayHelper::merge($defaultOptions, $options);
        foreach ($options as $key => $value) {
            if (!$value) {
                throw new Exception("Required options value not passed $key");
            }
        }
        $originateMsg = new OriginateAction($options['from']); // Звоним на номер 101
        $originateMsg->setContext($options['context']);
        $originateMsg->setPriority($options['priority']);
        $originateMsg->setExtension($options['to']); // После того как 101 берет трубку он набирает на 102
        $originateMsg->setCallerId($options['caller_id']);
        // $originateMsg->setTimeout(5000);
        $this->send($originateMsg);
    }
    private function send($data)
    {
        $response = $this->client->send($data);
        $this->logger->notice($response->getMessage());
        return $response;
    }
    public function loop()
    {
        while (true) {
            $this->client->process();
            usleep(50000);
        }
    }
    public function disconnect()
    {
        $this->client->close();
    }
    public function __destruct()
    {
        $this->client->close();
    }
}
