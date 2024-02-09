<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "norm_siz".
 *
 * @property int $id ключ нормы
 * @property string $norm_hand_id_link_1c ключ нормы выдачи СИЗ из внешней системы 1С
 * @property string $siz_hand_norm_id_link_1c Название нормы выдачи СИЗ
 *
 * @property NormHand $normHandIdLink1c
 * @property SizHandNorm $sizHandNormIdLink1c
 */
class NormSiz extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'norm_siz';
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
            [['norm_hand_id_link_1c', 'siz_hand_norm_id_link_1c'], 'required'],
            [['norm_hand_id_link_1c', 'siz_hand_norm_id_link_1c'], 'string', 'max' => 100],
            [['norm_hand_id_link_1c', 'siz_hand_norm_id_link_1c'], 'unique', 'targetAttribute' => ['norm_hand_id_link_1c', 'siz_hand_norm_id_link_1c']],
            [['norm_hand_id_link_1c'], 'exist', 'skipOnError' => true, 'targetClass' => NormHand::className(), 'targetAttribute' => ['norm_hand_id_link_1c' => 'link_1c']],
            [['siz_hand_norm_id_link_1c'], 'exist', 'skipOnError' => true, 'targetClass' => SizHandNorm::className(), 'targetAttribute' => ['siz_hand_norm_id_link_1c' => 'link_1c']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'norm_hand_id_link_1c' => 'Norm Hand Id Link 1c',
            'siz_hand_norm_id_link_1c' => 'Siz Hand Norm Id Link 1c',
        ];
    }

    /**
     * Gets query for [[NormHandIdLink1c]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNormHandIdLink1c()
    {
        return $this->hasOne(NormHand::className(), ['link_1c' => 'norm_hand_id_link_1c']);
    }

    /**
     * Gets query for [[SizHandNormIdLink1c]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSizHandNormIdLink1c()
    {
        return $this->hasOne(SizHandNorm::className(), ['link_1c' => 'siz_hand_norm_id_link_1c']);
    }
}
