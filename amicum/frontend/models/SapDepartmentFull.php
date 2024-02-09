<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_department_full".
 *
 * @property int $id
 * @property int $OBJID
 * @property string $STEXT
 * @property int $status
 */
class SapDepartmentFull extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_department_full';
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
            [['OBJID', 'STEXT'], 'required'],
            [['OBJID', 'status'], 'integer'],
            [['STEXT'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'OBJID' => 'Objid',
            'STEXT' => 'Stext',
            'status' => 'Status',
        ];
    }
}
