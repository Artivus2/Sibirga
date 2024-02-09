<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "mine_camera_rotation".
 *
 * @property int $id
 * @property int $mine_id
 * @property double $x
 * @property double $y
 * @property double $z
 *
 * @property Mine $mine
 */
class MineCameraRotation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mine_camera_rotation';
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
            [['mine_id', 'x', 'y', 'z'], 'required'],
            [['mine_id'], 'integer'],
            [['x', 'y', 'z'], 'number'],
            [['mine_id'], 'unique'],
            [['mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['mine_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mine_id' => 'Mine ID',
            'x' => 'X',
            'y' => 'Y',
            'z' => 'Z',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMine()
    {
        return $this->hasOne(Mine::className(), ['id' => 'mine_id']);
    }
}
