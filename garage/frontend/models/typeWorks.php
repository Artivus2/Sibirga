<?php

namespace frontend\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "user_access".
 *
 * @property int $id
 * @property int $user_id
 * @property int $garage_id
 * @property int|null $read
 * @property int|null $write
 *
 * @property Access $access
 */
class typeWorks extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'typeWorks';
    }


    public function attributeLabels() {
    return [
    'name' => 'Типы работ',
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