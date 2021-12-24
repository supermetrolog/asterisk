<?php

namespace app\models\AsteriskEvents;

use app\models\CallList;
use app\models\UserProfile;

class EventDialBegin extends AbstractEvent
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
        $this->logger->notice("{$this->event->getName()} -> Начало звонка на номер {$data['calleridnum']}({$data['calleridname']}) с номера {$data['connectedlinenum']}({$data['connectedlinename']})");

        if ($data['connectedlinename'] == 'server') {
            return;
        }
        $userCallerIds = UserProfile::find()->select('caller_id')->asArray()->all();
        foreach ($userCallerIds as $item) {

            if ($item['caller_id'] == $data['connectedlinenum']) {
                $this->saveCallData($data['connectedlinenum'], $data['connectedlinenum'], $data['calleridnum'], CallList::TYPE_OUTGOING, $data['destuniqueid']);
            }
            if ($item['caller_id'] == $data['calleridnum']) {
                $this->saveCallData($data['calleridnum'], $data['connectedlinenum'], $data['calleridnum'], CallList::TYPE_INCOMING, $data['destuniqueid']);
            }
        }
    }

    private function saveCallData($caller_id, $from, $to, $type, $uniqueid)
    {
        $options = [
            'caller_id' => $caller_id,
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'uniqueid' => $uniqueid
        ];
        $model = new CallList($options);
        if ($model->save()) {
            $this->logger->notice('Информация о звонке сохранена');
        } else {
            $this->logger->notice(json_encode($model->getErrors()));
        }
    }
}
