<?php


namespace backend\controllers\cachemanagers;

/**
 * Интерфейс для классов по работе с кэшами объектов
 * Interface ObjectCacheInterface
 * @package backend\controllers\cachemanagers
 */
interface ObjectCacheInterface
{
	public function runInit($mine_id);
	public function multiGetParameterValue($object_id = '*', $parameter_id='*', $parameter_type_id = '*');
	public function multiGetParameterValueByParameters($object_id = '*', $parameters = '*:*');
	public function getParameterValue($object_id, $parameter_id, $parameter_type_id);
	public function setParameterValue($object_id, $value);
	public function buildParameterKey($object_id, $parameter_id, $parameter_type_id);
	public function delParameterValue($object_id, $parameter_id, $parameter_type_id);
	public function removeAll();
}