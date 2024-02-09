<?php

namespace frontend\models;

/**
 * This is the ActiveQuery class for [[ViewEdgeParameterValueMaxDateForMerge]].
 *
 * @see ViewEdgeParameterValueMaxDateForMerge
 */
class ViewEdgeParameterValueMaxDateForMergeQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return ViewEdgeParameterValueMaxDateForMerge[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return ViewEdgeParameterValueMaxDateForMerge|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
