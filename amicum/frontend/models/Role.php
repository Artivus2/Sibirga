<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "role".
 *
 * @property int $id Ключ справоника ролей
 * @property string $title Название роли (сокращенное). Например, МГВМ, ГРОЗ и т.д.
 * @property int|null $weight Уровень роли в иерархии. Нужен в ряде случаев для сортировки сотрудников в порядке убывания уровня ролей
 * @property int|null $type Тип роли, используется для разделения справочника на несколько подсправочников. Шахтный (type=1), участковый (ИТР) (type=2), участковый (рабочие) (type=3), прочие (type=4).
 * @property int|null $surface_underground подземный поверхностный
 *
 * @property Contingent[] $contingents
 * @property ContractingOrganization[] $contractingOrganizations
 * @property EventPbWorker[] $eventPbWorkers
 * @property GraficTabelDateFact[] $graficTabelDateFacts
 * @property GraficTabelDatePlan[] $graficTabelDatePlans
 * @property OperationWorker[] $operationWorkers
 * @property RepairMapSpecificRole[] $repairMapSpecificRoles
 * @property RepairMapTypicalRole[] $repairMapTypicalRoles
 * @property Sout[] $souts
 * @property WorkerObject[] $workerObjects
 * @property WorkerObjectRole[] $workerObjectRoles
 * @property WorkerObject[] $workerObjects0
 * @property WorkingPlace[] $workingPlaces
 */
class Role extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'role';
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
            [['title'], 'required'],
            [['weight', 'type', 'surface_underground'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
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
            'weight' => 'Weight',
            'type' => 'Type',
            'surface_underground' => 'Surface Underground',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContingents()
    {
        return $this->hasMany(Contingent::className(), ['role_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContractingOrganizations()
    {
        return $this->hasMany(ContractingOrganization::className(), ['role_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventPbWorkers()
    {
        return $this->hasMany(EventPbWorker::className(), ['role_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDateFacts()
    {
        return $this->hasMany(GraficTabelDateFact::className(), ['role_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDatePlans()
    {
        return $this->hasMany(GraficTabelDatePlan::className(), ['role_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationWorkers()
    {
        return $this->hasMany(OperationWorker::className(), ['role_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificRoles()
    {
        return $this->hasMany(RepairMapSpecificRole::className(), ['role_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypicalRoles()
    {
        return $this->hasMany(RepairMapTypicalRole::className(), ['role_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSouts()
    {
        return $this->hasMany(Sout::className(), ['role_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerObjects()
    {
        return $this->hasMany(WorkerObject::className(), ['role_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerObjectRoles()
    {
        return $this->hasMany(WorkerObjectRole::className(), ['role_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerObjects0()
    {
        return $this->hasMany(WorkerObject::className(), ['id' => 'worker_object_id'])->viaTable('worker_object_role', ['role_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkingPlaces()
    {
        return $this->hasMany(WorkingPlace::className(), ['role_id' => 'id']);
    }
}
