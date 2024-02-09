<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[WebSocketSync]].
 *
 * @see WebSocketSync
 */
class WebSocketSyncQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return WebSocketSync[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return WebSocketSync|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
