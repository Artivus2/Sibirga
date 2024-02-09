<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_company_full".
 *
 * @property int $id
 * @property int $id_comp
 * @property string $title
 * @property int $upper_company_id
 */
class SapCompanyFull extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_company_full';
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
            [['id_comp', 'upper_company_id'], 'integer'],
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
            'id_comp' => 'Id Comp',
            'title' => 'Title',
            'upper_company_id' => 'Upper Company ID',
        ];
    }
}
