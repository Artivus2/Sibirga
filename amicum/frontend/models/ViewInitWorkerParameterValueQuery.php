<?php

namespace frontend\models;

/**
 * This is the ActiveQuery class for [[ViewInitWorkerParameterValue]].
 *
 * @see ViewInitWorkerParameterValue
 */
class ViewInitWorkerParameterValueQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return ViewInitWorkerParameterValue[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return ViewInitWorkerParameterValue|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
