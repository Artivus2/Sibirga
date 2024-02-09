<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers;

use Yii;
use yii\web\Controller;

class HandbookCachedController extends Controller
{
    // GetDepartmentList
    // GetOperationsList
    // GetDepartmentListWithWorkers
    // GetWorkersForHandbook
    // GetUndergroundPlaceList
    // GetPlacesList
    // GetListConveyorEquipments

    // clearOperationCache          - Очистка кеша операций
    // clearDepartmentCache         - Очистка кеша подразделений
    // clearPlaceCache              - Очистка кеша мест
    // clearWorkerCache             - Очистка кеша работников
    // clearConveyorEquipmentsCache - Очистка кеша Конвейеров

    /**
     * Очистка кеша операций
     */
    public static function clearOperationCache()
    {
        $cache = Yii::$app->cache;
        $cache->delete("GetOperationsList");
        $cache->delete("GetOperationsListHash");
    }

    /**
     * Очистка кеша подразделений
     */
    public static function clearDepartmentCache()
    {
        $cache = Yii::$app->cache;
        $cache->delete("GetDepartmentListWithWorkers");
        $cache->delete("GetDepartmentList");
        $cache->delete("GetDepartmentListWithWorkersHash");
        $cache->delete("GetDepartmentListHash");
    }

    /**
     * Очистка кеша мест
     */
    public static function clearPlaceCache()
    {
        $cache = Yii::$app->cache;
        $cache->delete("GetUndergroundPlaceList");
        $cache->delete("GetPlacesList");
        $cache->delete("GetUndergroundPlaceListHash");
        $cache->delete("GetPlacesListHash");
    }

    /**
     * Очистка кеша работников
     */
    public static function clearWorkerCache()
    {
        $cache = Yii::$app->cache;
        $cache->delete("GetWorkersForHandbook");
        $cache->delete("GetWorkersForHandbookHash");
    }

    /**
     * Очистка кеша Конвейеров
     */
    public static function clearConveyorEquipmentsCache()
    {
        $cache = Yii::$app->cache;
        $cache->delete("GetListConveyorEquipments");
        $cache->delete("GetListConveyorEquipmentsHash");
    }

}