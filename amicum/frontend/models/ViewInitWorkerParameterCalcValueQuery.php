<?php

namespace frontend\models;

/**
 * This is the ActiveQuery class for [[ViewInitWorkerParameterCalcValue]].
 *
 * @see ViewInitWorkerParameterCalcValue
 */
class ViewInitWorkerParameterCalcValueQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return ViewInitWorkerParameterCalcValue[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return ViewInitWorkerParameterCalcValue|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
