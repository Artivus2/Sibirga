<?php

namespace frontend\models;

/**
 * This is the ActiveQuery class for [[ViewEmployeeLastWorkerInfo]].
 *
 * @see ViewEmployeeLastWorkerInfo
 */
class ViewEmployeeLastWorkerInfoQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return ViewEmployeeLastWorkerInfo[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return ViewEmployeeLastWorkerInfo|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
