<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "siz_kind1".
 *
 * @property int $id
 * @property string $title
 * @property string $siz_subgroup_id
 */
class SizKind1 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'siz_kind1';
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
            [['title', 'siz_subgroup_id'], 'string', 'max' => 45],
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
            'siz_subgroup_id' => 'Siz Subgroup ID',
        ];
    }
}
