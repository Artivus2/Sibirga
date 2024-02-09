<?php

namespace frontend\models;

/**
 * This is the ActiveQuery class for [[ViewInitBrigadeParameterHandbookValue]].
 *
 * @see ViewInitBrigadeParameterHandbookValue
 */
class ViewInitBrigadeParameterHandbookValueQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return ViewInitBrigadeParameterHandbookValue[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return ViewInitBrigadeParameterHandbookValue|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
