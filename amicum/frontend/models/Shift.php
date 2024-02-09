<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "shift".
 *
 * @property int $id ключ справочника смен
 * @property string $title Название смены
 * @property string|null $short_title
 *
 * @property BrigadeParameterCalcValue[] $brigadeParameterCalcValues
 * @property GraficChaneTable[] $graficChaneTables
 * @property GraficTabelDateFact[] $graficTabelDateFacts
 * @property GraficTabelDatePlan[] $graficTabelDatePlans
 * @property GroupDepParameterCalcValue[] $groupDepParameterCalcValues
 * @property Order[] $orders
 * @property OrderPermit[] $orderPermits
 * @property OrderVtbAb[] $orderVtbAbs
 * @property PassportOperation[] $passportOperations
 * @property PassportOperation[] $passportOperations0
 * @property RestrictionOrder[] $restrictionOrders
 * @property Storage[] $storages
 * @property TemplateOrderVtbAb[] $templateOrderVtbAbs
 * @property TimetableInstructionPb[] $timetableInstructionPbs
 * @property TimetableTabel[] $timetableTabels
 */
class Shift extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shift';
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
            [['id', 'title'], 'required'],
            [['id'], 'integer'],
            [['title'], 'string', 'max' => 30],
            [['short_title'], 'string', 'max' => 1],
            [['id'], 'unique'],
            [['title'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ справочника смен',
            'title' => 'Название смены',
            'short_title' => 'Short Title',
        ];
    }

    /**
     * Gets query for [[BrigadeParameterCalcValues]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBrigadeParameterCalcValues()
    {
        return $this->hasMany(BrigadeParameterCalcValue::className(), ['shift_id' => 'id']);
    }

    /**
     * Gets query for [[GraficChaneTables]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGraficChaneTables()
    {
        return $this->hasMany(GraficChaneTable::className(), ['shift_id' => 'id']);
    }

    /**
     * Gets query for [[GraficTabelDateFacts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDateFacts()
    {
        return $this->hasMany(GraficTabelDateFact::className(), ['shift_id' => 'id']);
    }

    /**
     * Gets query for [[GraficTabelDatePlans]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDatePlans()
    {
        return $this->hasMany(GraficTabelDatePlan::className(), ['shift_id' => 'id']);
    }

    /**
     * Gets query for [[GroupDepParameterCalcValues]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGroupDepParameterCalcValues()
    {
        return $this->hasMany(GroupDepParameterCalcValue::className(), ['shift_id' => 'id']);
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['shift_id' => 'id']);
    }

    /**
     * Gets query for [[OrderPermits]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPermits()
    {
        return $this->hasMany(OrderPermit::className(), ['shift_id' => 'id']);
    }

    /**
     * Gets query for [[OrderVtbAbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderVtbAbs()
    {
        return $this->hasMany(OrderVtbAb::className(), ['shift_id' => 'id']);
    }

    /**
     * Gets query for [[PassportOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportOperations()
    {
        return $this->hasMany(PassportOperation::className(), ['shift_id' => 'id']);
    }

    /**
     * Gets query for [[PassportOperations0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportOperations0()
    {
        return $this->hasMany(PassportOperation::className(), ['shift_id' => 'id']);
    }

    /**
     * Gets query for [[RestrictionOrders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRestrictionOrders()
    {
        return $this->hasMany(RestrictionOrder::className(), ['shift_id' => 'id']);
    }

    /**
     * Gets query for [[Storages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStorages()
    {
        return $this->hasMany(Storage::className(), ['shift_id' => 'id']);
    }

    /**
     * Gets query for [[TemplateOrderVtbAbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTemplateOrderVtbAbs()
    {
        return $this->hasMany(TemplateOrderVtbAb::className(), ['shift_id' => 'id']);
    }

    /**
     * Gets query for [[TimetableInstructionPbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTimetableInstructionPbs()
    {
        return $this->hasMany(TimetableInstructionPb::className(), ['shift_id' => 'id']);
    }

    /**
     * Gets query for [[TimetableTabels]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTimetableTabels()
    {
        return $this->hasMany(TimetableTabel::className(), ['shift_id' => 'id']);
    }

    /**
     * Название метода: getShiftList()
     * Метод получения смен из справочника смен
     * @param $title
     * @return array
     *
     * Входные необязательные параметры
     *
     * @throws \yii\db\Exception
     * Документация на портале:
     * @see
     * @example
     *
     * @package app\models
     *
     * Входные обязательные параметры:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 23.04.2019 10:04
     * @since ver
     */
    public static function getShiftList()
    {
        $shifts_list = Yii::$app->db->createCommand("SELECT id,title FROM shift")
            ->queryAll();
        return $shifts_list;
    }

    /**
     * Название метода: insertToTable()
     * Метод добавления смены в справочник смен
     * @param $title
     * @return array
     *
     * Входные необязательные параметры
     *
     * @throws \yii\db\Exception
     * Документация на портале:
     * @see
     * @example
     *
     * @package app\models
     *
     * Входные обязательные параметры:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 23.04.2019 10:05
     * @since ver
     */
    public static function insertShift($title)
    {
        $command = Yii::$app->db->createCommand("INSERT INTO shift(title) VALUES(:title)")
            ->bindValue(":title", $title);
        if(!$command->execute())
            $errors[] = "Возникли ошибки при добавлении смены";
        return $errors;
    }

    /**
     * Название метода: insertToTable()
     * @param $id - идентификатор изменяемой смены
     * @param $title - наименование изменяемой смены
     * @return array
     *
     * Входные необязательные параметры
     *
     * @throws \yii\db\Exception
     * Документация на портале:
     * @see
     * @example
     *
     * @package app\models
     *
     * Входные обязательные параметры:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 23.04.2019 10:06
     * @since ver
     */
    public static function updateShift($id, $title)
    {
        /** @var $shift проверяем есть ли подобная смена в БД по идентификатору */
        $shift = Yii::$app->db->createCommand("SELECT id FROM shift WHERE id = :id")
            ->bindValue(":id", $id)
            ->queryOne();
        if(isset($shift)){
            $command = Yii::$app->db->createCommand("UPDATE shift SET title = :title WHERE id = :id")
                ->bindValue(":title", $title)
                ->bindValue(":id", $id);
            if(!$command->execute())
                $errors[] = "Возникли ошибки при изменении смены";
        }
        return $errors;
    }

    /**
     * Название метода: deleteShift()
     * Метод удаления смены из справочника смен
     *
     * @param $id - идентификатор удаляемой смены
     * @return array
     *
     * Входные необязательные параметры
     *
     * @throws \yii\db\Exception
     * Документация на портале:
     * @see
     * @example
     *
     * @package app\models
     *
     * Входные обязательные параметры:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 23.04.2019 10:08
     * @since ver
     */
    public static function deleteShift($id)
    {
        /** @var  $shift объект смены с переданнеым идентификатором */
        $shift = Yii::$app->db->createCommand("SELECT id FROM shift WHERE id = :id")
            ->bindValue(":id", $id)
            ->queryOne();
        if($shift) {
            $command = Yii::$app->db->createCommand("DELETE FROM shift WHERE id = :id")
                ->bindValue(":id", $id);
            if (!$command->execute())
                $errors[] = "Возникли ошибки при удалении смены";
        }
        return $errors;
    }

}
