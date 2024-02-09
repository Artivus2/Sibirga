<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "passport_parameter".
 *
 * @property int $id ключ конкретного параметра паспорта
 * @property int $passport_id ключ паспорта
 * @property int $parameter_id ключ параметра
 * @property string $value значение параметра
 *
 * @property Parameter $parameter
 * @property Passport $passport
 */
class PassportParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'passport_parameter';
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
            [['passport_id', 'parameter_id', 'value'], 'required'],
            [['passport_id', 'parameter_id'], 'integer'],
            [['value'], 'string', 'max' => 255],
            [['passport_id', 'parameter_id'], 'unique', 'targetAttribute' => ['passport_id', 'parameter_id']],
            [['parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parameter::className(), 'targetAttribute' => ['parameter_id' => 'id']],
            [['passport_id'], 'exist', 'skipOnError' => true, 'targetClass' => Passport::className(), 'targetAttribute' => ['passport_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'passport_id' => 'Passport ID',
            'parameter_id' => 'Parameter ID',
            'value' => 'Value',
        ];
    }

    /**
     * Gets query for [[Parameter]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParameter()
    {
        return $this->hasOne(Parameter::className(), ['id' => 'parameter_id']);
    }

    /**
     * Gets query for [[Passport]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassport()
    {
        return $this->hasOne(Passport::className(), ['id' => 'passport_id']);
    }
}
