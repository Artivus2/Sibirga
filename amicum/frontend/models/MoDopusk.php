<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "mo_dopusk".
 *
 * @property int $id
 * @property string|null $title наименование допуска (разрешен, откланен)
 *
 * @property PhysicalEsmo[] $physicalEsmos
 */
class MoDopusk extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mo_dopusk';
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
            [['title'], 'string', 'max' => 255],
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
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalEsmos()
    {
        return $this->hasMany(PhysicalEsmo::className(), ['mo_dopusk_id' => 'id']);
    }
}
