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
class Auto extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auto';
    }


    public function attributeLabels() {
    return [
    'name' => 'Наименование',
    'gosnomer' => 'Госномер',
    ];
    }
    
    public function rules()
    {
        return [
            ['name', 'required'],
            ['gosnomer', 'string', 'max' => 10],
            ['type', 'required'],
            ['work_status', 'default', 'value' => 1],
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
