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
class Drivers extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'drivers';
    }


    public function attributeLabels() {
    return [
    'id' => 'id',
    'fio' => 'Фамилия И О',
    'tabelnom' => 'Табельный номер',
    ];
    }
    
    public function rules()
    {
        return [
            ['fio', 'required'],
            ['tabelnom', 'string', 'max' => 10],
            ['status', 'default', 'value' => 0],
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
