<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

use PAMI\Client\Impl\ClientImpl as PamiClient;
use PAMI\Message\Event\EventMessage;
use PAMI\Listener\IEventListener;
use PAMI\Message\Event\DialEvent;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        $options = array(
            'host' => '10.1.3.110',
            'scheme' => 'tcp://',
            'port' => 5038,
            'username' => 'test',
            'secret' => 'fuck',
            'connect_timeout' => 10,
            'read_timeout' => 10
        );
        $client = new PamiClient($options);
        $client->open();

        $client->registerEventListener(
            function (EventMessage $event) {
                print_r($event);
            }
        );
        while (true) {
            $client->process();
            usleep(1000);
        }
        $client->close();
        return $this->render('index');
    }
    /**
     * Displays homepage.
     *
     * @return string
     */
    // public function actionIndex()
    // {
    //     $ami = new \PHPAMI\Ami();
    //     if ($ami->connect('10.1.3.110:5038', 'test', 'fuck', 'off') === false) {
    //         throw new \RuntimeException('Could not connect to Asterisk Management Interface.');
    //     }

    //     // // if you have a looping of command function
    //     // // set allowTimeout flag to true
    //     // $ami->allowTimeout();

    //     // $result contains the output from the command
    //     $result = $ami->command('core show channels');
    //     var_dump($result);
    //     $ami->disconnect();
    //     return $this->render('index');
    // }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
