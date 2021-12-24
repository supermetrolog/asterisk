<?php

namespace app\models\AsteriskEvents;

use PAMI\Message\Event\EventMessage;
use yii\base\Model;
use app\models\Logger;
use Yii;

abstract class AbstractEvent extends Model
{
    protected $logger;
    protected $event;
    public function __construct(EventMessage $event)
    {
        $this->logger = new Logger();
        $this->event = $event;
        if (!Yii::$app->db->isActive) {
            Yii::$app->db->open();
        }
        echo "\n\nDB isActive: " . Yii::$app->db->isActive . "\n\n";
    }
    abstract public function run();
    public static function handler(EventMessage $event)
    {
        return new static($event);
    }
}
