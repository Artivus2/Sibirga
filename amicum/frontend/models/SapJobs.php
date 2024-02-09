<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_jobs".
 *
 * @property int $id
 * @property int $STELL Идентификатор
 * @property string $STEXT Название
 */
class SapJobs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_jobs';
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
            [['STELL', 'STEXT'], 'required'],
            [['STELL'], 'integer'],
            [['STEXT'], 'string', 'max' => 150],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'STELL' => 'Идентификатор',
            'STEXT' => 'Название',
        ];
    }
}
