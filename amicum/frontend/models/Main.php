<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "main".
 *
 * @property int $id
 * @property string $table_address
 * @property string $db_address
 *
 * @property EnergyMine[] $energyMines
 * @property EnergyMine[] $energyMines0
 * @property EventFact[] $eventFacts
 * @property MineSituationFact[] $mineSituationFacts
 * @property ObjectPlace[] $objectPlaces
 * @property OrderRelation[] $orderRelations
 * @property PpsMine[] $ppsMines
 * @property PpsMine[] $ppsMines0
 * @property SituationFact[] $situationFacts
 */
class Main extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'main';
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
            [['table_address', 'db_address'], 'required'],
            [['table_address', 'db_address'], 'string', 'max' => 120],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'table_address' => 'Table Address',
            'db_address' => 'Db Address',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMines()
    {
        return $this->hasMany(EnergyMine::className(), ['main_from_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMines0()
    {
        return $this->hasMany(EnergyMine::className(), ['main_to_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventFacts()
    {
        return $this->hasMany(EventFact::className(), ['main_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituationFacts()
    {
        return $this->hasMany(MineSituationFact::className(), ['main_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObjectPlaces()
    {
        return $this->hasMany(ObjectPlace::className(), ['main_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderRelations()
    {
        return $this->hasMany(OrderRelation::className(), ['main_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPpsMines()
    {
        return $this->hasMany(PpsMine::className(), ['main_from_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPpsMines0()
    {
        return $this->hasMany(PpsMine::className(), ['main_to_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationFacts()
    {
        return $this->hasMany(SituationFact::className(), ['main_id' => 'id']);
    }
}
