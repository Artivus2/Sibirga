<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "company_department_info".
 *
 * @property int $id
 * @property int $company_department_id Внешний идентификатор участка
 * @property string $legal_address Юридический адрес
 * @property string $phone_fax Телефон/факс
 * @property int $head_worker_id Внешний идентификатор руководителя
 * @property string $head_phone_number Телефон руководителя
 * @property string $activitty_type Вид деятельности
 * @property string $danger_factor Опасные факторы
 * @property string $border_company_department Границы участка/объекта
 * @property string $work_schedule График выполнения работ
 * @property int $responsible_safe_work_worker_id Внешний идентификатор ответственного за безопасное ведение работ
 * @property string $responsible_safe_work_phone_number Телефон  ответственного за безопасное ведение работ
 * @property string $date_start Дата начала работ
 * @property string $date_end Дата окончания работ
 *
 * @property CompanyDepartment $companyDepartment
 * @property Worker $headWorker
 * @property Worker $responsibleSafeWorkWorker
 */
class CompanyDepartmentInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_department_info';
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
            [['company_department_id'], 'required'],
            [['company_department_id', 'head_worker_id', 'responsible_safe_work_worker_id'], 'integer'],
            [['date_start', 'date_end'], 'safe'],
            [['legal_address', 'phone_fax', 'head_phone_number', 'activitty_type', 'danger_factor', 'border_company_department', 'work_schedule', 'responsible_safe_work_phone_number'], 'string', 'max' => 255],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['head_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['head_worker_id' => 'id']],
            [['responsible_safe_work_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['responsible_safe_work_worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_department_id' => 'Внешний идентификатор участка',
            'legal_address' => 'Юридический адрес',
            'phone_fax' => 'Телефон/факс',
            'head_worker_id' => 'Внешний идентификатор руководителя',
            'head_phone_number' => 'Телефон руководителя',
            'activitty_type' => 'Вид деятельности',
            'danger_factor' => 'Опасные факторы',
            'border_company_department' => 'Границы участка/объекта',
            'work_schedule' => 'График выполнения работ',
            'responsible_safe_work_worker_id' => 'Внешний идентификатор ответственного за безопасное ведение работ',
            'responsible_safe_work_phone_number' => 'Телефон  ответственного за безопасное ведение работ',
            'date_start' => 'Дата начала работ',
            'date_end' => 'Дата окончания работ',
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
    public function getHeadWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'head_worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getResponsibleSafeWorkWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'responsible_safe_work_worker_id']);
    }
}
