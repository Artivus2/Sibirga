<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_departments".
 *
 * @property int $id
 * @property int $OBJID Идентификатор
 * @property string $STEXT Название
 */
class SapDepartments extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_departments';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['OBJID', 'STEXT'], 'required'],
            [['OBJID'], 'integer'],
            [['STEXT'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'OBJID' => 'Идентификатор',
            'STEXT' => 'Название',
        ];
    }
}
