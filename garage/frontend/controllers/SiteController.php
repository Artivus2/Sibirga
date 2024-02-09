<?php

namespace frontend\controllers;

//use frontend\models\ResendVerificationEmailForm;
//use frontend\models\VerifyEmailForm;
use Yii;
//use yii\base\InvalidArgumentException;
//use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use frontend\models\LoginForm;
//use frontend\models\PasswordResetRequestForm;
use frontend\models\SignupForm;
use frontend\models\UserAccess;
use frontend\models\User;
use frontend\models\Garage;



/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public static function allowedDomains() {
    return [
        'http://10.8.12.118',                        // star allows all domains
// 'http://test1.example.com',
// 'http://test2.example.com',
    ];
}
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
//                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['login', 'error', 'garage', 'signup'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
            'corsFilter'  => [
            'class' => \yii\filters\Cors::className(),
            'cors'  => [
                // restrict access to domains:
                'Origin'                           => static::allowedDomains(),
                'Access-Control-Request-Method'    => ['POST' , 'PUT'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Request-Headers' => ['X-Wsse'],
                'Access-Control-Max-Age'           => 3600,                 // Cache (seconds)
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
                'class' => \yii\web\ErrorAction::class,
            ],
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
    $user = Yii::$app->user->identity->id;
    $model = UserAccess::find()->where(['user_id' => $user])->all();
    $garage = Garage::find()->all();
    return $this->render('index', ['model' => $model, 'garage' => $garage, 'user' => $user]);
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
//    public function init()
//    {
//    if (!Yii::$app->user->isGuest) {
//    
//	$this->layout = 'main';
//	} else {
//	$this->layout = 'login';
//	}
//    }
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
	$this->layout='login';
        return $this->render('login', [
            'model' => $model,
        ]);

    }

    public function actionRegister()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
	$this->layout='login';
        return $this->render('register', [
            'model' => $model,
        ]);

    }

    public function actionCheckSession()
    {
        $result = "";
        $session = Yii::$app->session;
        if (isset($session['sessionLogin'])) {                                                                          // Если в сессии задан логин
            if (isset($session['sessionPassword'])) {                                                                   // Если в сессии задан пароль
                $result = "Сессия активна";                                                                                  // Вывод лога
            } else {                                                                                                    // Если пароль не задан
                $result = "Пароль сессии не задан";                                                                          // Вывод лога
            }
        } else {                                                                                                        // Если пароль не задан
            $result = "Логин сессии не задан";                                                                               // Вывод лога
        }
	
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }
    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     *
     * @return mixed
     */
    
    public function actionGarage ()
    {
	return $this->render('garage/index');
    }
     

    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post()) && $model->signup()) {
            Yii::$app->session->setFlash('success', 'Thank you for registration. Please check your inbox for verification email.');
            return $this->goHome();
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            }

            Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * Verify email address
     *
     * @param string $token
     * @throws BadRequestHttpException
     * @return yii\web\Response
     */
    public function actionVerifyEmail($token)
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if (($user = $model->verifyEmail()) && Yii::$app->user->login($user)) {
            Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
            return $this->goHome();
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     *
     * @return mixed
     */
    public function actionResendVerificationEmail()
    {
        $model = new ResendVerificationEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            }
            Yii::$app->session->setFlash('error', 'Sorry, we are unable to resend verification email for the provided email address.');
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model
        ]);
    }
}
