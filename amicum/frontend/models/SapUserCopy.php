<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_user_copy".
 *
 * @property int $id
 * @property string $PERNR Табельный номер
 * @property string $EMAIL_ADDRESS Адрес электронной почты
 * @property string $USERID Логин пользователя AD
 * @property string $USERPRINCIPALNAME  Реквизит AD UPN
 * @property string $DATE_MODIFIED Дата последнего изменения записи
 */
class SapUserCopy extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_user_copy';
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
            [['PERNR', 'USERID', 'DATE_MODIFIED'], 'required'],
            [['DATE_MODIFIED'], 'safe'],
            [['PERNR', 'EMAIL_ADDRESS', 'USERID', 'USERPRINCIPALNAME'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'PERNR' => 'Табельный номер',
            'EMAIL_ADDRESS' => 'Адрес электронной почты',
            'USERID' => 'Логин пользователя AD',
            'USERPRINCIPALNAME' => ' Реквизит AD UPN',
            'DATE_MODIFIED' => 'Дата последнего изменения записи',
        ];
    }
}
