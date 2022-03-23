<?php

namespace app\models\AsteriskEvents;

use app\models\CallList;
use app\models\UserProfile;

class EventDialEnd extends AbstractEvent
{
    public function run()
    {
        $data = $this->event->getKeys();
        if ($data['destcalleridnum']) {
            $data['calleridnum'] = $data['destcalleridnum'];
            $data['calleridname'] = $data['destcalleridname'];
            $data['connectedlinenum'] = $data['destconnectedlinenum'];
            $data['connectedlinename'] = $data['destconnectedlinename'];
        }
        $this->logger->notice("{$this->event->getName()} -> Конец звонка на номер : {$data['calleridnum']}({$data['calleridname']}) с номера: {$data['connectedlinenum']}({$data['connectedlinename']})");
        $this->updateCallStatus($data['dialstatus'], $data['destuniqueid']);

        switch ($data['dialstatus']) {
            case 'ANSWER':
                $this->logger->notice("Абонент -> {$data['calleridnum']}({$data['calleridname']}) взял трубку!");
                break;
            case 'BUSY':
                $this->logger->notice("Абонент -> {$data['calleridnum']}({$data['calleridname']}) сбросил вызов!");
                break;
            case 'NOANSWER':
                $this->logger->notice("Абонент -> {$data['calleridnum']}({$data['calleridname']}) сбросил вызов!");
                break;
            case 'CANCEL':
                $this->logger->notice("Абонент -> {$data['calleridnum']}({$data['calleridname']}) сбросил вызов!");
                break;
            default:
                $this->logger->notice("Статус -> {$data['dialstatus']}");

                break;
        }
        if ($data['connectedlinename'] == 'server') {
            return;
        }
    }

    private function updateCallStatus($status, $uniqueid)
    {
        $models = CallList::find()->where(['uniqueid' => $uniqueid])->all();
        foreach ($models as $model) {
            $model->call_ended_status = $status;
            if ($model->save()) {
                $this->logger->notice('Информация о звонке сохранена');
            } else {
                $this->logger->notice(json_encode($model->getErrors()));
            }
        }
    }
}
