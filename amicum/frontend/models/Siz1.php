<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "siz1".
 *
 * @property int $id
 * @property string $title
 * @property int $unit_id
 * @property int $wear_period
 * @property int $season_id
 * @property string $comment
 * @property int $siz_kind_id
 * @property int $document_id
 */
class Siz1 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'siz1';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_amicum2');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['unit_id', 'wear_period', 'season_id', 'siz_kind_id', 'document_id'], 'integer'],
            [['title'], 'string', 'max' => 45],
            [['comment'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'unit_id' => 'Unit ID',
            'wear_period' => 'Wear Period',
            'season_id' => 'Season ID',
            'comment' => 'Comment',
            'siz_kind_id' => 'Siz Kind ID',
            'document_id' => 'Document ID',
        ];
    }
}
