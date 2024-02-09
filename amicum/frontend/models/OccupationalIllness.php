<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "occupational_illness".
 *
 * @property int $id
 * @property int $worker_id Внешний идентификатор работника у которого было установлено проф заболевание
 * @property int $position_id Внешний идентификатор професси работника на которой он работал когда получил проф заболевание
 * @property int $reason_occupational_illness_id
 * @property string $installed Кто установил
 * @property string $diagnosis Диагноз
 * @property string $date_act Дата составления акта
 * @property string $state_on_date Состояние на дату
 * @property string $state_on_act Состояние на момент выявления проф заболевания
 * @property string $state_now Текущее состояние работника
 * @property int $age Возраст работника
 * @property string $birthdate Дата рождения работника
 * @property string $gender Пол работника
 * @property int $experience Стаж работника
 * @property int $company_department_id Внешний ключ участка
 * @property int $worker_status Флаг работает человек или нет
 * @property int $position_experience Стаж по профессии
 *
 * @property CompanyDepartment $companyDepartment
 * @property Position $position
 * @property ReasonOccupationalIllness $reasonOccupationalIllness
 * @property Worker $worker
 * @property OccupationalIllnessAttachment[] $occupationalIllnessAttachments
 */
class OccupationalIllness extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'occupational_illness';
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
            [['worker_id', 'position_id', 'reason_occupational_illness_id', 'diagnosis', 'date_act', 'age', 'birthdate', 'gender', 'experience', 'company_department_id'], 'required'],
            [['worker_id', 'position_id', 'reason_occupational_illness_id', 'age', 'experience', 'company_department_id', 'worker_status', 'position_experience'], 'integer'],
            [['date_act', 'state_on_date', 'birthdate'], 'safe'],
            [['installed', 'diagnosis', 'state_on_act', 'state_now'], 'string', 'max' => 255],
            [['gender'], 'string', 'max' => 1],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['position_id'], 'exist', 'skipOnError' => true, 'targetClass' => Position::className(), 'targetAttribute' => ['position_id' => 'id']],
            [['reason_occupational_illness_id'], 'exist', 'skipOnError' => true, 'targetClass' => ReasonOccupationalIllness::className(), 'targetAttribute' => ['reason_occupational_illness_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'worker_id' => 'Внешний идентификатор работника у которого было установлено проф заболевание',
            'position_id' => 'Внешний идентификатор професси работника на которой он работал когда получил проф заболевание',
            'reason_occupational_illness_id' => 'Reason Occupational Illness ID',
            'installed' => 'Кто установил',
            'diagnosis' => 'Диагноз',
            'date_act' => 'Дата составления акта',
            'state_on_date' => 'Состояние на дату',
            'state_on_act' => 'Состояние на момент выявления проф заболевания',
            'state_now' => 'Текущее состояние работника',
            'age' => 'Возраст работника',
            'birthdate' => 'Дата рождения работника',
            'gender' => 'Пол работника',
            'experience' => 'Стаж работника',
            'company_department_id' => 'Внешний ключ участка',
            'worker_status' => 'Флаг работает человек или нет',
            'position_experience' => 'Стаж по профессии',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPosition()
    {
        return $this->hasOne(Position::className(), ['id' => 'position_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReasonOccupationalIllness()
    {
        return $this->hasOne(ReasonOccupationalIllness::className(), ['id' => 'reason_occupational_illness_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOccupationalIllnessAttachments()
    {
        return $this->hasMany(OccupationalIllnessAttachment::className(), ['occupational_illness_id' => 'id']);
    }
}
