<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "siz_hand_norm".
 *
 * @property int $id ключ сиз
 * @property string|null $title Название СИЗ
 * @property string|null $link_1c ключ СИЗ из внешней системы 1С
 *
 * @property NormSiz[] $normSizs
 * @property NormHand[] $normHandIdLink1cs
 */
class SizHandNorm extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'siz_hand_norm';
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
            [['title'], 'string', 'max' => 255],
            [['link_1c'], 'string', 'max' => 100],
            [['link_1c'], 'unique'],
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
            'link_1c' => 'Link 1c',
        ];
    }

    /**
     * Gets query for [[NormSizs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNormSizs()
    {
        return $this->hasMany(NormSiz::className(), ['siz_hand_norm_id_link_1c' => 'link_1c']);
    }

    /**
     * Gets query for [[NormHandIdLink1cs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNormHandIdLink1cs()
    {
        return $this->hasMany(NormHand::className(), ['link_1c' => 'norm_hand_id_link_1c'])->viaTable('norm_siz', ['siz_hand_norm_id_link_1c' => 'link_1c']);
    }
}
