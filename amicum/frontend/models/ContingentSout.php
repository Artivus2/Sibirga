<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "contingent_sout".
 *
 * @property int $id
 * @property int $contingent_from_sout_id Внешний идентификатор контингента СОУТа
 * @property int $sout_id Внешний идентификатор СОУТа
 *
 * @property ContingentFromSout $contingentFromSout
 * @property Sout $sout
 */
class ContingentSout extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contingent_sout';
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
            [['id', 'contingent_from_sout_id', 'sout_id'], 'required'],
            [['id', 'contingent_from_sout_id', 'sout_id'], 'integer'],
            [['id'], 'unique'],
            [['contingent_from_sout_id'], 'exist', 'skipOnError' => true, 'targetClass' => ContingentFromSout::className(), 'targetAttribute' => ['contingent_from_sout_id' => 'id']],
            [['sout_id'], 'exist', 'skipOnError' => true, 'targetClass' => Sout::className(), 'targetAttribute' => ['sout_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'contingent_from_sout_id' => 'Внешний идентификатор контингента СОУТа',
            'sout_id' => 'Внешний идентификатор СОУТа',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContingentFromSout()
    {
        return $this->hasOne(ContingentFromSout::className(), ['id' => 'contingent_from_sout_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSout()
    {
        return $this->hasOne(Sout::className(), ['id' => 'sout_id']);
    }
}
