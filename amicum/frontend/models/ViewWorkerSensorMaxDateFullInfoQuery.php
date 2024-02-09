<?php

namespace frontend\models;

/**
 * This is the ActiveQuery class for [[ViewWorkerSensorMaxDateFullInfo]].
 *
 * @see ViewWorkerSensorMaxDateFullInfo
 */
class ViewWorkerSensorMaxDateFullInfoQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return ViewWorkerSensorMaxDateFullInfo[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return ViewWorkerSensorMaxDateFullInfo|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
