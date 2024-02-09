<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "modul_amicum".
 *
 * @property int $id ключ модуля АМИКУМ
 * @property string $title Имя модуля АМИКУМ
 *
 * @property WorkstationPage[] $workstationPages
 */
class ModulAmicum extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'modul_amicum';
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
            [['title'], 'required'],
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
     * Gets query for [[WorkstationPages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkstationPages()
    {
        return $this->hasMany(WorkstationPage::className(), ['modul_amicum_id' => 'id']);
    }
}
