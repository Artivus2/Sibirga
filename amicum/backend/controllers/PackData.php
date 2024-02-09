<?php
namespace backend\controllers;

trait PackData
{
	/**
     * Название метода: UnityPackUp()
     * Назначение метода: метод возврата данных в указанном формате для Unity
     *
     * Входные обязательные параметры
     * @param $operation_type - тип операции
     * @param $transaction - номер транзакции
     * @param $action_url - url
     * @param $items - массив данных
     *
     * @return array данный в нужном формате
     *
     * @package app\controllers
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
	 * Created date: on 23.04.2019 15:55
	 */
	public static function UnityPackUp($operation_type, $transaction, $action_url, $items)
	{
        return array(
        	'json' => array(
				'service_info' => array(
					'operation_type' =>$operation_type,
					'trunsuction' => $transaction,
					'action_url' => $action_url
				),
				'data' =>  json_encode($items)
        	),

		);
	}

	/**
	 * Название метода: SocketServicesData()
	 * Назначение метода: Метод возврата данных в нужный формат для вебсокет сервера
	 * @param $active_page - активная страница
	 * @param $action_type - тип операции
	 * @param $role_id - идентификатор роли
	 * @param $mine_id - идентификатор шахты
	 * @param $tabel_number - табельный номер
	 * @param $transaction - номер транзакция
	 * @param $action_url - запрашиваемый url
	 * @param $filter - входные параметры (данные)
	 * @param $entity - входные параметры (данные)
	 *
	 * @return array - массив данных в нужном формате для вебсокет сервера
	 * @package app\controllers
	 * @author Озармехр Одилов <ooy@pfsz.ru>
	 * Created date: on 24.04.2019 14:19
	 */
	public function SocketServicesData($active_page, $action_type, $role_id, $mine_id, $tabel_number, $transaction, $action_url, $filter, $entity)
	{
		return array(
			'service_info' => array(
				'active_page' => $active_page,
				'action_type' => $action_type,
				'role_id' => $role_id,
				'mine_id' => $mine_id,
				'tabel_number' => $tabel_number,
				'transaction' => $transaction,
				'action_url' => $action_url,

			),
			'data' => array(
				'filter' => $filter,
				'entity' => $entity
			)
		);
	}
}