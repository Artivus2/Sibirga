<?php

namespace frontend\models;

/**
 * This is the ActiveQuery class for [[ViewEdgeParameterHandbookValueMaxDateForMerge]].
 *
 * @see ViewEdgeParameterHandbookValueMaxDateForMerge
 */
class ViewEdgeParameterHandbookValueMaxDateForMergeQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return ViewEdgeParameterHandbookValueMaxDateForMerge[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return ViewEdgeParameterHandbookValueMaxDateForMerge|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
