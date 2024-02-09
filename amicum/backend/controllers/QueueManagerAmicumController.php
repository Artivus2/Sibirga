<?php

namespace backend\controllers;

use Yii;


class QueueManagerAmicumController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

	/**
	 * Название метода: QueuePush()
	* @param $queue - название очереди объекта (edge, sensor, equipment, worker). Например: 'worker'
	 * @param $controller - название вызываемого контроллера. Необходимо передать с указанием пространства имен. Например: "frontend\\controllers\\SiteController"
	 * @param $method - название метода. Например: 'Go'
	 * @param $data	- входные параметры метода. Например: 'Hello'
	 *
	 * Входные необязательные параметры
	 * @param string $subscribe - название канала/подписки для оповещения
     *
     * @return array - результат выполнения метода
     *
     *
     * @package backend\controllers
     * Назначение метода: Метод добавления задачи в очередь. Задача добавляется в конкретный очередь.
     * Перед добавлением в очередь, проверяется, существует ли вообще контроллер м указанный метод, если нет, то возвращает
     * ошибку. Если контроллер и метод существует, то добавляет определяет тип менеджера. После в указанный очередь добавляет задачу.
     * Задача считается добавленной в очередь, если одно из следующих состояний вернет значение 1:
     * 1. isWaiting – задача находится в очереди и ожидает выполнения
     * 2. isReserved – исполнитель взял задачу и выполняет
     * 3. isDone – задача выполнена
     *
     * Входные обязательные параметры:
     * @example $queue_res = QueueManagerAmicumController::QueuePush('worker', "frontend\\controllers\\SiteController", "G", "You are Bitch!!!", "sdf");
     *
     * Документация на портале: http://192.168.1.3/products/files/doceditor.aspx?fileid=21908&action=view
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 22.05.2019 12:02
	 */
    public static function QueuePush($queue, $controller, $method, $data, $subscribe = "")
	{
		$errors = array();
		$add_task_queue_status = 0;
		$queue_id = -1;
		if(method_exists($controller,$method))																			// проверяем, существует ли контроллер и метод, если да, то определяем тип менеджера очереди
		{
			$method_params = [
				'controller' => $controller, 																			// передаем название конктроллера
				'method' => $method, 																					// передаем название метода
				'data' => $data,																						// передаем входные параметры
				'subscribe' => $subscribe																				// на какой канал оповещать, в виде обычного текста - это фактически ключ подписки у веб сокета
			];
			$queue_manager = "";
			$queue_namespace = "backend\\controllers\\queuemanagers\\";
			switch ($queue)																								// определяем тип менеджера очереди
			{
				case 'worker' : {
					$queue_manager = "queueWorker";																		// очередь работников
					$queue_object_manager = $queue_namespace.'WorkerJobController';
				} break;
				case 'edge' : {																							// очередь выработок
					$queue_manager = "queueEdge";
					$queue_object_manager = $queue_namespace.'EdgeJobController';
				} break;
				case 'sensor' : {
					$queue_manager = "queueSensor";																		// очередь сенсоров
					$queue_object_manager = $queue_namespace.'SensorJobController';
				} break;
				case 'equipment' : {
					$queue_manager = "queueEquipment";																	// очередь оборудований
					$queue_object_manager = $queue_namespace.'EquipmentJobController';
				} break;
				default : $queue_object_manager = ""; break;
			}
			if($queue_object_manager == "")																				// если тип менеджера очередей не нашелся, то выподим ошибку
			{
				$errors[] = "Неизвестный тип менеджера очереди";
				$warnings[] = "Неизвестный тип менеджера очереди";
			}
			else																										// если нашли менеджер очереди, то добавляем его в очередь
			{
				$warnings[] = "Выбран менеджер очереди $queue_manager";
				$queue_id = Yii::$app->$queue_manager->push(															// добавление задачи в очередь
					new $queue_object_manager(																			// создаем экземпляр конкретного класса очереди и добавляем в очередь
						$method_params
					));
				if(Yii::$app->$queue_manager->isWaiting($queue_id) !== false || 										// если заданиие все еще находится в очереди
					Yii::$app->$queue_manager->isReserved($queue_id) !== false ||										// или менеджер взял и выполняет метод
					Yii::$app->$queue_manager->isDone($queue_id) !== false)												// или менеджер выполнил метод, то задача считается добавленной в очередь
				{
					$warnings[] = "Задача успешно добалена в очередь";
					$add_task_queue_status = 1;
				}
				else																									// если задача НЕ добавлена в очередь, то передаем ошибку на фронт
				{
					$warnings[] = "Ошибка добавления задачи в очередь";
					$errors[] = "Ошибка добавления задачи в очередь";
				}
				$warnings['isWaiting'] = Yii::$app->$queue_manager->isWaiting($queue_id);								// заданиие все еще находится в очереди
				$warnings['isReserved'] = Yii::$app->$queue_manager->isReserved($queue_id);								// менеджер взял и выполняет метод
				$warnings['isDone'] = Yii::$app->$queue_manager->isDone($queue_id);										// менеджер выполнил метод, то задача считается добавленной в очередь
			}
		}
		else																											// если контроллера или метода не существует, то возвращаем ошибку
		{
			$errors = __METHOD__.": Контроллер или метод не существует";
			$warnings = __METHOD__.": Контроллер или метод не существует";
		}

		return array(																									// возвращаем рузультат
			'status' => $add_task_queue_status,																			// статус добавления задачи в очередь
			'errors' => $errors,																						// возникщие ошибки
			'warnings' => $warnings,																					// возникщие предупреждения
			'queue_id' => $queue_id																						// идентификатор созданной задачи в очередь
		);
	}
}
