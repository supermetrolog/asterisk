<?php

namespace app\models;

use yii\base\Model;

class Logger extends Model
{
    private $filepath;
    private $i = 1;
    public function __construct($filepath = null)
    {
        $this->filepath = $filepath;
    }

    public function notice($message)
    {
        if ($this->filepath) {
            return false;
        }
        // echo "Logger: [$this->i]\n";
        print_r($message);
        echo "\n";
        $this->i++;
    }
}
