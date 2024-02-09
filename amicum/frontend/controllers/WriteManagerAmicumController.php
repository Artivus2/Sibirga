<?php

namespace frontend\controllers;
use backend\controllers\LogAmicum;
use backend\controllers\QueueManagerAmicumController;
use Yii;
use yii\web\Response;

require_once __DIR__ . '/../controllers/../../DebugCode.php';
class WriteManagerAmicumController extends \yii\web\Controller
{



	/**
	 * Название метода: actionIndex()
	 * Назначение метода: Менеджер добавления задач в очередь
	 * Описание метода: Клиент отрпавляет запрос на изменение в менеджер WriteManagerAmicum. Данный менеджер проверяет,
	 * существует ли указанный контроллер и метод, если метод и контроллер существует, то добавляет задачу в конкретный менеджер очереди
	 * передавая назание контроллера, метод и входные параметры метода.
	 * Логирует свои действия в таблицу user_action_log , в том числе и возникающие ошибки.
	 * @return array|false|string|\yii\helpers\Json возвращает результат выполнения самого метода (а не результат задачи которую нужно добавить в очередь)
	 * @package frontend\controllers
	 *
	 * Входные обязательные параметры:
	 * $post['controller'] - название контроллера
	 * $post['method'] - название метода
	 * $post['data'] - входные параметры метода
	 * $post['queue'] - какой мененджер очереди использовать. Например, если очередь для worker, sensor, edge.
	 * $post['namespace'] - название пространства имен. Это параметр обязательно передать, но может быть пустый.
	 * Каждый класс объекта очередь заканчивается суффиксом JobController. Например WorkerJobController
	 *
	 * @example http://amicum.advanced/write-manager-amicum?controller=Site&method=Go&subscribe=handbook-unit&data=12121&queue=edge&namespace
	 * @example http://192.168.2.4/write-manager-amicum?controller=Site&method=Go&subscribe=handbook-unit&data=12121&queue=edge&namespace
	 * @example http://amicum.advanced/write-manager-amicum?controller=Test&method=SetCache&subscribe=handbook-unit&data={"mines":{"value":"Menennenene"},"ll":{"value":2254545}}&queue=worker&namespace=backend\controllers
	 *
	 * Документация на портале:
	 * @author Озармехр Одилов <ooy@pfsz.ru>
	 * Created date: on 20.05.2019 9:30
	 */
	public function actionIndex()
	{
		$add_task_queue_status = 0;
		$post = $this->GetServerMethod();																				// получение данных из POST/GET
		$microtime_start = microtime(true);                                                                  // задаем начало времени выполнения запроса
		$status=1;                                                                                                      // статус выполнения метода приравниваем к 1, по мере выполнения может обнулятся, в случае если подметоды возвращают 0, притом сам метод может выполняться полностью, но с логическими ошибками
		$warnings=array();                                                                                              // массив предупреждений
		$errors=array();                                                                                                // массив ошибок
		$result=array();                                                                                                // промежуточный результирующий массив
		$session = Yii::$app->session;                                                                                  // проверяем достаточно ли прав пользователю для выполнения метода, на основе данных сессиии по табельном номеру
		if(!isset($session['userStaffNumber']) ||
			is_null($session['userStaffNumber']) ||
			$session['userStaffNumber']==""
		)
		{
			if(isset($post['controller'])) {                                             //проверка на первичную авторизацию, в том случае если это первая авторизация, то метод должен запустить на авторизацию - нужно мобильным устройствам
				$result = array('errors' => array('503' => "Недостаточно прав для выполнения запроса"));

				LogAmicum::LogAccessAmicum(                                                                                 // записываем в журнал сведения о нарушении доступа
					date("y.m.d H:i:s"), $session);
				Yii::$app->response->format = Response::FORMAT_JSON;
				Yii::$app->response->data = $result;
				return $result;
			}
		}


		$namespace = 'frontend\controllers\\';																			// просторанство имен Yii2

		/** @var \yii\helpers\Json $result - результат возвращаемых данных фронтенду*/
		$result = '';

		/** @var string $duration_method -  длительность выполнения метода. Используется для логирования и отладки методов Yii2 фронтендов*/
		$duration_method = '';

		$errors = array();																								// массив ошибок
		if(																												// проверка входных параметров
			isset
			(
				$post['controller'], 																					// название вызываемого контроллера со стороны фронтенда(ОБЯЗАТЕЛЬНО без суффикса Controller, например НЕ SensorInfoController, а SensorInfo)
				$post['method'],																						// название вызываемого метода контроллера со стороны фронтенда
				$post['data'],																							// входные параметеры метода
				$post['queue'],																							// какой мененджер очереди использовать. Например, если очередь для worker, sensor, edge. Каждый класс объекта очередь заканчивается суффиксом JobController. Например WorkerJobController
				$post['subscribe'],																						// на какой канал оповещать
				$post['namespace']																						// пространства имен
			) &&
			$post['controller'] != '' &&
			$post['method'] != ''
		)
		{
			$controller = $post['controller'];																			// название вызываемого контроллера со стороны фронтенда
			$method = $post['method'];																					// название вызываемого метода контроллера со стороны фронтенда
			$data =  $post['data'];																	                    // входные парамтеры метода
			$subscribe = $post['subscribe'];																			// на какой канал оповещать
			$queue = $post['queue'];																					// какой именно очередь вызвать
			(isset($post['namespace']) && $post['namespace'] != "") ? $namespace = $post['namespace']."\\" : true;									// получаем пространства имен, если передан
			try
			{

				$controller .= 'Controller';
				$controller = $namespace.$controller;
				$queue_push_result = QueueManagerAmicumController::QueuePush($queue, $controller, $method, array($data), $subscribe);	// добавляем задачу в очередь
				$errors = array_merge($errors, (array)$queue_push_result['errors']);									// получаем ошибку
				$warnings = array_merge($warnings, (array)$queue_push_result['warnings']);								// получаем предупреждения
				$status = $queue_push_result['status'];																	// получаем статус добавления в очередь
				$add_task_queue_status = $queue_push_result['status'];													// получаем статус добавления в очередь
			}
			catch (\Exception $e)
			{
				$status = 0;
				$errors[] = $e->getMessage();
			}
		}
		else
		{
			$status=0;
			$errors[] = "WriteManagerAmicum. Входные параметры не переданы";
		}

		/***** Логирование в БД *****/
        $tabel_number=$session['userStaffNumber'];
		$post = json_encode($post);
        $errors_insert=json_encode($errors);
		$duration_method = round(microtime(true) - $microtime_start,6);                                              //расчет времени выполнения метода
		LogAmicum::LogEventAmicum(                                                                                      //записываем в журнал сведения о выполнении метода
			'WebsocketServerController/index',
			date("y.m.d H:i:s"), $duration_method, $post, $result, $errors_insert, $tabel_number);

		$result_main = array('Items'=>$result,'status'=>$status,'errors' => $errors, 'warnings'=>$warnings, 'queue_status' => $add_task_queue_status);
		Yii::$app->response->format = Response::FORMAT_JSON;
		Yii::$app->response->data = $result_main;
	}

	public function GetServerMethod()
	{
		if($_SERVER['REQUEST_METHOD'] == 'POST') return  Yii::$app->request->post();
		else if($_SERVER['REQUEST_METHOD'] == 'GET') return  Yii::$app->request->get();
	}
}
