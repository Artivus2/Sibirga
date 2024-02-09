<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_company".
 *
 * @property int $id в Oracle = OBJID
 * @property string $title
 * @property string $upper_company_id
 */
class SapCompany extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_company';
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
            [['id'], 'required'],
            [['id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['upper_company_id'], 'string', 'max' => 45],
            [['id'], 'unique'],
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
            'upper_company_id' => 'Upper Company ID',
        ];
    }
}
