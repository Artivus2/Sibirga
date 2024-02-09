<?php

namespace backend\controllers\queuemanagers;

use backend\models\QueueLog;
use yii\base\BaseObject;

/**
 * Class SensorJobController
 * Класс/Испольнитель очереди датчиков/сенсоров
 * @package backend\controllers\queuemanagers
 */
class SensorJobController extends BaseObject implements \yii\queue\JobInterface
{
	public $controller;                                                                                                 // название контроллера
	public $method;                                                                                                     // название метода
	public $data;                                                                                                       // входые параметры
	public $subscribe;                                                                                                  // на какой канал оповещать

	/**
	 * Название метода: execute()
	 * Назначение метода: Метод интерфейса JobInterface, который выполняет задачу очереди.
	 * Метод проверяет, есть ли указанный контроллер или метод, если нет, то записывает лог.
	 * Вызывает конкретный метод. Записывает длительность выполнения методов.
	 * Логи записываются в таблицу queue_log и в БД `amicum2_log`
	 * @author Озармехр Одилов <ooy@pfsz.ru>
	 * Created date: on 21.05.2019 11:22
	 */
	public function execute($queue)
	{
		$errors = array();
		$duration = 0;
		$result = "";
		if($this->controller != "" && $this->method != "")																// проверяем входные параметры
		{
			if(method_exists($this->controller, $this->method))															// Проверяем, существует ли метод
			{
				try
				{
					$microtime_start = microtime(true);														// фиксируем начало выполнения метода
					call_user_func_array($this->controller."::". $this->method, $this->data);					// вызываем метод
					$duration =  round(microtime(true) - $microtime_start,6);					// получаем длительность выполнения метода
				}
				catch (\Exception $e)																					// записываем логи
				{
					$errors[] = "Ошибка при вызове метода";
					$errors['error-message'] = $e->getMessage();														// сообщение ошибки
					$errors['error-code'] = $e->getCode();																// код ошибки
					$errors['error-line'] = $e->getLine();																// строка ошибки
				}
			}
			else																										// если метода не существует или контролера, записываем ошибку
			{
				$errors[] = "Метод или контроллер не существует";														// записыаем ошибку
			}
		}
		else
		{
			$errors[] = "Название контроллера или метода не передан";
		}
		QueueLog::AddLog($this->controller, $this->method, $this->data, $duration, $result, $errors, 'sensor');					// записываем лог в БД
	}
}