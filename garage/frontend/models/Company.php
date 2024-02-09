<?php

namespace frontend\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "user_access".
 *
 * @property Access $access
 */
class Company extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company';
    }


    public function attributeLabels() {
    return [
    'name' => 'Наименование',
    ];
    }
    
    public function rules()
    {
        return [
            ['name', 'required'],
        ];
    }
    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
//    public static function getDb()
//    {
//        return Yii::$app->get('garazh');
//    }

    /**
     * {@inheritdoc}
     */
    /**
     * {@inheritdoc}
     */
    /**
     * Gets query for [[Access]].
     *
     * @return \yii\db\ActiveQuery
     */
//    public function getAccess()
//    {
//        return $this->hasOne(Access::className(), ['id' => 'access_id']);
//    }

    /**
     * @return \yii\db\ActiveQuery
     */

}
