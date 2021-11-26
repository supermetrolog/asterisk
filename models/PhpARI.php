<?php

namespace app\models;

use channels;
use Exception;
use yii\helpers\ArrayHelper;

$pathinfo = pathinfo($_SERVER['PHP_SELF']);
$dir = $pathinfo['dirname'] . "/";


/* START YOUR MODIFICATIONS HERE */

class PhpAri
{

    private $ariEndpoint;
    private $stasisClient;
    private $stasisLoop;
    private $phpariObject;
    private $stasisChannelID;
    private $dtmfSequence = "";
    private $stasisEvents;
    public $stasisLogger;

    public function __construct($appname = NULL)
    {
        try {
            if (is_null($appname))
                throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Stasis application name must be defined!", 500);

            $this->phpariObject = new \phpari($appname);

            $this->ariEndpoint  = $this->phpariObject->ariEndpoint;
            $this->stasisClient = $this->phpariObject->stasisClient;
            $this->stasisLoop   = $this->phpariObject->stasisLoop;
            $this->stasisLogger = $this->phpariObject->stasisLogger;
            $this->stasisEvents = $this->phpariObject->stasisEvents;
        } catch (Exception $e) {
            echo $e->getMessage();
            exit(99);
        }
    }

    public function setDtmf($digit = NULL)
    {
        try {

            $this->dtmfSequence .= $digit;

            return TRUE;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    // process stasis events
    public function StasisAppEventHandler()
    {
        $this->stasisEvents->on('StasisStart', function ($event) {
            $this->stasisLogger->notice("Event received: StasisStart");
            $this->stasisChannelID = $event->channel->id;
            $this->phpariObject->channels()->channel_answer($this->stasisChannelID);
            $this->phpariObject->channels()->channel_playback($this->stasisChannelID, 'sound:demo-thanks', NULL, NULL, NULL, 'play1');
        });

        $this->stasisEvents->on('StasisEnd', function ($event) {
            /*
                 * The following section will produce an error, as the channel no longer exists in this state - this is intentional
                 */
            $this->stasisLogger->notice("Event received: StasisEnd");
            if (!$this->phpariObject->channels()->channel_delete($this->stasisChannelID))
                $this->stasisLogger->notice("Error occurred: " . $this->phpariObject->lasterror);
        });


        $this->stasisEvents->on('PlaybackStarted', function ($event) {
            $this->stasisLogger->notice("+++ PlaybackStarted +++ " . json_encode($event->playback) . "\n");
        });

        $this->stasisEvents->on('PlaybackFinished', function ($event) {
            switch ($event->playback->id) {
                case "play1":
                    $this->phpariObject->channels()->channel_playback($this->stasisChannelID, 'sound:demo-congrats', NULL, NULL, NULL, 'play2');
                    break;
                case "play2":
                    $this->phpariObject->channels()->channel_playback($this->stasisChannelID, 'sound:demo-echotest', NULL, NULL, NULL, 'end');
                    break;
                case "end":
                    $this->phpariObject->channels()->channel_continue($this->stasisChannelID);

                    break;
            }
        });

        $this->stasisEvents->on('ChannelDtmfReceived', function ($event) {
            $this->setDtmf($event->digit);
            $this->stasisLogger->notice("+++ DTMF Received +++ [" . $event->digit . "] [" . $this->dtmfSequence . "]\n");
            switch ($event->digit) {
                case "*":
                    $this->dtmfSequence = "";
                    $this->stasisLogger->notice("+++ Resetting DTMF buffer\n");
                    break;
                case "#":
                    $this->stasisLogger->notice("+++ Playback ID: " . $this->phpariObject->playbacks()->get_playback());
                    $this->phpariObject->channels()->channel_continue($this->stasisChannelID, "demo", "s", 1);
                    break;
                default:
                    break;
            }
        });
    }

    public function StasisAppConnectionHandlers()
    {
        try {
            $this->stasisClient->on("request", function ($headers) {
                $this->stasisLogger->notice("Request received!");
            });

            $this->stasisClient->on("handshake", function () {
                $this->stasisLogger->notice("Handshake received!");
            });

            $this->stasisClient->on("message", function ($message) {
                // $event = json_decode($message->getData());
                $event = json_decode($message->getData());
                // $event2 = json_decode($message->getData(), true);
                // if (ArrayHelper::keyExists('channel', $event2)) {
                //     $chData = $event2['channel'];
                //     // $channel->channel_continue($chData['id'], $chData['dialplan']['context'], $chData['dialplan']['exten'], $chData['dialplan']['priority']);
                //     $this->phpariObject->channels()->channel_continue($chData['id'], 'from-internal-custom', $chData['dialplan']['exten'], 2);
                // }
                // $this->stasisLogger->notice('Received event: ' . $event->type);

                $this->stasisLogger->notice('Received event: ' . $event->type);
                $this->stasisEvents->emit($event->type, array($event));

                // $this->phpariObject->channels()->channel_continue($event->channel->id, 'test-fuck', $event->channel->dialplan->exten, '1');
            });
        } catch (Exception $e) {
            echo $e->getMessage();
            exit(99);
        }
    }

    public function execute()
    {
        try {
            $this->stasisClient->open();
            $this->stasisLoop->run();
        } catch (Exception $e) {
            echo $e->getMessage();
            exit(99);
        }
    }
}
