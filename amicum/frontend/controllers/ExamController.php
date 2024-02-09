<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers;

use backend\controllers\Assistant as BackendAssistant;
use backend\controllers\const_amicum\StatusEnumController;
use backend\controllers\const_amicum\TypeBriefingEnumController;
use backend\controllers\const_amicum\TypeTestEnumController;
use backend\controllers\const_amicum\VidDocumentEnumController;
use Exception;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\handbooks\HandbookDepartmentController;
use frontend\controllers\handbooks\HandbookEmployeeController;
use frontend\controllers\notification\NotificationController;
use frontend\controllers\ordersystem\OrderController;
use frontend\controllers\service\Excel;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Attachment;
use frontend\models\Company;
use frontend\models\CompanyDepartment;
use frontend\models\Document;
use frontend\models\Examination;
use frontend\models\ExaminationAnswer;
use frontend\models\ExaminationAttachment;
use frontend\models\Injunction;
use frontend\models\KindPlanTraining;
use frontend\models\KindTest;
use frontend\models\Map;
use frontend\models\Media;
use frontend\models\MediaGroup;
use frontend\models\MediaMediaTheme;
use frontend\models\MediaTheme;
use frontend\models\OrderInstructionPb;
use frontend\models\PlanTraining;
use frontend\models\PlanTrainingKindTest;
use frontend\models\PlanTrainingTest;
use frontend\models\PredExamHistory;
use frontend\models\Question;
use frontend\models\QuestionAnswer;
use frontend\models\QuestionMedia;
use frontend\models\QuestionParagraphPb;
use frontend\models\Test;
use frontend\models\TestCompanyDepartment;
use frontend\models\TestMap;
use frontend\models\TestQuestion;
use frontend\models\TestQuestionAnswer;
use frontend\models\TypeTest;
use frontend\models\TypeTestType;
use frontend\models\Worker;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;

class ExamController extends Controller
{
    #region Блок структуры контроллера
    /** Конструктор */
    // AddTest()                        - Метод добавление теста
    // ListTest()                       - Метод получения списка тестов
    // DeleteTest()                     - Метод удаления теста
    // GetTest()                        - Метод получения конкретного теста по его ID
    // SaveTest()                       - Метод сохранения конкретного теста

    // ListKindTest()                   - Метод получения списка видов тестов
    // AddKindTest()                    - Метод добавление вида теста
    // DeleteKindTest()                 - Метод удаления вида теста

    // ListTypeTest()                   - Метод получения списка типов тестов

    // AddQuestion()                    - Метод добавление вопроса
    // ListQuestion()                   - Метод получения списка вопросов
    // DeleteQuestion()                 - Метод удаления вопроса
    // SaveQuestion()                   - Метод сохранения вопроса

    // CopyTestToKindTest()             - Метод копирования теста в вид теста
    // AddTestToTest()                  - Метод добавления теста в тест

    // CalcCompleteFillTest()           - Метод расчета заполненности теста

    // ListMap()                        - Метод получения списка карт
    // GetDocumentParagraphPbList       - Метод получения списка документов с их пунктами

    // SaveMedia                        - Метод сохранения медиа
    // SaveMediaGroup                   - Метод сохранения группы медиа
    // GetMediaToGroupList              - Метод получения списка медиа по группам
    // GetMediaThemeList                - Метод получения списка тем медиа

    /** Экзаменатор */
    // PersonalListTest()               - Метод получения персонального списка тестов
    // GetTestForControlKnowledge()     - Метод получения теста для проверки знаний
    // SaveAnswerFromControlKnowledge() - Метод сохранения ответа работника при проверке знаний
    // CreateControlKnowledge()         - Метод создания проверки знаний
    // GetListPersonalExamination()     - Метод получения списка экзаменов пройденных пользователем
    // GetPersonalPlace()               - Метод получения рейтинга пользователя по шахте и по участку
    // GetPersonalStatistic()           - Метод получения персональной статистики в экзаменаторе
    // SaveExaminationAttachment        - Метод сохранения вложения для тестирования
    // GetExaminationAttachmentList     - Метод получения списка вложений тестирования

    /** Аналитика */
    // GetAnalyticData()                - Метод получения статистики предсменного экзаменатора
    // GetPersonalAnalyticData()        - Метод получения персональной статистики предсменного экзаменатора
    // GetCompaniesAnalyticData()       - Метод получения статистики по департаментам предсменного экзаменатора
    // GetCompanyAnalyticData()         - Метод получения статистики по департаменту предсменного экзаменатора
    // GetListCompanyExamination()      - Метод получения списка экзаменов пройденных по подразделению

    /** Журнал предсменного экзаменатора */
    // GetPredExam                      - Метод получения сведений о прохождении предсменного тестирования
    // GetSummaryPredExam               - Метод получения сводных сведений о прохождении предсменного тестирования
    // SendEmailReportSummaryPredExam   - Отправка сводных сведений о прохождении предсменного тестирования на электронную почту

    /** Планирование обучения сотрудников */
    // SavePlanTrainingList             - Метод сохранения листа планов обучения
    // GetPlanTrainingList              - Метод получение списка планов обучения


    #endregion

    /**
     * AddTest() - Метод добавление теста
     * входные данные:
     *      test_id         - ключ теста
     *      kind_test_id    - ключ вида теста
     *      title           - название теста
     *      test_id         - ключ теста
     *      actual_status   - статус теста
     * выходные данные:
     *      test_id         - ключ теста
     *      kind_test_id    - ключ вида теста
     *      title           - название теста
     *      test_id         - ключ теста
     *      actual_status   - статус теста
     *      date_time_create- дата создания теста
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=AddTest&subscribe=&data={"test_id":-1,"kind_test_id":1,"title":"тест №1","actual_status":true}
     */
    public static function AddTest($data_post = NULL): array
    {
        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("AddTest");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'test_id') ||
                !property_exists($post, 'kind_test_id') ||
                !property_exists($post, 'title') ||
                !property_exists($post, 'actual_status') ||
                $post->test_id == '' ||
                $post->kind_test_id == '' ||
                $post->title == '' ||
                $post->actual_status == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $test_id = $post->test_id;
            $kind_test_id = $post->kind_test_id;
            $title = $post->title;
            $actual_status = $post->actual_status;

            $test = Test::findOne(['id' => $test_id]);
            if (!$test) {
                $test = new Test();
            }

            $test->kind_test_id = $kind_test_id;
            $test->title = $title;
            $test->actual_status = $actual_status ? 1 : 0;
            $test->date_time_create = Assistant::GetDateTimeNow();

            if (!$test->save()) {
                $log->addData($test->errors, '$test->errors', __LINE__);
                throw new Exception("Не удалось сохранить данные в модели Test");
            }

            $test->refresh();
            $post->test_id = $test->id;
            $post->date_time_create = $test->date_time_create;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $post], $log->getLogAll());
    }


    /**
     * ListTest() - Метод получения списка тестов
     * выходные данные:
     *  {test_id}
     *      test_id             - ключ теста
     *      kind_test_id        - ключ вида теста
     *      kind_test_title     - название вида теста
     *      title               - название теста
     *      test_id             - ключ теста
     *      actual_status       - статус теста
     *      date_time_create    - дата создания теста
     *      complete_fill_test  - сведения о заполненности теста
     *          count_all                       - общее количество
     *          count_all_fill                  - общее заполненное количество
     *          count_question_all              - количество всех вопросов
     *          count_question_fill             - количество заполненных вопросов
     *          count_answer_all                - количество всех ответов
     *          count_answer_fill               - количество заполненных ответов
     *          flag_typeTestTypes              - наличие типа теста
     *          flag_testMaps                   - наличие назначенной карты
     *          flag_testCompanyDepartments     - наличие привязки к CompanyDepartments
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=ListTest&subscribe=&data={}
     */
    public static function ListTest($data_post = NULL): array
    {
        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("ListTest");

        try {
            $log->addLog("Начало выполнения метода");

            $tests = Test::find()
                ->innerJoinWith('kindTest')
                ->joinWith('typeTestTypes')
                ->joinWith('testMaps')
                ->joinWith('testCompanyDepartments')
                ->joinWith('testQuestions.question')
                ->joinWith('testQuestions.testQuestionAnswers')
                ->asArray()
                ->all();

            foreach ($tests as $test) {
                $response = self::CalcCompleteFillTest($test);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка расчета полноты заполненности теста");
                }
                $complete_fill_test = $response['Items'];

                $result[$test['id']] = array(
                    'test_id' => $test['id'],
                    'kind_test_id' => $test['kind_test_id'],
                    'kind_test_title' => $test['kindTest']['title'],
                    'title' => $test['title'],
                    'actual_status' => $test['actual_status'],
                    'date_time_create' => $test['date_time_create'],
                    'complete_fill_test' => $complete_fill_test
                );
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * CalcCompleteFillTest() - Метод расчета заполненности теста
     * Входные данные:
     *      test
     *          test_id             - ключ теста/билета
     *          testQuestions       - список вопросов теста
     *              {test_question_id}  - ключ привязки вопроса к тесту
     *                  id                      - ключ привязки вопроса к тесту
     *                  question                - вопрос
     *                      id                      - ключ вопроса
     *                      title                   - название вопроса
     *                      question                - сам вопроса
     *                      comment                 - Комментарий при неправильном ответе
     *                  actual_status           - актуальный или нет вопрос в данном тесте
     *                  date_time_create        - дата создания вопроса
     *                  testQuestionAnswers     - список ответов
     *                      {test_question_answer_id}   - ключ привязки вопроса и ответа
     *                          id                          - ключ привязки вопроса и ответа
     *                          title                       - сам ответ
     *                          count_mark                  - количество баллов
     *                          flag_true                   - признак верности (1/0)
     *                          number_in_order             - номер по порядку
     *                          actual_status               - актуальный или нет ответ в данном вопросе
     * Выходные данные:
     *      count_all                       - общее количество
     *      count_all_fill                  - общее заполненное количество
     *      count_question_all              - количество всех вопросов
     *      count_question_fill             - количество заполненных вопросов
     *      count_answer_all                - количество всех ответов
     *      count_answer_fill               - количество заполненных ответов
     *      flag_typeTestTypes              - наличие типа теста
     *      flag_testMaps                   - наличие назначенной карты
     *      flag_testCompanyDepartments     - наличие привязки к CompanyDepartments
     *      flag_test_complete              - полное заполнение теста
     */
    public static function CalcCompleteFillTest($test): array
    {
        $result = array(
            'count_all' => 0,                                                                                           // общее количество
            'count_all_fill' => 0,                                                                                      // общее заполненное количество
            'count_question_all' => 0,                                                                                  // количество всех вопросов
            'count_question_fill' => 0,                                                                                 // количество заполненных вопросов
            'count_answer_all' => 0,                                                                                    // количество всех ответов
            'count_answer_fill' => 0,                                                                                   // количество заполненных ответов
            'flag_typeTestTypes' => 0,
            'flag_testMaps' => 0,
            'flag_testCompanyDepartments' => 0,
            'flag_test_complete' => 0
        );
        $count_record = 0;

        $log = new LogAmicumFront("CalcCompleteFillTest");

        try {
            $log->addLog("Начало выполнения метода");


            foreach ($test['testQuestions'] as $question) {
                $count_flag_true = 0;
                foreach ($question['testQuestionAnswers'] as $answer) {

                    if (
                        $answer['title'] != "" and
                        $answer['count_mark'] !== null and
                        $answer['number_in_order'] !== null
                    ) {
                        if ($answer['flag_true'] = 1 and $answer['count_mark'] > 0) {
                            $count_flag_true++;
                            $result['count_answer_fill']++;
                        } else {
                            $result['count_answer_fill']++;
                        }
                        $result['count_all_fill']++;
                    }

                    $result['count_answer_all']++;
                    $result['count_all']++;
                }

                if (
                    $question['question']['title'] != "" and
                    strlen($question['question']['title']) > 10 and
                    $question['question']['question'] != "" and
                    strlen($question['question']['question']) > 10 and
                    count($question['testQuestionAnswers']) > 1 and
                    $count_flag_true > 0
                ) {
                    $result['count_question_fill']++;
                    $result['count_all_fill']++;
                }

                $result['count_question_all']++;
                $result['count_all']++;
            }

            if ($test['typeTestTypes']) {
                $result['flag_typeTestTypes'] = 1;
            }
            if ($test['testMaps']) {
                $result['flag_testMaps'] = 1;
            }
            if ($test['testCompanyDepartments']) {
                $result['flag_testCompanyDepartments'] = 1;
            }

            if (
                $result['flag_typeTestTypes'] == 1 &&
                $result['flag_testMaps'] == 1 &&
                $result['flag_testCompanyDepartments'] == 1 &&
                $result['count_all_fill'] == $result['count_all']
            ) {
                $result['flag_test_complete'] = 1;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * CalcCompleteFillQuestion() - Метод расчета заполненности вопроса
     * Входные данные:
     *          question           - вопрос
     *                  id                      - ключ вопроса
     *                  title                   - название вопроса
     *                  question                - сам вопроса
     *                  comment                 - Комментарий при неправильном ответе
     *                  date_time_create        - дата создания вопроса
     *                  questionAnswers         - список ответов
     *                      {test_question_answer_id}   - ключ привязки вопроса и ответа
     *                          question_answer_id          - ключ привязки вопроса и ответа
     *                          title                - сам ответ
     *                          count_mark                  - количество баллов
     *                          flag_true                   - признак верности (1/0)
     *                          number_in_order             - номер по порядку
     *                          actual_status               - актуальный или нет ответ в данном вопросе
     * Выходные данные:
     *      count_all              - общее количество
     *      count_all_fill         - общее заполненное количество
     *      count_question_all     - количество всех вопросов
     *      count_question_fill    - количество заполненных вопросов
     *      count_answer_all       - количество всех ответов
     *      count_answer_fill      - количество заполненных ответов
     *      flag_question_complete - полное заполнение вопроса
     */
    public static function CalcCompleteFillQuestion($question): array
    {
        $result = array(
            'count_all' => 0,                                                                                           // общее количество
            'count_all_fill' => 0,                                                                                      // общее заполненное количество
            'count_question_all' => 0,                                                                                  // количество всех вопросов
            'count_question_fill' => 0,                                                                                 // количество заполненных вопросов
            'count_answer_all' => 0,                                                                                    // количество всех ответов
            'count_answer_fill' => 0,                                                                                   // количество заполненных ответов
            'flag_question_complete' => 0
        );
        $count_record = 0;

        $log = new LogAmicumFront("CalcCompleteFillQuestion");

        try {
            $log->addLog("Начало выполнения метода");

            foreach ($question['questionAnswers'] as $answer) {

                if (
                    $answer['title'] != "" and
                    strlen($answer['title']) > 2 and
                    $answer['count_mark'] !== null and
                    $answer['number_in_order'] !== null
                ) {
                    $result['count_answer_fill']++;
                    $result['count_all_fill']++;
                }

                $result['count_answer_all']++;
                $result['count_all']++;
            }

            if (
                $question['title'] != "" and
                strlen($question['title']) > 5 and
                $question['question'] != "" and
                strlen($question['question']) > 10 and
                count($question['questionAnswers']) > 0
            ) {
                $result['count_question_fill']++;
                $result['count_all_fill']++;
            }

            $result['count_question_all']++;
            $result['count_all']++;

            if (
                $result['count_all_fill'] == $result['count_all']
            ) {
                $result['flag_question_complete'] = 1;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * DeleteTest() - Метод удаления теста
     * выходные данные:
     *      test_id     - ключ теста
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=DeleteTest&subscribe=&data={"test_id":1}
     */
    public static function DeleteTest($data_post = NULL): array
    {
        $result = null;
        $post = null;
        $count_record = 0;

        $log = new LogAmicumFront("DeleteTest");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'test_id') ||
                $post->test_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $test_id = $post->test_id;

            $result = Test::deleteAll(['id' => $test_id]);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * ListKindTest() - Метод получения списка видов тестов
     * выходные данные:
     *  {id}
     *      id              - ключ вида теста
     *      title           - название вида теста
     *      actual_status   - статус актуальности вида теста
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=ListKindTest&subscribe=&data={}
     */
    public static function ListKindTest($data_post = NULL): array
    {
        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("ListKindTest");

        try {
            $log->addLog("Начало выполнения метода");

            $result = KindTest::find()
                ->indexBy('id')
                ->asArray()
                ->all();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * AddKindTest() - Метод добавление вида теста
     * входные данные:
     *      id              - ключ вида теста
     *      title           - название теста
     *      actual_status   - статус актуальности вида теста
     * выходные данные:
     *      id              - ключ вида теста
     *      title           - название теста
     *      actual_status   - статус актуальности вида теста
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=AddKindTest&subscribe=&data={"id":1,"title":"тест №1","actual_status":true}
     */
    public static function AddKindTest($data_post = NULL): array
    {
        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("AddKindTest");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'id') ||
                !property_exists($post, 'title') ||
                !property_exists($post, 'actual_status') ||
                $post->id == '' ||
                $post->title == '' ||
                $post->actual_status == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $id = $post->id;
            $title = $post->title;
            $actual_status = $post->actual_status;


            $kind_test_new = KindTest::findOne(['id' => $id]);
            if (!$kind_test_new) {
                $kind_test_new = new KindTest();
            }

            $kind_test_new->title = $title;
            $kind_test_new->actual_status = $actual_status ? 1 : 0;

            if (!$kind_test_new->save()) {
                $log->addData($kind_test_new->errors, '$kind_test_new->errors', __LINE__);
                throw new Exception("Не удалось сохранить данные в модели KindTest");
            }

            $kind_test_new->refresh();
            $post->id = $kind_test_new->id;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $post], $log->getLogAll());
    }


    /**
     * DeleteKindTest() - Метод удаления вида теста
     * выходные данные:
     *      kind_test_id     - ключ вида теста
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=DeleteKindTest&subscribe=&data={"kind_test_id":5}
     */
    public static function DeleteKindTest($data_post = NULL): array
    {
        $result = null;
        $post = null;
        $count_record = 0;

        $log = new LogAmicumFront("DeleteKindTest");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'kind_test_id') ||
                $post->kind_test_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $kind_test_id = $post->kind_test_id;

            $exams = Examination::find()
                ->joinWith('test')
                ->where(['kind_test_id' => $kind_test_id])
                ->all();
            if (!$exams) {
                $result = KindTest::deleteAll(['id' => $kind_test_id]);
            } else {
                throw new Exception('Удаление невозможно, по билетам была проведена проверка знаний. Удалите сперва все билеты в группе.');
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * ListTypeTest() - Метод получения списка типов тестов
     * выходные данные:
     *  {test_id}
     *      id              - ключ типа теста
     *      title           - название типа теста
     *      actual_status   - статус актуальности типа теста
     *      date_time_create- дата создания типа теста
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=ListTypeTest&subscribe=&data={}
     */
    public static function ListTypeTest($data_post = NULL): array
    {
        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("ListTypeTest");

        try {
            $log->addLog("Начало выполнения метода");

            $result = TypeTest::find()
                ->indexBy('id')
                ->asArray()
                ->all();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * AddQuestion() - Метод добавление вопроса
     * входные данные:
     *      question_id     - ключ вопроса
     *      title           - название вопроса
     *      question        - вопрос
     *      comment         - Комментарий при неправильном ответе
     * выходные данные:
     *      question_id     - ключ вопроса
     *      title           - название вопроса
     *      question        - вопрос
     *      date_time_create- дата создания вопроса
     *      comment         - Комментарий при неправильном ответе
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=AddQuestion&subscribe=&data={"question_id":-1,"title":"тест №1","question":"sdfgsdfg"}
     */
    public static function AddQuestion($data_post = NULL): array
    {
        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("AddQuestion");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'question_id') ||
                !property_exists($post, 'title') ||
                !property_exists($post, 'question') ||
                $post->question_id == '' ||
                $post->title == '' ||
                $post->question == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $question_id = $post->question_id;
            $title = $post->title;
            $question_description = $post->question;
            $question_comment = $post->comment;

            $question = Question::findOne(['id' => $question_id]);
            if (!$question) {
                $question = new Question();
            }

            $question->title = $title;
            $question->question = $question_description;
            $question->comment = $question_comment;
            $question->date_time_create = Assistant::GetDateTimeNow();

            if (!$question->save()) {
                $log->addData($question->errors, '$test->errors', __LINE__);
                throw new Exception("Не удалось сохранить данные в модели Question");
            }

            $question->refresh();
            $post->question_id = $question->id;
            $post->date_time_create = $question->date_time_create;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $post], $log->getLogAll());
    }


    /**
     * ListQuestion() - Метод получения списка вопросов
     * Входные данные:
     *  question_id - ключ вопроса, если нужен только один вопрос, то задается в качестве фильтра
     * Выходные данные:
     *  {question_id}
     *      question_id                         - ключ вопроса
     *      title                               - название вопроса
     *      question                            - вопрос
     *      comment                             - Комментарий при неправильном ответе
     *      date_time_create                    - дата создания вопроса
     *      complete_fill_question              - подробней в CalcCompleteFillQuestion
     *          count_all
     *          count_all_fill
     *          count_question_all
     *          count_question_fill
     *          count_answer_all
     *          count_answer_fill
     *      answers                             - массив ответов
     *          id                                  - ключ ответов
     *          question_id                         - ключ вопроса
     *          title                               - название ответа
     *          count_mark                          - количество балов за ответ
     *          flag_true                           - флаг правильного ответа
     *          number_in_order                     - номер по порядку
     *          actual_status                       - статус актуальности
     *      medias                              - список медиа
     *          {question_media_id}                 - ключ привязки вопроса и медиа
     *              question_media_id                   - ключ привязки вопроса и медиа
     *              media_id                            - ключ медиа
     *              media_group_id                      - группа медиа
     *              attachment_id                       - ключ вложения
     *              attachment_path                     - путь вложения
     *              attachment_title                    - название вложения
     *              attachment_type                     - тип вложения(.mp4)
     *              media_themes [              - массив привязанных тем
     *                  id                          - ключ связи с темой
     *                  media_themes_id             - ключ темы
     *                  title                       - название темы
     *              ]
     *      paragraphs_pb                       - список пунктов
     *          {question_paragraph_pb_id}          - ключ привязки вопроса и пункта
     *              question_paragraph_pb_id            - ключ привязки вопроса и пункта
     *              paragraph_pb_id                     - ключ пункта
     *              paragraph_pb_document_id            - ключ документа
     *              paragraph_pb_text                   - текст пункта
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=ListQuestion&subscribe=&data={"question_id":12}
     */
    public static function ListQuestion($data_post = NULL): array
    {
        $result = null;
        $count_record = 0;
        $post = (object)array();
        $log = new LogAmicumFront("ListQuestion");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            }

            $question_filter = [];

            if (
                property_exists($post, 'question_id') and
                $post->question_id != ''
            ) {
                $question_filter = array('question.id' => $post->question_id);
            }

            $questions = Question::find()
                ->joinWith('questionAnswers')
                ->joinWith('questionMedia.media.attachment')
                ->joinWith('questionMedia.media.mediaMediaThemes.mediaTheme')
                ->joinWith('questionParagraphPbs.paragraphPb')
                ->where($question_filter)
                ->asArray()
                ->all();

            $log->addLog("Получил данные");

            foreach ($questions as $question) {
                $response = self::CalcCompleteFillQuestion($question);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка расчета полноты заполненности теста");
                }
                $complete_fill_question = $response['Items'];

                foreach ($question['questionMedia'] as $question_media) {
                    foreach ($question_media['media']['mediaMediaThemes'] as $media_media_theme) {
                        $media_themes[] = array(
                            'id' => $media_media_theme['id'],
                            'media_themes_id' => $media_media_theme['media_theme_id'],
                            'title' => $media_media_theme['mediaTheme']['title']
                        );
                    }
                    if (!isset($media_themes)) {
                        $media_themes = (object)array();
                    }

                    $medias[$question_media['id']] = array(
                        'question_media_id' => $question_media['id'],
                        'media_id' => $question_media['media']['id'],
                        'media_group_id' => $question_media['media']['media_group_id'],
                        'attachment_id' => $question_media['media']['attachment']['id'],
                        'attachment_path' => $question_media['media']['attachment']['path'],
                        'attachment_title' => $question_media['media']['attachment']['title'],
                        'attachment_type' => $question_media['media']['attachment']['attachment_type'],
                        'media_themes' => $media_themes
                    );
                    unset($media_themes);
                }
                if (!isset($medias)) {
                    $medias = (object)array();
                }

                foreach ($question['questionParagraphPbs'] as $question_paragraph_pb) {
                    $paragraphs_pb[$question_paragraph_pb['id']] = array(
                        'question_paragraph_pb_id' => $question_paragraph_pb['id'],
                        'paragraph_pb_id' => $question_paragraph_pb['paragraphPb']['id'],
                        'paragraph_pb_document_id' => $question_paragraph_pb['paragraphPb']['document_id'],
                        'paragraph_pb_text' => $question_paragraph_pb['paragraphPb']['text']
                    );
                }
                if (!isset($paragraphs_pb)) {
                    $paragraphs_pb = (object)array();
                }

                $result[$question['id']] = array(
                    'question_id' => $question['id'],
                    'title' => $question['title'],
                    'question' => $question['question'],
                    'comment' => $question['comment'],
                    'date_time_create' => $question['date_time_create'],
                    'complete_fill_question' => $complete_fill_question,
                    'answers' => $question['questionAnswers'],
                    'medias' => $medias,
                    'paragraphs_pb' => $paragraphs_pb
                );
                unset($medias);
                unset($paragraphs_pb);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * DeleteQuestion() - Метод удаления вопроса
     * выходные данные:
     *      question_id     - ключ вопроса
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=DeleteQuestion&subscribe=&data={"question_id":1}
     */
    public static function DeleteQuestion($data_post = NULL): array
    {
        $result = null;
        $post = null;
        $count_record = 0;

        $log = new LogAmicumFront("DeleteQuestion");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'question_id') ||
                $post->question_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $question_id = $post->question_id;

            $result = Question::deleteAll(['id' => $question_id]);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * SaveQuestion() - Метод сохранения вопроса
     * входные данные:
     *          question           - список вопросов теста
     *                  question_id             - ключ вопроса
     *                  question_title          - название вопроса
     *                  question                - сам вопроса
     *                  comment                 - Комментарий при неправильном ответе
     *                  date_time_create        - дата создания вопроса
     *                  answers                 - список ответов
     *                      {question_answer_id}    - ключ привязки вопроса и ответа
     *                          question_answer_id          - ключ привязки вопроса и ответа
     *                          answer_title                - сам ответ
     *                          count_mark                  - количество баллов
     *                          flag_true                   - признак верности (1/0)
     *                          number_in_order             - номер по порядку
     *                          actual_status               - актуальный или нет ответ в данном вопросе
     *                          delete                      - статус удаления ответа
     *                  medias                          - список медиа
     *                      {question_media_id}             - ключ привязки вопроса и медиа
     *                          media_id                        - ключ медиа
     *                          delete                          - статус удаления связь
     *                  paragraphs_pb                   - список пунктов
     *                      {question_paragraph_pb_id}      - ключ привязки вопроса и пункта
     *                          paragraph_pb_id                 - ключ пункта
     *                          delete                          - статус удаления связь
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=SaveQuestion&subscribe=&data={}
     */
    public static function SaveQuestion($data_post = NULL): array
    {

        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("SaveQuestion");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }
//            $log->addData($post,'$post',__LINE__);

            if (
                !property_exists($post, 'question') ||
                $post->question == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $question = $post->question;


            $log->addLog("Сохраняю тест");
            $question_new = Question::find()
                ->where(['id' => $question->question_id])
                ->one();
            if (!$question_new) {
                $question_new = new Question();
            }

            $question_new->title = $question->question_title;
            $question_new->question = $question->question;
            $question_new->comment = $question->comment;
            $question_new->date_time_create = Assistant::GetDateTimeNow();

            if (!$question_new->save()) {
                $log->addData($question_new->errors, '$question_new->errors', __LINE__);
                throw new Exception("Ошибка сохранения модели Question");
            }

            $question_new->refresh();

            $post->question->question_id = $question_new->id;
            $question_id = $question_new->id;
            $post->question->date_time_create = $question_new->date_time_create;

            foreach ($question->answers as $key_answ => $answer) {
                if (property_exists($answer, 'delete') and $answer->delete) {
                    QuestionAnswer::deleteAll(['id' => $answer->id]);
                    unset($post->question->answers->{$key_answ});
                } else {
                    $answer_new = QuestionAnswer::find()
                        ->where(['id' => $answer->id])
                        ->orWhere(['question_id' => $question_id, 'title' => $answer->title])
                        ->one();
                    if (!$answer_new) {
                        $answer_new = new QuestionAnswer();
                    }
                    $answer_new->question_id = $question_id;
                    $answer_new->title = $answer->title;
                    $answer_new->count_mark = $answer->count_mark;
                    $answer_new->flag_true = $answer->flag_true;
                    $answer_new->number_in_order = $answer->number_in_order;
                    $answer_new->actual_status = $answer->actual_status;

                    if (!$answer_new->save()) {
                        $log->addData($answer_new->errors, '$answer_new->errors', __LINE__);
                        throw new Exception("Ошибка сохранения модели QuestionAnswer");
                    }

                    $answer_new->refresh();

                    $post->question->answers->{$key_answ}->id = $answer_new->id;
                }
            }

            if (isset($question->medias)) {
                foreach ($question->medias as $question_media_id => $media) {
                    if (property_exists($media, 'delete') and $media->delete) {
                        QuestionMedia::deleteAll(['id' => $question_media_id]);
                        unset($post->question->$media->{$question_media_id});
                    } else {
                        $question_media = QuestionMedia::findOne(['question_id' => $question_id, 'media_id' => $media->media_id]);
                        if (!$question_media) {
                            $question_media = new QuestionMedia();
                        }
                        $question_media->question_id = $question_id;
                        $question_media->media_id = $media->media_id;

                        if (!$question_media->save()) {
                            $log->addData($question_media->errors, '$question_media->errors', __LINE__);
                            throw new Exception("Ошибка сохранения модели QuestionMedia");
                        }

                        $medias[$question_media->id] = $media;
                    }
                }
                $post->question->medias = $medias;
            }

            if (isset($question->paragraphs_pb)) {
                foreach ($question->paragraphs_pb as $question_paragraph_pb_id => $paragraph_pb) {
                    if (property_exists($paragraph_pb, 'delete') and $paragraph_pb->delete) {
                        QuestionParagraphPb::deleteAll(['id' => $question_paragraph_pb_id]);
                        unset($post->question->$paragraph_pb->{$question_paragraph_pb_id});
                    } else {
                        $question_paragraph = QuestionParagraphPb::findOne(['question_id' => $question_id, 'paragraph_pb_id' => $paragraph_pb->paragraph_pb_id]);
                        if (!$question_paragraph) {
                            $question_paragraph = new QuestionParagraphPb();
                        }
                        $question_paragraph->question_id = $question_id;
                        $question_paragraph->paragraph_pb_id = $paragraph_pb->paragraph_pb_id;

                        if (!$question_paragraph->save()) {
                            $log->addData($question_paragraph->errors, '$question_paragraph->errors', __LINE__);
                            throw new Exception("Ошибка сохранения модели QuestionParagraphPb");
                        }

                        $paragraphs_pb[$question_paragraph->id] = $paragraph_pb;
                    }
                }
                $post->question->paragraphs_pb = $paragraphs_pb;
            }

            $result = $post;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    public function actionIndex()
    {
        return $this->render('index');
    }


    /**
     * GetTest() - Метод получения конкретного теста по его ID
     * входные данные:
     *      test_id     - ключ вопроса
     * выходные данные:
     *      test
     *          test_id             - ключ теста/билета
     *          test_title          - название теста/билета
     *          actual_status       - статус билета (активный/неактивный 1/0)
     *          date_time_create    - дата создания теста/билета
     *          kind_test_id        - ключ вида теста/билета (пром без)
     *          kind_test_title     - название вида теста/билета
     *
     *          maps                - список карт, в которых есть тест/билет
     *              {test_map_id}       - ключ привязки карты и теста
     *                  test_map_id             - ключ привязки карты и теста/билета
     *                  map_id                  - ключ карты
     *                  map_title               - название карты
     *                  map_count_number        - количество номеров на карте
     *                  map_attachment_id       - ключ вложения, в котором лежит карта
     *                  map_attachment_path     - путь до карты
     *                  number_on_map           - номер на карте данного теста/билета
     *
     *          type_tests          - в каких типах теста принимает участие тест/билет
     *              {type_test_id}      - ключ типа теста
     *                  type_test_type_id       - ключ привязки типа теста и самого теста
     *                  type_test_id            - ключ типа теста
     *                  status                  - статус типа теста (выбран/не выбран)
     *
     *          questions           - список вопросов теста
     *              {test_question_id}  - ключ привязки вопроса к тесту
     *                  test_question_id        - ключ привязки вопроса к тесту
     *                  question_id             - ключ вопроса
     *                  question_title          - название вопроса
     *                  question                - сам вопроса
     *                  comment                 - Комментарий при неправильном ответе
     *                  actual_status           - актуальный или нет вопрос в данном тесте
     *                  date_time_create        - дата создания вопроса
     *                  answers                 - список ответов
     *                      {test_question_answer_id}   - ключ привязки вопроса и ответа
     *                          test_question_answer_id     - ключ привязки вопроса и ответа
     *                          answer_title                - сам ответ
     *                          count_mark                  - количество баллов
     *                          flag_true                   - признак верности (1/0)
     *                          number_in_order             - номер по порядку
     *                          actual_status               - актуальный или нет ответ в данном вопросе
     *
     *          company_departments - список департаментов для кого предназначен тест/билет
     *              {company_department_id}     - ключ конкретного подразделения
     *                  test_company_department_id      - ключ привязки департамента к тесту
     *                  company_department_id           - ключ конкретного подразделения
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=GetTest&subscribe=&data={"test_id":15}
     */
    public static function GetTest($data_post = NULL): array
    {
        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("GetTest");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'test_id') ||
                $post->test_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $test_id = $post->test_id;


            $test = Test::find()
                ->innerJoinWith('kindTest')
                ->joinWith('typeTestTypes.typeTest')
                ->joinWith('testQuestions.question')
                ->joinWith('testQuestions.testQuestionAnswers')
                ->joinWith('testMaps.map.attachment')
                ->joinWith('testCompanyDepartments')
                ->where(['test.id' => $test_id])
                ->one();

            if (!$test) {
                throw new Exception("Запрашиваемого теста не существует");
            }

            $result['test'] = array(
                'test_id' => $test->id,
                'test_title' => $test->title,
                'actual_status' => $test->actual_status,
                'date_time_create' => $test->date_time_create,
                'kind_test_id' => $test->kind_test_id,
                'kind_test_title' => $test->kindTest->title,
                'maps' => (object)array(),
                'type_tests' => (object)array(),
                'questions' => (object)array(),
                'company_departments' => (object)array(),
            );

            foreach ($test->testMaps as $test_map) {                                                                    // Заполняем привязку теста/билета к конкретным картам и их пунктам
                $maps[$test_map->id] = array(
                    'test_map_id' => $test_map->id,
                    'map_id' => $test_map->map_id,
                    'map_title' => $test_map->map->title,
                    'map_count_number' => $test_map->map->count_number,
                    'map_attachment_id' => $test_map->map->attachment_id,
                    'map_attachment_path' => $test_map->map->attachment_id ? $test_map->map->attachment->path : "",
                    'number_on_map' => $test_map->number_on_map,
                );
            }

            if (isset($maps)) {
                $result['test']['maps'] = $maps;
            }

            foreach ($test->testCompanyDepartments as $test_company_department) {                                       // Заполняем привязку теста/билета к конкретным подразделениям
                $company_departments[$test_company_department->company_department_id] = array(
                    'test_company_department_id' => $test_company_department->id,
                    'company_department_id' => $test_company_department->company_department_id,
                );
            }

            if (isset($company_departments)) {
                $result['test']['company_departments'] = $company_departments;
            }

            foreach ($test->typeTestTypes as $test_type) {                                                              // Заполняем привязку теста/билета к конкретным типам теста
                $type_tests[$test_type->type_test_id] = array(
                    'type_test_type_id' => $test_type->id,
                    'type_test_id' => $test_type->type_test_id,
                    'status' => $test_type->status,
                );
            }

            if (isset($type_tests)) {
                $result['test']['type_tests'] = $type_tests;
            }

            foreach ($test->testQuestions as $test_question) {                                                          // Заполняем привязку теста/билета к конкретным вопросам
                $test_questions[$test_question->id] = array(
                    'test_question_id' => $test_question->id,
                    'question_id' => $test_question->question_id,
                    'question_title' => $test_question->question->title,
                    'question' => $test_question->question->question,
                    'comment' => $test_question->question->comment,
                    'actual_status' => $test_question->actual_status,
                    'date_time_create' => $test_question->date_time_create,
                    'answers' => (object)array(),
                );

                foreach ($test_question->testQuestionAnswers as $test_question_answer) {                                // Заполняем привязку вопроса к конкретным ответам
                    $answers[$test_question_answer->id] = array(
                        'test_question_answer_id' => $test_question_answer->id,
                        'answer_title' => $test_question_answer->title,
                        'count_mark' => $test_question_answer->count_mark,
                        'flag_true' => $test_question_answer->flag_true,
                        'number_in_order' => $test_question_answer->number_in_order,
                        'actual_status' => $test_question_answer->actual_status,
                    );
                }
                if (isset($answers)) {
                    $test_questions[$test_question->id]['answers'] = $answers;
                }

                unset($answers);
            }

            if (isset($test_questions)) {
                $result['test']['questions'] = $test_questions;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * SaveTest() - Метод сохранения конкретного теста
     * входные данные:
     *      test
     *          test_id             - ключ теста/билета
     *          test_title          - название теста/билета
     *          actual_status       - статус билета (активный/неактивный 1/0)
     *          date_time_create    - дата создания теста/билета
     *          kind_test_id        - ключ вида теста/билета (пром без)
     *          kind_test_title     - название вида теста/билета
     *
     *          maps                - список карт, в которых есть тест/билет
     *              {test_map_id}       - ключ привязки карты и теста
     *                  test_map_id             - ключ привязки карты и теста/билета
     *                  map_id                  - ключ карты
     *                  map_title               - название карты
     *                  map_count_number        - количество номеров на карте
     *                  map_attachment_id       - ключ вложения, в котором лежит карта
     *                  map_attachment_path     - путь до карты
     *                  number_on_map           - номер на карте данного теста/билета
     *
     *          type_tests          - в каких типах теста принимает участие тест/билет
     *              {type_test_id}      - ключ типа теста
     *                  type_test_type_id       - ключ привязки типа теста и самого теста
     *                  type_test_id            - ключ типа теста
     *                  status                  - статус типа теста (выбран/не выбран)
     *
     *          questions           - список вопросов теста
     *              {test_question_id}  - ключ привязки вопроса к тесту
     *                  test_question_id        - ключ привязки вопроса к тесту
     *                  question_id             - ключ вопроса
     *                  question_title          - название вопроса
     *                  question                - сам вопроса
     *                  comment                 - Комментарий при неправильном ответе
     *                  actual_status           - актуальный или нет вопрос в данном тесте
     *                  date_time_create        - дата создания вопроса
     *                  delete                  - статус удаления вопроса
     *                  answers                 - список ответов
     *                      {test_question_answer_id}   - ключ привязки вопроса и ответа
     *                          test_question_answer_id     - ключ привязки вопроса и ответа
     *                          answer_title                - сам ответ
     *                          count_mark                  - количество баллов
     *                          flag_true                   - признак верности (1/0)
     *                          number_in_order             - номер по порядку
     *                          actual_status               - актуальный или нет ответ в данном вопросе
     *                          delete                      - статус удаления ответа
     *
     *          company_departments - список департаментов для кого предназначен тест/билет
     *              {test_company_department_id}    - ключ привязки департамента к тесту
     *                  test_company_department_id      - ключ привязки департамента к тесту
     *                  company_department_id           - ключ конкретного подразделения
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=SaveTest&subscribe=&data={"test":{"test_id":4,"test_title":"тест №1","actual_status":1,"date_time_create":"2023-03-23 16:40:19","kind_test_id":1,"kind_test_title":"Промышленная безопасность","maps":{"3":{"test_map_id":3,"map_id":1,"map_title":"Карта 1","map_count_number":10,"map_attachment_id":null,"map_attachment_path":"","number_on_map":2},"4":{"test_map_id":4,"map_id":2,"map_title":"Карта 2","map_count_number":15,"map_attachment_id":10559,"map_attachment_path":"/img/medical_report/28-02-2020 13-04-24.1582884264_ЦОФ. Мыло.jpg","number_on_map":4}},"test_types":{"1":{"type_test_type_id":6,"type_test_id":1,"status":1}},"questions":{"1":{"test_question_id":1,"question_id":5,"question_title":"вопрос 1","question":"Есть ли жизнь на марсе","actual_status":1,"date_time_create":"2022-03-05 00:00:00","answers":{"1":{"test_question_answer_id":1,"answer_title":"Да","count_mark":1,"flag_true":1,"number_in_order":1,"actual_status":1},"2":{"test_question_answer_id":2,"answer_title":"Нет","count_mark":0,"flag_true":0,"number_in_order":2,"actual_status":1}}}},"company_departments":{"101":{"test_company_department_id":1,"company_department_id":101},"1":{"test_company_department_id":2,"company_department_id":1}}}}
     */
    public static function SaveTest($data_post = NULL): array
    {

        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("SaveTest");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }
//            $log->addData($post,'$post',__LINE__);

            if (
                !property_exists($post, 'test') ||
                $post->test == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");
            $test = $post->test;

            $test_new = Test::findOne(['id' => $test->test_id]);

            if (!$test_new) {
                $test_new = new Test();
            }

            $test_new->kind_test_id = $test->kind_test_id;
            $test_new->title = $test->test_title;
            $test_new->actual_status = $test->actual_status;
            $test_new->date_time_create = Assistant::GetDateTimeNow();

            if (!$test_new->save()) {
                $log->addData($test_new->errors, '$test_new->errors', __LINE__);
                throw new Exception("Ошибка сохранения модели Test");
            }

            $test_new->refresh();

            $post->test->test_id = $test_new->id;
            $post->test->date_time_create = $test_new->date_time_create;
            $test_id = $test_new->id;

            TypeTestType::deleteAll(['test_id' => $test_id]);

            foreach ($test->type_tests as $type_test) {
                $type_test_new = new TypeTestType();
                $type_test_new->test_id = $test_id;
                $type_test_new->type_test_id = $type_test->type_test_id;
                $type_test_new->status = $type_test->status;

                if (!$type_test_new->save()) {
                    $log->addData($type_test_new->errors, '$type_test_new->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели TypeTestType");
                }

                $type_test_new->refresh();

                $post->test->type_tests->{$type_test->type_test_id}->type_test_type_id = $type_test_new->id;
            }

            TestCompanyDepartment::deleteAll(['test_id' => $test_id]);

            foreach ($test->company_departments as $key => $company_department) {
                $company_department_new = new TestCompanyDepartment();
                $company_department_new->test_id = $test_id;
                $company_department_new->company_department_id = $company_department->company_department_id;

                if (!$company_department_new->save()) {
                    $log->addData($company_department_new->errors, '$company_department_new->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели TestCompanyDepartment");
                }

                $company_department_new->refresh();

                $post->test->company_departments->{$key}->test_company_department_id = $company_department_new->id;
            }


            TestMap::deleteAll(['test_id' => $test_id]);

            foreach ($test->maps as $key => $map) {
                $test_map_new = new TestMap();
                $test_map_new->test_id = $test_id;
                $test_map_new->map_id = $map->map_id;
                $test_map_new->number_on_map = $map->number_on_map;

                if (!$test_map_new->save()) {
                    $log->addData($test_map_new->errors, '$test_map_new->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели TestMap");
                }

                $test_map_new->refresh();

                $post->test->maps->{$key}->test_map_id = $test_map_new->id;
            }


            foreach ($test->questions as $key => $test_question) {
                if (property_exists($test_question, 'delete') and $test_question->delete) {
                    TestQuestion::deleteAll(['id' => $test_question->test_question_id]);
                    unset($post->test->questions->{$key});
                } else {
                    $log->addLog("Сохраняю тест");

                    $question_new = Question::find()
                        ->where(['id' => $test_question->question_id])
                        ->one();
                    if (!$question_new) {
                        $question_new = new Question();
                        $question_new->date_time_create = Assistant::GetDateTimeNow();
                    }
                    $question_new->title = $test_question->question_title;
                    $question_new->question = $test_question->question;
                    $question_new->comment = $test_question->comment;

                    if (!$question_new->save()) {
                        $log->addData($question_new->errors, '$question_new->errors', __LINE__);
                        throw new Exception("Ошибка сохранения модели Question");
                    }

                    $question_new->refresh();


                    $question_id = $question_new->id;
                    $post->test->questions->{$key}->question_id = $question_id;


                    $test_question_new = TestQuestion::find()
                        ->where(['id' => $test_question->test_question_id])
//                        ->orWhere(['question_id' => $test_question->question_id, 'test_id' => $test_id])
                        ->one();
                    if (!$test_question_new) {
                        $test_question_new = new TestQuestion();
                    }
                    $test_question_new->test_id = $test_id;
                    $test_question_new->question_id = $question_id;
                    $test_question_new->actual_status = $test_question->actual_status;
                    $test_question_new->date_time_create = Assistant::GetDateTimeNow();

                    if (!$test_question_new->save()) {
                        $log->addData($test_question_new->errors, '$test_question_new->errors', __LINE__);
                        throw new Exception("Ошибка сохранения модели TestQuestion");
                    }

                    $test_question_new->refresh();

                    $post->test->questions->{$key}->test_question_id = $test_question_new->id;
                    $test_question_id = $test_question_new->id;
                    $post->test->questions->{$key}->date_time_create = $test_question_new->date_time_create;

                    foreach ($test_question->answers as $key_answ => $test_answer) {
                        if (property_exists($test_answer, 'delete') and $test_answer->delete) {
                            TestQuestionAnswer::deleteAll(['id' => $test_answer->test_question_answer_id]);
                            unset($post->test->questions->{$key}->answers->{$key_answ});
                        } else {

                            $answer_new = QuestionAnswer::find()
                                ->Where(['question_id' => $question_id, 'title' => $test_answer->answer_title])
                                ->one();
                            if (!$answer_new) {
                                $answer_new = new QuestionAnswer();
                            }
                            $answer_new->question_id = $question_id;
                            $answer_new->title = $test_answer->answer_title;
                            $answer_new->count_mark = $test_answer->count_mark;
                            $answer_new->flag_true = $test_answer->flag_true;
                            $answer_new->number_in_order = $test_answer->number_in_order;
                            $answer_new->actual_status = $test_answer->actual_status;

                            if (!$answer_new->save()) {
                                $log->addData($answer_new->errors, '$answer_new->errors', __LINE__);
                                throw new Exception("Ошибка сохранения модели QuestionAnswer");
                            }


                            $test_answer_new = TestQuestionAnswer::find()
                                ->where(['id' => $test_answer->test_question_answer_id])
                                ->orWhere(['test_question_id' => $test_question_id, 'title' => $test_answer->answer_title])
                                ->one();
                            if (!$test_answer_new) {
                                $test_answer_new = new TestQuestionAnswer();
                            }
                            $test_answer_new->test_question_id = $test_question_id;
                            $test_answer_new->title = $test_answer->answer_title;
                            $test_answer_new->count_mark = $test_answer->count_mark;
                            $test_answer_new->flag_true = $test_answer->flag_true;
                            $test_answer_new->number_in_order = $test_answer->number_in_order;
                            $test_answer_new->actual_status = $test_answer->actual_status;

                            if (!$test_answer_new->save()) {
                                $log->addData($test_answer_new->errors, '$test_answer_new->errors', __LINE__);
                                throw new Exception("Ошибка сохранения модели TestQuestionAnswer");
                            }

                            $test_answer_new->refresh();

                            $post->test->questions->{$key}->answers->{$key_answ}->test_question_answer_id = $test_answer_new->id;
                        }
                    }
                }
            }


            $result = $post;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * PersonalListTest() - Метод получения персонального списка тестов
     * Входные параметры:
     *      worker_id   - ключ работника
     * выходные данные:
     * tests:       - список тестов, доступных работнику
     *  {test_id}       - ключ теста
     *      test_id         - ключ теста
     *      kind_test_id    - ключ вида теста
     *      kind_test_title - название вида теста
     *      title           - название теста
     *      test_id         - ключ теста
     *      actual_status   - статус теста
     *      date_time_create- дата создания теста
     *      count_mark_sum  - общее количество баллов
     *      count_actual_questions_sum - общее количество актуальных вопросов
     *      type_tests      - список типов тестов, в которых присутствует данный тест
     *          {type_test_id}          - ключ типа теста
     *              type_test_id            - ключ типа теста
     *              type_test_title         - название типа теста
     * examinations - список пройденных тестов с оценками
     *  {test_id _ type_test_id}    - составной ключ
     *      id                      - ключ экзамена
     *      worker_id               - ключ работника
     *      type_test_id            - ключ типа теста
     *      position_id             - ключ должности работника
     *      test_id                 - ключ теста
     *      date_time               - дата и время создания теста
     *      date_time_start         - дата и время начала теста
     *      date_time_end           - дата и время окончания теста
     *      duration                - продолжительность
     *      status_id               - статус теста
     *      count_mark              - количество баллов
     *      mine_id                 - ключ шахты работника
     *      company_department_id   - ключ подразделения работника
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=PersonalListTest&subscribe=&data={"worker_id":1}
     */
    public static function PersonalListTest($data_post = NULL): array
    {
        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("PersonalListTest");

        try {
            $log->addLog("Начало выполнения метода");


            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'worker_id') ||
                $post->worker_id == ''
            ) {
                $session = Yii::$app->session;
                $worker_id = $session['worker_id'];
            } else {
                $worker_id = $post->worker_id;
            }

            $log->addLog("Получил все входные параметры");

            $worker = Worker::findOne(['id' => $worker_id]);

            if (!$worker) {
                throw new Exception("Нет такого работника в БД");
            }

            $company_department_id = $worker->company_department_id;

            $log->addData($company_department_id, '$company_department_id', __LINE__);
            $log->addData($worker_id, '$worker_id', __LINE__);

            $tests = Test::find()
                ->innerJoinWith('testQuestions.testQuestionAnswers')
                ->innerJoinWith('kindTest')
                ->innerJoinWith('typeTestTypes.typeTest')
                ->innerJoinWith('testCompanyDepartments')
                ->where(['test_company_department.company_department_id' => $company_department_id])
                ->all();

            foreach ($tests as $test) {
                $result['tests'][$test->id] = array(
                    'test_id' => $test->id,
                    'kind_test_id' => $test->kind_test_id,
                    'kind_test_title' => $test->kindTest->title,
                    'title' => $test->title,
                    'actual_status' => $test->actual_status,
                    'date_time_create' => $test->date_time_create,
                    'count_mark_sum' => 0,
                    'count_actual_questions_sum' => 0,
                    'type_tests' => (object)array(),
                );

                foreach ($test->typeTestTypes as $type) {
                    $type_tests[$type->type_test_id] = array(
                        'type_test_id' => $type->type_test_id,
                        'type_test_title' => $type->typeTest->title,
                    );
                }

                foreach ($test->testQuestions as $question) {
                    foreach ($question->testQuestionAnswers as $answer) {
                        if ($answer['flag_true']) {
                            $result['tests'][$test->id]['count_mark_sum'] += $answer['count_mark'];
                        }
                    }
                    if ($question->actual_status == 1) {
                        $result['tests'][$test->id]['count_actual_questions_sum']++;
                    }
                }

                if (isset($type_tests)) {
                    $result['tests'][$test->id]['type_tests'] = $type_tests;
                }

                unset($type_tests);
            }

            $result['examinations'] = Examination::find()
                ->where(['examination.worker_id' => $worker_id])
                ->indexBy(function ($row) {
                    return $row['test_id'] . '_' . $row['type_test_id'];
                })
                ->orderBy(['date_time' => SORT_ASC])
                ->all();


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * GetTestForControlKnowledge() - Метод получения теста для проверки знаний
     * входные данные:
     *      test_id     - ключ вопроса, и если не задан, то будет выбран случайным образом
     *      type_test_id- ключ типа теста
     * выходные данные:
     *      test
     *          test_id             - ключ теста/билета
     *          test_title          - название теста/билета
     *          actual_status       - статус билета (активный/неактивный 1/0)
     *          date_time_create    - дата создания теста/билета
     *
     *          questions           - список вопросов теста
     *              {test_question_id}  - ключ привязки вопроса к тесту
     *                  test_question_id        - ключ привязки вопроса к тесту
     *                  question_id             - ключ вопроса
     *                  question_title          - название вопроса
     *                  question                - сам вопроса
     *                  comment                 - Комментарий при неправильном ответе
     *                  date_time_create        - дата создания вопроса
     *                  count_flag_true         - количество верных ответов
     *                  answers                 - список ответов
     *                      {test_question_answer_id}   - ключ привязки вопроса и ответа
     *                          test_question_answer_id     - ключ привязки вопроса и ответа
     *                          answer_title                - сам ответ
     *                          count_mark                  - количество баллов
     *                          number_in_order             - номер по порядку
     *                  medias                              - список медиа
     *                      {question_media_id}                 - ключ привязки вопроса и медиа
     *                          attachment_path                     - путь вложения
     *                          attachment_type                     - тип вложения(.mp4)
     *                          attachment_title                    - название вложения
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=GetTestForControlKnowledge&subscribe=&data={"test_id":15,"type_test_id":1}
     */
    public static function GetTestForControlKnowledge($data_post = NULL): array
    {
        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("GetTestForControlKnowledge");

        try {
            $log->addLog("Начало выполнения метода");

            $session = Yii::$app->session;
            $company_department_id = $session['userCompanyDepartmentId'];
            $worker_id = $session['worker_id'];
            $position_id = $session['position_id'];
            $userMineId = $session['userMineId'];

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'test_id') ||
                !property_exists($post, 'type_test_id')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            if ($post->test_id != '') {
                $test_id = $post->test_id;
            } else {
                if (
                    $post->type_test_id == ''
                ) {
                    throw new Exception("Обязательные параметры не переданы");
                }
                $type_test_id = $post->type_test_id;
                $date = Assistant::GetDateNow();

                $filter = array(
                    'type_test_type.type_test_id' => $type_test_id,
                    'test.actual_status' => 1,
                    'test_question.actual_status' => 1,
                    'test_question_answer.actual_status' => 1,
                );

                $worker = Worker::find()
                    ->innerJoinWith('workerObjects.role')
                    ->where([
                        'worker.id' => $worker_id,
                        'role.type' => 2
                    ])
                    ->all();
                if ($worker) {
                    $filter['test.kind_test_id'] = 742;
                }
                $log->addData($filter, '$filter', __LINE__);

                /**ВНЕПЛАНОВЫЕ*/
                $type_test_name = 'внеплановый';
                $test = Test::find()
                    ->distinct(['test.id'])
                    ->joinWith('typeTestTypes')
                    ->JoinWith('kindTest.planTrainingKindTests')
                    ->joinWith('planTrainingTests')
                    ->innerJoin('plan_training', 'plan_training.id = plan_training_test.plan_training_id or plan_training.id = plan_training_kind_test.plan_training_id')
                    ->joinWith('testQuestions.testQuestionAnswers')
                    ->where([
                        'plan_training.kind_plan_training_id' => 2,
                        'plan_training.date' => $date
                    ])
                    ->orWhere([
                        'plan_training.company_department_id' => $company_department_id,
                        'plan_training.position_id' => $position_id,
                        'plan_training.mine_id' => $userMineId,
                        'plan_training.worker_id' => $worker_id
                    ])
                    ->andWhere($filter)
                    ->column();

                if (!$test) {
                    /**ТИПОВЫЕ ОШИБКИ*/
                    $type_test_name = 'типовой';
                    $upper_company_departments = (new Query())
                        ->select(['upper_c_d.id as upper_c_d_id'])
                        ->from('company_department')
                        ->leftJoin('company', 'company.id = company_department.company_id')
                        ->leftJoin('company_department as upper_c_d', 'upper_c_d.company_id = company.upper_company_id')
                        ->where(['company_department.id' => $company_department_id]);

                    $injunctions = Injunction::find()
                        ->select(['test.id'])
                        ->distinct(['test.id'])
                        ->innerJoinWith('injunctionViolations.paragraphPb.questionParagraphPbs.question.tests')
                        ->joinWith('injunctionViolations.violators')
                        ->where([
                            'injunction.status_id' => [
                                StatusEnumController::PRODUCTION_CONTROL_NEW,
                                StatusEnumController::PRODUCTION_CONTROL_IN_WORK
                            ]])
                        ->andWhere([
                            'or',
                            ['and',
                                ['injunction.kind_document_id' => 2],
                                ['violator.worker_id' => $worker_id]
                            ],
                            ['and',
                                ['injunction.kind_document_id' => 1],
                                ['or',
                                    ['injunction.company_department_id' => $upper_company_departments],
                                    ['injunction.company_department_id' => $company_department_id]
                                ]
                            ]
                        ])/*->column()*/
                    ;
//                $log->addData($injunctions, '$injunctions', __LINE__);

                    $test = Test::find()
                        ->distinct(['test.id'])
                        ->joinWith('typeTestTypes')
                        ->JoinWith('kindTest.planTrainingKindTests')
                        ->joinWith('planTrainingTests')
                        ->innerJoin('plan_training', 'plan_training.id = plan_training_test.plan_training_id or plan_training.id = plan_training_kind_test.plan_training_id')
                        ->joinWith('testQuestions.testQuestionAnswers')
                        ->where([
                            'plan_training.kind_plan_training_id' => 1,
                            'test.id' => $injunctions
                        ])
                        ->andWhere([
                            'or',
                            ['and',
                                ['plan_training.worker_id' => $session['worker_id']],
                                ['plan_training.position_id' => $position_id]
                            ],
                            ['and',
                                ['plan_training.worker_id' => null],
                                ['plan_training.position_id' => null],
                                ['plan_training.company_department_id' => $company_department_id]
                            ],
                            ['and',
                                ['plan_training.worker_id' => null],
                                ['plan_training.position_id' => null],
                                ['plan_training.mine_id' => $userMineId]
                            ]
                        ])
                        ->andWhere($filter)
                        ->column();
                }

                if (!$test) {
                    /**НАЗНАЧЕНЫЕ В РЕДАКТОРЕ ТЕСТОВ*/
                    $type_test_name = 'случайный';
                    $test = Test::find()
                        ->distinct(['test.id'])
                        ->joinWith('typeTestTypes')
                        ->innerJoinWith('testCompanyDepartments')
                        ->joinWith('testQuestions.testQuestionAnswers')
                        ->where(['test_company_department.company_department_id' => $company_department_id,])
                        ->andWhere($filter)
                        ->column();
                }

                if (!$test) {
                    throw new Exception("Нет назначенных тестов");
                }
                $log->addLog("Получил $type_test_name тест");

                $test_id = $test[array_rand($test, 1)];
                $log->addData($test, '$test', __LINE__);
                $log->addData($test_id, '$test_id', __LINE__);
            }

            $log->addLog("Получил все входные параметры");


            $test = Test::find()
                ->joinWith('testQuestions.question.questionMedia.media.attachment')
                ->joinWith('testQuestions.testQuestionAnswers')
                ->where([
                    'test.id' => $test_id,
                    'test_question.actual_status' => 1,
                    'test_question_answer.actual_status' => 1
                ])
                ->one();

            if (!$test) {
                throw new Exception("Запрашиваемого теста не существует");
            }

            $result['test'] = array(
                'test_id' => $test->id,
                'test_title' => $test->title,
                'actual_status' => $test->actual_status,
                'date_time_create' => $test->date_time_create,
                'questions' => [],
            );


            foreach ($test->testQuestions as $test_question) {                                                          // Заполняем привязку теста/билета к конкретным вопросам

                if (isset($test_question->question->questionMedia)) {
                    foreach ($test_question->question->questionMedia as $question_media) {
                        $medias[$question_media->id] = array(
                            'attachment_path' => $question_media->media->attachment->path,
                            'attachment_type' => $question_media->media->attachment->attachment_type,
                            'attachment_title' => $question_media->media->attachment->title
                        );
                    }
                }
                if (!isset($medias)) {
                    $medias = (object)array();
                }

                $test_questions[$test_question->id] = array(
                    'test_question_id' => $test_question->id,
                    'question_id' => $test_question->question_id,
                    'question_title' => $test_question->question->title,
                    'question' => $test_question->question->question,
                    'comment' => $test_question->question->comment,
                    'actual_status' => $test_question->actual_status,
                    'date_time_create' => $test_question->date_time_create,
                    'count_flag_true' => 0,
                    'answers' => (object)array(),
                    'medias' => $medias
                );
                unset($medias);

                $count_flag_true = 0;

                foreach ($test_question->testQuestionAnswers as $test_question_answer) {                                // Заполняем привязку вопроса к конкретным ответам
                    $answers[$test_question_answer->id] = array(
                        'test_question_answer_id' => $test_question_answer->id,
                        'answer_title' => $test_question_answer->title,
                        'count_mark' => $test_question_answer->count_mark,
                        'flag_true' => $test_question_answer->flag_true,
                        'number_in_order' => $test_question_answer->number_in_order,
                        'actual_status' => $test_question_answer->actual_status,
                    );
                    if ($test_question_answer->flag_true == 1) {
                        $count_flag_true++;
                    }
                }

                $test_questions[$test_question->id]['count_flag_true'] = $count_flag_true;

                if (isset($answers)) {
                    $test_questions[$test_question->id]['answers'] = $answers;
                }

                unset($answers);
            }

            if (isset($test_questions)) {
                $result['test']['questions'] = $test_questions;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * SaveAnswerFromControlKnowledge() - Метод сохранения ответа работника при проверке знаний
     * входные данные:
     * worker_id        - ключ работника
     * examination      - экзамен
     *      examination_id              - ключ экзамена
     *      type_test_id                - ключ типа экзамена
     *      test_id                     - ключ теста
     *      status_id                   - ключ статуса экзамена
     *      mine_id                     - ключ шахты
     *      duration                    - продолжительность
     *      count_true_question         - количество верно отвеченных вопросов
     *      count_mark                  - количество баллов
     *      count_true_answer           - количество верных ответов
     *      count_answer                - количество ответов
     * answers           - ответы работника
     *     test_question_id             - ключ вопроса
     *     test_question_answer_id      - ключ ответа на вопрос
     *     flag_answer                  - ответ пользователя
     *     true_test_question_answer_id - ключ правильного ответа
     * @example 127.0.0.1/read-manager-amicum?controller=Exam&method=SaveAnswerFromControlKnowledge&subscribe=&data={"answers":[{"test_question_answer_id":7,"flag_answer":1},{"test_question_answer_id":8,"flag_answer":1}],"worker_id":1,"examination":{"examination_id":8,"count_true_answer":1,"type_test_id":1,"test_id":4,"status_id":130,"mine_id":1,"date_time_end":"2020-02-02"}}
     */
    public static function SaveAnswerFromControlKnowledge($data_post = NULL): array
    {
        $result = null;

        $log = new LogAmicumFront("SaveAnswerFromControlKnowledge");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'answers') ||
                !property_exists($post, 'examination')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            if (
                !property_exists($post, 'worker_id') ||
                $post->worker_id == ''
            ) {
                $session = Yii::$app->session;
                $worker_id = $session['worker_id'];
            } else {
                $worker_id = $post->worker_id;
            }

            $worker = Worker::findOne(['id' => $worker_id]);

            $answers = $post->answers;
            $examination = $post->examination;

            if (!property_exists($post->examination, "count_true_answer")) {
                $post->examination->count_true_answer = 0;
            }

            if (!property_exists($post->examination, "count_answer")) {
                $post->examination->count_answer = 0;
            }

            if (!property_exists($post->examination, "count_true_question")) {
                $post->examination->count_true_question = 0;
            }

            $exam_new = Examination::findOne(['id' => $examination->examination_id]);

            if (!$exam_new) {
                $exam_new = new Examination();

                $exam_new->worker_id = $worker_id;
                $exam_new->position_id = $worker->position_id;
                $exam_new->company_department_id = $worker->company_department_id;

                $exam_new->type_test_id = $examination->type_test_id;
                $exam_new->test_id = $examination->test_id;
                $exam_new->mine_id = $examination->mine_id;

                $exam_new->date_time = Assistant::GetDateTimeNow();
                $exam_new->date_time_start = Assistant::GetDateTimeNow();

                $exam_new->count_mark = 0;
            }
            $status_id = $examination->status_id;

            $exam_new->status_id = $status_id;
            $exam_new->date_time_end = Assistant::GetDateTimeNow();
            $exam_new->duration = strtotime($exam_new->date_time_end) - strtotime($exam_new->date_time_start);
            if ($exam_new->duration < 0) {
                throw new Exception("duration меньше 0");
            }

            if (!$exam_new->save()) {
                $log->addData($exam_new->errors, '$exam_new->errors', __LINE__);
                throw new Exception("Ошибка сохранения модели Examination");
            }

            $exam_new->refresh();

            $post->examination->examination_id = $exam_new->id;
            $post->examination->date_time_end = $exam_new->date_time_end;
            $post->examination->duration = $exam_new->duration;


            $examination_id = $exam_new->id;
            $count_mark_exam = 0;
            $flag_failed = false;

            foreach ($answers as $answer) {
                if ($answer->flag_answer == 1) {
                    $true_answer = TestQuestionAnswer::findOne(['id' => $answer->test_question_answer_id]);
                    if (!$true_answer) {
                        throw new Exception("Нет такого ответа в списке ответов на вопросы");
                    }
                    $test_questions_id[$true_answer->test_question_id] = $true_answer->test_question_id;
                    $true_answers[$true_answer->id] = $true_answer;
                }
            }

            foreach ($true_answers as $true_answer) {

                $count_mark = $true_answer->count_mark;
                $flag_true = $true_answer->flag_true;

                $answer_new = new ExaminationAnswer();
                $answer_new->examination_id = $examination_id;
                $answer_new->test_question_id = $true_answer->test_question_id;
                $answer_new->test_question_answer_id = $true_answer->id;
                $answer_new->flag_answer = $answer->flag_answer;
                $answer_new->flag_true = $flag_true;
                $answer_new->count_mark = $count_mark;

                if (!$answer_new->save()) {
                    $log->addData($answer_new->errors, '$answer_new->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели ExaminationAnswer");
                }

                $count_mark_exam += $count_mark;
                $post->examination->count_true_answer += $true_answer->flag_true;

                if ($flag_true == 0) {
                    $flag_failed = true;
                }

            }

            $answers = TestQuestionAnswer::find()
                ->innerJoinWith('testQuestion')
                ->where(['test_question_id' => $test_questions_id, 'flag_true' => 1, 'test_question_answer.actual_status' => 1])
                ->all();

            if ($flag_failed) {
                $count_mark_exam = 0;
            } else {
                foreach ($answers as $answer) {
                    if (!isset($true_answers[$answer->id])) {
                        $count_mark_exam = 0;
                        $flag_failed = true;
                    }
                }
            }

            if (!$flag_failed) {
                $post->examination->count_true_question += 1;
            }

            $exam_new->count_mark += $count_mark_exam;

            if ($status_id == StatusEnumController::EXAM_END) {
                $count_mark_sum = TestQuestionAnswer::find()
                    ->select('sum(count_mark)')
                    ->innerJoinWith('testQuestion')
                    ->where(['test_id' => $examination->test_id, 'flag_true' => 1, 'test_question_answer.actual_status' => 1])
                    ->scalar();
                if ($count_mark_sum != 0 and $exam_new->count_mark / $count_mark_sum > 0.7) {
                    $status_id = StatusEnumController::EXAM_DONE;
                } else {
                    $status_id = StatusEnumController::EXAM_NOT_DONE;
                }
            }

            $exam_new->status_id = $status_id;
            $post->examination->status_id = $status_id;

            if (!$exam_new->save()) {
                $log->addData($exam_new->errors, '$exam_new->errors', __LINE__);
                throw new Exception("Ошибка второго сохранения модели Examination");
            }
            $post->examination->count_mark = $exam_new->count_mark;

            if ($examination->type_test_id == TypeTestEnumController::STUDY) {
                foreach ($post->answers as $answer) {
                    $true_test_question_answers[$answer->test_question_answer_id]['test_question_answer_id'] = $answer->test_question_answer_id;
                    $true_test_question_answers[$answer->test_question_answer_id]['flag_answer'] = $answer->flag_answer;
                }

                foreach ($answers as $answer) {
                    $true_test_question_answers[$answer->id]['true_test_question_answer'] = $answer->id;
                }
                $post->answers = $true_test_question_answers;
            }

            $result = $post;
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * CreateControlKnowledge() - Метод создания проверки знаний
     * входные данные:
     *  worker_id        - ключ работника
     *  examination      - экзамен
     *      examination_id              - ключ экзамена
     *      type_test_id                - ключ типа экзамена
     *      test_id                     - ключ теста
     *      mine_id                     - ключ шахты
     *      duration                    - продолжительность
     *      count_mark                  - количество баллов
     *      count_true_answer           - количество верных ответов
     *      count_answer                - количество ответов
     * @example 127.0.0.1/read-manager-amicum?controller=Exam&method=CreateControlKnowledge&subscribe=&data={"worker_id":1,"examination":{"examination_id":8,"count_true_answer":1,"type_test_id":1,"test_id":4,"status_id":130,"mine_id":1,"date_time_end":"2020-02-02"}}
     */
    public static function CreateControlKnowledge($data_post = NULL): array
    {
        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("SaveAnswerFromControlKnowledge");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'examination')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            if (
                !property_exists($post, 'worker_id') ||
                $post->worker_id == ''
            ) {
                $session = Yii::$app->session;
                $worker_id = $session['worker_id'];
            } else {
                $worker_id = $post->worker_id;
            }

            $worker = Worker::findOne(['id' => $worker_id]);

            $examination = $post->examination;

            $exam_new = Examination::findOne(['id' => $examination->examination_id]);

            if (!$exam_new) {
                $exam_new = new Examination();

                $exam_new->worker_id = $worker_id;
                $exam_new->position_id = $worker->position_id;
                $exam_new->company_department_id = $worker->company_department_id;

                $exam_new->type_test_id = $examination->type_test_id;
                $exam_new->test_id = $examination->test_id;
                $exam_new->mine_id = $examination->mine_id;

                $exam_new->date_time = Assistant::GetDateTimeNow();
                $exam_new->date_time_start = Assistant::GetDateTimeNow();

                $exam_new->count_mark = 0;
            }

            $exam_new->status_id = StatusEnumController::EXAM_NOT_START;
            $exam_new->date_time_end = Assistant::GetDateTimeNow();
            $exam_new->duration = strtotime($exam_new->date_time_end) - strtotime($exam_new->date_time_start);


            if (!$exam_new->save()) {
                $log->addData($exam_new->errors, '$exam_new->errors', __LINE__);
                throw new Exception("Ошибка сохранения модели Examination");
            }

            $exam_new->refresh();

            $post->examination->examination_id = $exam_new->id;
            $post->examination->date_time_end = $exam_new->date_time_end;
            $post->examination->duration = $exam_new->duration;

            $result = $post;
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * GetListPersonalExamination() - Метод получения списка экзаменов пройденных пользователем
     * входные данные:
     *      worker_id           - ключ работника
     *      type_tests          - массив ключей типов теста для выборки
     * Выходные данные:
     *      examinations        - список экзаменов пользователя
     *          {examination_id}        - ключ экзамена
     *              examination_id          - ключ экзамена
     *              date_time               - дата прохождения
     *              test_id                 - ключ теста
     *              test_title              - название теста
     *              type_test_id            - ключ типа теста
     *              kind_test_id            - ключ вида теста
     *              kind_test_title         - название вида теста
     *              duration                - продолжительность в секундах
     *              status_id               - ключ статуса теста
     *              status_title            - название статуса теста
     *              question_count          - количество вопросов общее
     *              question_true_count     - количество вопросов с правильными ответами
     *              answers_count           - количество ответов общее
     *              answers_true_count      - количество правильных ответов
     *              count_mark              - количество баллов
     *              count_mark_all          - сколько всего баллов за правильные ответы
     *              attachments                         - список вложений
     *                  {examination_attachment_id}         - ключ привязки экзамена и вложения
     *                      attachment_path                     - путь вложения
     *                      attachment_type                     - тип вложения(.mp4)
     *                      attachment_title                    - название вложения
     *      average_times        - статистика по экзаменам работника
     *          {type_test_id}          - ключ типа теста
     *              type_test_id            - ключ типа теста
     *              avr                     - средняя продолжительность
     *              sum                     - суммарная продолжительность
     *              count                   - количество тестов
     * @example 127.0.0.1/read-manager-amicum?controller=Exam&method=GetListPersonalExamination&subscribe=&data={"worker_id":1,"type_tests":[1,2]}
     */
    public static function GetListPersonalExamination($data_post = NULL): array
    {
        $result = array(
            'examinations' => null,
            'average_times' => null,
        );
        $count_record = 0;

        $log = new LogAmicumFront("GetListPersonalExamination");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'type_tests')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            if (
                !property_exists($post, 'worker_id') ||
                $post->worker_id == ''
            ) {
                $session = Yii::$app->session;
                $worker_id = $session['worker_id'];
            } else {
                $worker_id = $post->worker_id;
            }

            $type_tests = $post->type_tests;

            $list_exam = Examination::find()
                ->innerJoinWith('test.kindTest')
                ->joinWith('examinationAnswers')
                ->joinWith('test.testQuestions.testQuestionAnswers')
                ->joinWith('status')
                ->joinWith('examinationAttachments')
                ->where(['worker_id' => $worker_id])
                ->all();

            foreach ($list_exam as $exam) {
                if (array_search($exam['type_test_id'], $type_tests) !== false) {

                    foreach ($exam['examinationAttachments'] as $examinationAttachment) {
                        $attachments[$examinationAttachment['id']] = array(
                            'attachment_path' => $examinationAttachment['attachment']['path'],
                            'attachment_type' => $examinationAttachment['attachment']['attachment_type'],
                            'attachment_title' => $examinationAttachment['attachment']['title']
                        );
                    }

                    if (!isset($attachments)) {
                        $attachments = (object)array();
                    }

                    $result['examinations'][$exam['id']] = array(
                        'examination_id' => $exam['id'],
                        'duration' => $exam['duration'],
                        'date_time' => $exam['date_time'],
                        'test_id' => $exam['test_id'],
                        'test_title' => $exam['test']['title'],
                        'type_test_id' => $exam['type_test_id'],
                        'kind_test_id' => $exam['test']['kind_test_id'],
                        'kind_test_title' => $exam['test']['kindTest']['title'],
                        'status_id' => $exam['status_id'],
                        'status_title' => $exam['status']['title'],
                        'question_count' => count($exam['test']['testQuestions']),
                        'question_true_count' => 0,
                        'answers_count' => count($exam['examinationAnswers']),
                        'answers_true_count' => 0,
                        'count_mark' => $exam['count_mark'],
                        'count_mark_all' => 0,
                        'attachments' => $attachments
                    );
                    unset($attachments);

                    foreach ($exam['examinationAnswers'] as $answer) {
                        if ($answer['flag_true'] == 1) {
                            $result['examinations'][$exam['id']]['answers_true_count']++;
                        }
                        if ($answer['test_question_id'] > 0) {
                            $questions[$answer['test_question_id']][$answer['test_question_answer_id']] = $answer;
                        }
                    }

                    foreach ($exam['test']['testQuestions'] as $test_question) {

                        if (isset($questions[$test_question['id']])) {
                            $answers = $questions[$test_question['id']];
                        }
                        $flag_question_true = true;

                        foreach ($test_question['testQuestionAnswers'] as $test_q_answer) {
                            if ($test_q_answer['flag_true'] == 1) {
                                $result['examinations'][$exam['id']]['count_mark_all'] += $test_q_answer['count_mark'];
                                if (!isset($answers[$test_q_answer['id']])) {
                                    $flag_question_true = false;
                                }
                            } else {
                                if (isset($answers[$test_q_answer['id']])) {
                                    $flag_question_true = false;
                                }
                            }
                        }

                        if ($flag_question_true) {
                            $result['examinations'][$exam['id']]['question_true_count']++;
                        }
                    }
                    unset($questions);
                }
                if (!isset($result['average_times'][$exam['type_test_id']])) {
                    $result['average_times'][$exam['type_test_id']] = array(
                        'type_test_id' => $exam['type_test_id'],
                        'avr' => 0,
                        'sum' => 0,
                        'count' => 0,
                    );
                }
                $result['average_times'][$exam['type_test_id']]['sum'] += $exam['duration'];
                $result['average_times'][$exam['type_test_id']]['count']++;
                $result['average_times'][$exam['type_test_id']]['avr'] = round($result['average_times'][$exam['type_test_id']]['sum'] / $result['average_times'][$exam['type_test_id']]['count'], 0);

            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * GetPersonalPlace() - Метод получения рейтинга пользователя по шахте и по участку
     * входные данные:
     *      worker_id                   - ключ работника
     *      mine_id                     - ключ шахты
     * Выходные данные:
     *      place_by_company_department - место работника в рамках своего участка
     *      place_by_mine               - место работника в рамках шахты
     *      places                      - рейтинг работников по первым трем местам
     *          []
     *              fio                         - ФИО работника
     *              count_mark                  - количество баллов работника
     * @example 127.0.0.1/read-manager-amicum?controller=Exam&method=GetPersonalPlace&subscribe=&data={"worker_id":1,"mine_id":290}
     */
    public static function GetPersonalPlace($data_post = NULL): array
    {
        $result = array(
            'place_by_company_department' => 0,
            'place_by_mine' => 0,
            'places' => [],
        );
        $count_record = 0;

        $log = new LogAmicumFront("GetPersonalPlace");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'mine_id')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            if (
                !property_exists($post, 'worker_id') ||
                $post->worker_id == ''
            ) {
                $session = Yii::$app->session;
                $worker_id = $session['worker_id'];
            } else {
                $worker_id = $post->worker_id;
            }

            $mine_id = $post->mine_id;

            $worker = Worker::findOne(['id' => $worker_id]);

            if (!$worker) {
                throw new Exception("Работника с переданным ключом не существует");
            }

            $list_place_by_department = Examination::find()
                ->select([
                    'worker_id',
                    'sum(count_mark) as sum_count_mark'
                ])
                ->where(['company_department_id' => $worker->company_department_id, 'examination.status_id' => StatusEnumController::EXAM_DONE])
                ->groupBy('worker_id')
                ->orderBy(['sum_count_mark' => SORT_DESC])
                ->asArray()
                ->all();

            foreach ($list_place_by_department as $key => $place) {
                if ($place['worker_id'] == $worker_id) {
                    $result['place_by_company_department'] = $key + 1;
                }
            }

            $list_place_by_mine = Examination::find()
                ->select([
                    'worker_id',
                    'sum(count_mark) as sum_count_mark'
                ])
                ->where(['mine_id' => $mine_id, 'examination.status_id' => StatusEnumController::EXAM_DONE])
                ->groupBy('worker_id')
                ->orderBy(['sum_count_mark' => SORT_DESC])
                ->asArray()
                ->all();

            foreach ($list_place_by_mine as $key => $place) {
                if ($place['worker_id'] == $worker_id) {
                    $result['place_by_mine'] = $key + 1;
                }

                if ($key < 3) {
                    $worker = Worker::find()
                        ->innerJoinWith('employee')
                        ->where(['worker.id' => $place['worker_id']])
                        ->one();

                    if (!$worker) {
                        throw new Exception("Работника с переданным ключом не существует");
                    }
                    $result['places'][] = array(
                        'fio' => Assistant::GetShortFullName($worker['employee']['first_name'], $worker['employee']['patronymic'], $worker['employee']['last_name']),
                        'count_mark' => $place['sum_count_mark'],
                    );
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * CopyTestToKindTest() - Метод копирования теста в вид теста
     * входные данные:
     *      test_id                     - ключ теста, который копируют
     *      kind_test_id                - ключ вида теста, в который копируют
     * Выходные данные:
     *          test_id             - ключ теста/билета
     *          test_title          - название теста/билета
     *          actual_status       - статус билета (активный/неактивный 1/0)
     *          date_time_create    - дата создания теста/билета
     *          kind_test_id        - ключ вида теста/билета (пром без)
     *          kind_test_title     - название вида теста/билета
     *
     *          maps                - список карт, в которых есть тест/билет
     *              {test_map_id}       - ключ привязки карты и теста
     *                  test_map_id             - ключ привязки карты и теста/билета
     *                  map_id                  - ключ карты
     *                  map_title               - название карты
     *                  map_count_number        - количество номеров на карте
     *                  map_attachment_id       - ключ вложения, в котором лежит карта
     *                  map_attachment_path     - путь до карты
     *                  number_on_map           - номер на карте данного теста/билета
     *
     *          type_tests          - в каких типах теста принимает участие тест/билет
     *              {type_test_id}      - ключ типа теста
     *                  type_test_type_id       - ключ привязки типа теста и самого теста
     *                  type_test_id            - ключ типа теста
     *                  status                  - статус типа теста (выбран/не выбран)
     *
     *          questions           - список вопросов теста
     *              {test_question_id}  - ключ привязки вопроса к тесту
     *                  test_question_id        - ключ привязки вопроса к тесту
     *                  question_id             - ключ вопроса
     *                  question_title          - название вопроса
     *                  question                - сам вопроса
     *                  comment                 - Комментарий при неправильном ответе
     *                  actual_status           - актуальный или нет вопрос в данном тесте
     *                  date_time_create        - дата создания вопроса
     *                  delete                  - статус удаления вопроса
     *                  answers                 - список ответов
     *                      {test_question_answer_id}   - ключ привязки вопроса и ответа
     *                          test_question_answer_id     - ключ привязки вопроса и ответа
     *                          answer_title                - сам ответ
     *                          count_mark                  - количество баллов
     *                          flag_true                   - признак верности (1/0)
     *                          number_in_order             - номер по порядку
     *                          actual_status               - актуальный или нет ответ в данном вопросе
     *                          delete                      - статус удаления ответа
     *
     *          company_departments - список департаментов для кого предназначен тест/билет
     *              {test_company_department_id}    - ключ привязки департамента к тесту
     *                  test_company_department_id      - ключ привязки департамента к тесту
     *                  company_department_id           - ключ конкретного подразделения
     * @example 127.0.0.1/read-manager-amicum?controller=Exam&method=CopyTestToKindTest&subscribe=&data={"test_id":6,"kind_test_id":1}
     */
    public static function CopyTestToKindTest($data_post = NULL): array
    {
        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("CopyTestToKindTest");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'test_id') ||
                !property_exists($post, 'kind_test_id')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            $test_id = $post->test_id;
            $kind_test_id = $post->kind_test_id;

            $response = self::GetTest(json_encode(array('test_id' => $test_id)));
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения копируемого теста');
            }
            $find_test = $response['Items'];

            $find_test['test']['test_id'] = -1;
            $find_test['test']['kind_test_id'] = $kind_test_id;
            $find_test['test']['kind_test_title'] = KindTest::findOne(['id' => $kind_test_id])->title;
            $i = -1;
            foreach ($find_test['test']['questions'] as $test_question_id => $question) {
                $find_test['test']['questions'][$test_question_id]['test_question_id'] = $i--;
                foreach ($question['answers'] as $test_question_answer_id => $answers) {
                    $find_test['test']['questions'][$test_question_id]['answers'][$test_question_answer_id]['test_question_answer_id'] = $i--;

                }
            }

            $response = self::SaveTest(json_encode($find_test));
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения копируемого теста');
            }

            $result = $response['Items']->test;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * AddTestToTest() - Метод добавления теста в тест
     * входные данные:
     *      test_id_from                - ключ теста, из которого добавляем
     *      test_id_to                  - ключ теста, в который добавляем
     * Выходные данные:
     *          test_id             - ключ теста/билета
     *          test_title          - название теста/билета
     *          actual_status       - статус билета (активный/неактивный 1/0)
     *          date_time_create    - дата создания теста/билета
     *          kind_test_id        - ключ вида теста/билета (пром без)
     *          kind_test_title     - название вида теста/билета
     *
     *          maps                - список карт, в которых есть тест/билет
     *              {test_map_id}       - ключ привязки карты и теста
     *                  test_map_id             - ключ привязки карты и теста/билета
     *                  map_id                  - ключ карты
     *                  map_title               - название карты
     *                  map_count_number        - количество номеров на карте
     *                  map_attachment_id       - ключ вложения, в котором лежит карта
     *                  map_attachment_path     - путь до карты
     *                  number_on_map           - номер на карте данного теста/билета
     *
     *          type_tests          - в каких типах теста принимает участие тест/билет
     *              {type_test_id}      - ключ типа теста
     *                  type_test_type_id       - ключ привязки типа теста и самого теста
     *                  type_test_id            - ключ типа теста
     *                  status                  - статус типа теста (выбран/не выбран)
     *
     *          questions           - список вопросов теста
     *              {test_question_id}  - ключ привязки вопроса к тесту
     *                  test_question_id        - ключ привязки вопроса к тесту
     *                  question_id             - ключ вопроса
     *                  question_title          - название вопроса
     *                  question                - сам вопроса
     *                  comment                 - Комментарий при неправильном ответе
     *                  actual_status           - актуальный или нет вопрос в данном тесте
     *                  date_time_create        - дата создания вопроса
     *                  delete                  - статус удаления вопроса
     *                  answers                 - список ответов
     *                      {test_question_answer_id}   - ключ привязки вопроса и ответа
     *                          test_question_answer_id     - ключ привязки вопроса и ответа
     *                          answer_title                - сам ответ
     *                          count_mark                  - количество баллов
     *                          flag_true                   - признак верности (1/0)
     *                          number_in_order             - номер по порядку
     *                          actual_status               - актуальный или нет ответ в данном вопросе
     *                          delete                      - статус удаления ответа
     *
     *          company_departments - список департаментов для кого предназначен тест/билет
     *              {test_company_department_id}    - ключ привязки департамента к тесту
     *                  test_company_department_id      - ключ привязки департамента к тесту
     *                  company_department_id           - ключ конкретного подразделения
     * @example 127.0.0.1/read-manager-amicum?controller=Exam&method=AddTestToTest&subscribe=&data={"test_id_from":6,"test_id_to":93}
     */
    public static function AddTestToTest($data_post = NULL): array
    {
        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("AddTestToTest");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'test_id_from') ||
                !property_exists($post, 'test_id_to')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            $test_id_to = $post->test_id_to;
            $test_id_from = $post->test_id_from;

            $response = self::GetTest(json_encode(array('test_id' => $test_id_from)));
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения копируемого теста');
            }
            $test_from = $response['Items'];

            $response = self::GetTest(json_encode(array('test_id' => $test_id_to)));
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения теста назначения');
            }
            $test_to = $response['Items'];

            $i = -1;
            foreach ($test_from['test']['questions'] as $test_question_id => $question_from) {
                foreach ($question_from['answers'] as $test_question_answer_id => $answers) {
                    $test_from['test']['questions'][$test_question_id]['answers'][$test_question_answer_id]['test_question_answer_id'] = $i--;
                }

                $is_exists = false;
                foreach ($test_to['test']['questions'] as $question_to) {
                    if ($question_from['question_id'] == $question_to['question_id']) {
                        $is_exists = true;
                    }
                }

                if (!$is_exists) {
                    $test_from['test']['questions'][$test_question_id]['test_question_id'] = $i;
                    if (is_object($test_to['test']['questions'])) {
                        $test_to['test']['questions']->{$i--} = $test_from['test']['questions'][$test_question_id];
                    } else {
                        $test_to['test']['questions'][$i--] = $test_from['test']['questions'][$test_question_id];
                    }
                }
            }

            $response = self::SaveTest(json_encode($test_to));
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения копируемого теста');
            }

            $result = $response['Items']->test;


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * GetPersonalStatistic() - Метод получения персональной статистики в экзаменаторе
     *  - проходил или нет предсменный экзаменатор
     *  - есть или нет предсменный инструктаж
     *  - количество выполненных тестов из общего объема назначенных
     *  - остаток действия аттестации ПБ (для ИТР и для Рабочих)
     * Входные данные:
     *      нет
     * Выходные данные:
     *      pred_exam_status            - статус предсменного экзамена (true/ false/ null)
     *
     *      instruction_status          - статус наличия предсменного инструктажа (true/ false/ null)
     *
     *      count_test_all              - количество тестов назначенных для прохождения
     *      count_test_done             - количество успешно сданных тестов
     *
     *      count_attestation_day_keep  - оставшееся количество дней до следующей аттестации
     *      count_attestation_day_all   - полное количество дней до следующей аттестации
     * @example 127.0.0.1/read-manager-amicum?controller=Exam&method=GetPersonalStatistic&subscribe=&data={}
     */
    public static function GetPersonalStatistic($data_post = NULL): array
    {
        $result = array(
            'pred_exam_status' => null,
            'instruction_status' => null,
            'count_test_all' => 0,
            'count_test_done' => 0,
            'count_attestation_day_keep' => 0,
            'count_attestation_day_all' => 1825,
        );
        $count_record = 0;

        $log = new LogAmicumFront("GetPersonalStatistic");

        try {
            $log->addLog("Начало выполнения метода");

            $session = Yii::$app->session;

            $worker_id = $session['worker_id'];
            $company_department_id = $session['userCompanyDepartmentId'];

            $response = Assistant::GetShiftByDateTime();
            $date_work = $response['date_work'];
            $shift_id = $response['shift_id'];
            $shift_id_next = $response['shift_id_next'];
            $date_work_next = $response['date_work_next'];

            $response = Assistant::GetDateTimeByShift($date_work, $shift_id);
            $date_time_start = $response['date_time_start'];
            $date_time_end = $response['date_time_end'];

            /** Статус предсменного экзаменатора */
            $find_exam = (new Query())
                ->select(['status_id', 'max(date_time_end) as max_date_time_end'])
                ->from('examination')
                ->where(['>', 'date_time_end', $date_time_start])
                ->andWhere(['<', 'date_time_end', $date_time_end])
                ->andWhere(['worker_id' => $worker_id])
                ->andWhere(['type_test_id' => TypeTestEnumController::PRED_SHIFT_EXAM])
                ->andWhere(['or',
                        ['status_id' => StatusEnumController::EXAM_DONE],
                        ['status_id' => StatusEnumController::EXAM_NOT_DONE]
                    ]
                )
                ->groupBy('status_id')
                ->indexBy('status_id')
                ->all();

            if (
                $find_exam and
                isset($find_exam[StatusEnumController::EXAM_DONE]) and
                isset($find_exam[StatusEnumController::EXAM_NOT_DONE])
            ) {
                if (strtotime($find_exam[StatusEnumController::EXAM_DONE]['max_date_time_end']) > strtotime($find_exam[StatusEnumController::EXAM_NOT_DONE]['max_date_time_end'])) {
                    $result['pred_exam_status'] = true;
                } else {
                    $result['pred_exam_status'] = false;
                }
            } else if (
                $find_exam and
                isset($find_exam[StatusEnumController::EXAM_DONE])
            ) {
                $result['pred_exam_status'] = true;
            } else if (
                $find_exam and
                isset($find_exam[StatusEnumController::EXAM_NOT_DONE])
            ) {
                $result['pred_exam_status'] = false;
            }

            /** Статус инструктажа из наряда */
            $instructions = OrderInstructionPb::find()
                ->joinWith('order.orderPlaces.orderOperations.operationWorkers')
                ->where(['shift_id' => $shift_id_next])
                ->where(['!=', 'order.status_id', 50])
                ->andWhere(['date_time_create' => $date_work_next])
                ->andWhere(['worker_id' => $worker_id])
                ->count();


            $result['instruction_status'] = (bool)$instructions;


            /**
             * Количество выполненных тестов из общего объема назначенных
             * - найти общее количество тестов назначенных на человека по подразделению type_test_id = 2
             * - найти количество пройденных тестов успешно
             */
//            $response = HandbookDepartmentController::GetAllParentsCompaniesWithCompany($company_department_id);
//            if ($response['status'] === 0) {
//                $log->addLogAll($response);
//                throw new Exception('Ошибка при получении вышестоящих участков');
//            }
//            $company_department_ids = $response['ids'];
            $company_department_ids = [$company_department_id];

            $all_test = (new Query())
                ->select(['test.id'])
                ->from('test')
                ->innerJoin('test_company_department', 'test_company_department.test_id=test.id')
                ->innerJoin('type_test_type', 'type_test_type.test_id=test.id')
                ->where(['company_department_id' => $company_department_ids])
                ->andWhere(['type_test_id' => TypeTestEnumController::TEST])
                ->andWhere(['actual_status' => 1])
                ->groupBy('test.id')
                ->all();

            $result['count_test_all'] = count($all_test);

            $test_arr = array();
            foreach ($all_test as $test) {
                $test_arr[] = $test['id'];
            }

            $count_done_exam = (new Query())
                ->select(['test_id'])
                ->from('examination')
                ->where(['test_id' => $test_arr])
                ->andWhere(['type_test_id' => TypeTestEnumController::TEST])
                ->andWhere(['status_id' => StatusEnumController::EXAM_DONE])
                ->andWhere(['worker_id' => $worker_id])
                ->groupBy('test_id')
                ->count();

            $result['count_test_done'] = (int)$count_done_exam;

            /** Остаток действия аттестации ПБ (для ИТР и для Рабочих) */
            $response = NotificationController::GetCheckKnowledgeByWorker([$worker_id], $date_work_next, 3);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения проверок знаний по работникам');
            }
            $check_knowledge_workers = $response['check_knowledge_workers'];

            $count_day_keep = 0;
            if (
                isset($check_knowledge_workers[$worker_id]) and
                isset($check_knowledge_workers[$worker_id]['type_check_knowledge'][3])
            ) {
                $count_day_keep = 1825 - round((strtotime(Assistant::GetDateTimeNow()) - strtotime($check_knowledge_workers[$worker_id]['type_check_knowledge'][3]['check_knowledge_date'])) / (60 * 60 * 24), 0);
                $count_day_keep = $count_day_keep > 0 ? $count_day_keep : 0;
            }

            $result['count_attestation_day_keep'] = $count_day_keep;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * GetAnalyticData() - Метод получения статистики предсменного экзаменатора
     * Входные данные:
     *      mines       - массив шахт
     *      companies   - массив подразделений
     *      date_start  - дата начала выборки
     *      date_end    - дата окончания выборки
     *      positions   - массив должностей
     * Выходные данные:
     *      list_personal_exam:     - список успеваемости работников
     *          common
     *              count_passed                    - количество пройденных экзаменов
     *              mark_sum                        - количество баллов
     *              mark_average                    - средний балл
     *              duration_average                - средняя продолжительность
     *          test_types
     *              test_type_id                    - ключ типа теста
     *              count_passed                    - количество пройденных экзаменов
     *              mark_sum                        - количество баллов
     *              mark_average                    - средний балл
     *              duration_average                - средняя продолжительность
     *              worker_exams                    - список экзаменов работников
     *                  {worker_id}                     - ключ работника
     *                      worker_id                       - ключ работника
     *                      full_name                       - ФИО
     *                      position_id                     - ключ должности
     *                      position_title                  - название должности
     *                      company_id                      - ключ подразделения работника
     *                      company_title                   - название подразделения работника
     *                      count_passed                    - количество пройденных экзаменов
     *                      mark_sum                        - количество баллов работника
     *                      mark_average                    - средний балл работника
     *                      duration_average                - средняя продолжительность
     *      summary_exams:          - главная вкладка аналитики
     *          exam_all                    - суммарное количество пройденных экзаменов
     *          exam_by_month               - статистика пройденных экзаменов по типам и месяцам
     *              test_types:                 - по типам тестов
     *                  type_test_id                - тип теста
     *                      year                        - год
     *                          month                       - месяц
     *                              year                        - год
     *                              month                       - месяц
     *                              count_passed                - количество экзаменов по типу теста требуемых для прохождения в месяц
     *                              mark_sum                    - суммарное количество баллов по типу теста в месяц
     *                              mark_average                - средний балл по типу теста в месяц
     *              common                      - общая
     *                  year                        - год
     *                          month                       - месяц
     *                              year                        - год
     *                              month                       - месяц
     *                              count_passed                - количество экзаменов по типу теста требуемых для прохождения в месяц
     *                              mark_sum                    - суммарное количество баллов по типу теста в месяц
     *                              mark_average                - средний балл по типу теста в месяц
     *          exam_by_type                - статистика пройденных экзаменов по типам
     *                  id                          - ключ типа теста
     *                  title                       - название типа теста
     *                  count_passed                - количество пройденных экзаменов по типу теста
     *                  count_need                  - количество экзаменов по типу теста требуемых для прохождения
     *                  mark_sum                    - суммарное количество баллов по типу теста
     *                  mark_average                - средний балл по типу теста
     *          exam_by_mark                - статистика пройденных экзаменов по баллам
     *              common                      - общая
     *                  0                           - меньше 1 балла
     *                  1                           - меньше 2 баллов
     *                  2                           - меньше 3 баллов
     *                  3                           - все остальные случаи
     *              test_types                  - по типам теста
     *                  test_type_id                - ключ типа теста
     *                      0                           - меньше 1 балла
     *                      1                           - меньше 2 баллов
     *                      2                           - меньше 3 баллов
     *                      3                           - все остальные случаи
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=GetAnalyticData&subscribe=&data={%22mines%22:[],%22companies%22:[60002522,20026575],%22date_start%22:%222020-01-01%22,%22date_end%22:%222023-12-31%22,%22positions%22:[]}
     */
    public static function GetAnalyticData($data_post = NULL): array
    {
        $result = array(
            'list_personal_exam' => array(          // список успеваемости работников
                'test_types' => array(),
                'common' => array(
                    'count_passed' => 0,
                    'mark_sum' => 0,
                    'mark_average' => 0,
                    'duration_sum' => 0,
                    'duration_average' => 0,
                ),
            ),
            'summary_exams' => array(               // главная вкладка аналитики
                'exam_all' => 0,                    // суммарное количество пройденных экзаменов
                'exam_by_type' => array(),          // статистика пройденных экзаменов по типам
                'exam_by_mark' => array(
                    'common' => array(              // общая
                        '0' => 0,
                        '1' => 0,
                        '2' => 0,
                        '3' => 0,
                    ),
                    'test_types' => array()         // по типам теста
                ),                                  // статистика пройденных экзаменов по баллам
                'exam_by_month' => array(           // статистика пройденных экзаменов по типам и месяцам
                    'test_types' => array(),        // по типам теста
                    'common' => array(),            // общая
                ),
            ),
        );
        $count_record = 0;

        $log = new LogAmicumFront("GetAnalyticData");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'mines') ||
                !property_exists($post, 'companies') ||
                !property_exists($post, 'positions') ||
                !property_exists($post, 'date_start') ||
                !property_exists($post, 'date_end')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            $mines = $post->mines;
            $companies = $post->companies;
            $positions = $post->positions;
            $date_start = $post->date_start;
            $date_end = $post->date_end;

            /** ИНИЦИАЛИЗИРУЕМ СТАТИСТИКУ ПО ТИПАМ ЭКЗАМЕНОВ */
            $type_tests = TypeTest::find()->asArray()->all();

            foreach ($type_tests as $type_test) {
                $result['summary_exams']['exam_by_type'][$type_test['id']] = array(
                    'id' => $type_test['id'],
                    'title' => $type_test['title'],
                    'count_passed' => 0,
                    'count_need' => 0,
                    'mark_sum' => 0,
                    'mark_average' => 0,
                );

                $result['list_personal_exam']['test_types'][$type_test['id']] = array(
                    'type_test_id' => $type_test['id'],
                    'count_passed' => 0,
                    'mark_sum' => 0,
                    'mark_average' => 0,
                    'duration_sum' => 0,
                    'duration_average' => 0,
                    'worker_exams' => array()
                );

                $result['summary_exams']['exam_by_mark']['test_types'][$type_test['id']] = array(
                    '0' => 0,
                    '1' => 0,
                    '2' => 0,
                    '3' => 0,
                );

                $result['summary_exams']['exam_by_month']['test_types'][$type_test['id']] = array(
                    'id' => $type_test['id'],
                    'title' => $type_test['title'],
                    'data' => array()
                );

                $month = (int)date('m', strtotime($date_start));
                $year = (int)date('Y', strtotime($date_start));

                $month_end = (int)date('m', strtotime($date_end));
                $year_end = (int)date('Y', strtotime($date_end));

                $flag_exit = false;
                $j = 0;

                for ($i = $month; !$flag_exit; $i++) {

                    $result['summary_exams']['exam_by_month']['test_types'][$type_test['id']]['data'][$year][$i] = array(
                        'year' => $year,
                        'month' => $i,
                        'count_passed' => 0,
                        'mark_sum' => 0,
                        'mark_average' => 0,
                    );

                    $result['summary_exams']['exam_by_month']['common']['data'][$year][$i] = array(
                        'year' => $year,
                        'month' => $i,
                        'count_passed' => 0,
                        'mark_sum' => 0,
                        'mark_average' => 0,
                    );

                    if ($year == $year_end and $i == $month_end) {
                        $flag_exit = true;
                    }

                    if ($i == 12) {
                        $year++;
                        $i = 0;
                    }

                    if ($j == 1000) {
                        $log->addData($flag_exit, '$flag_exit', __LINE__);
                        $log->addData($i, '$i', __LINE__);
                        $log->addData($year, '$year', __LINE__);
                        $log->addData($month, '$month', __LINE__);
                        $log->addData($year_end, '$year_end', __LINE__);
                        $log->addData($month_end, '$month_end', __LINE__);
                        throw new Exception($i . "Стоп" . $month);
                    }
                    $j++;
                }
            }

            /** ИНИЦИАЛИЗИРУЕМ СТАТИСТИКУ ПО МЕСЯЦАМ ЭКЗАМЕНОВ */

            $exams = Examination::find()
                ->joinWith('companyDepartment.company')
                ->joinWith('worker.employee')
                ->joinWith('position')
                ->where(['<=', 'examination.date_time', $date_end])
                ->andWhere(['>=', 'examination.date_time', $date_start])
                ->andWhere([
                    'or',
                    ['status_id' => StatusEnumController::EXAM_DONE],
                    ['status_id' => StatusEnumController::EXAM_NOT_DONE],
                ])
                ->andFilterWhere([
                    'examination.mine_id' => $mines,
                    'examination.company_department_id' => $companies,
                    'examination.position_id' => $positions,
                ])
                ->asArray()
                ->all();

//            $log->addData($exams, '$exams', __LINE__);

            foreach ($exams as $exam) {
                /** Суммарное количество пройденных экзаменов */
                $result['summary_exams']['exam_all']++;

                /** Количество пройденных экзаменов по типу теста */
                $result['summary_exams']['exam_by_type'][$exam['type_test_id']]['count_passed']++;

                /** Средний балл по типу теста */
                $result['summary_exams']['exam_by_type'][$exam['type_test_id']]['mark_sum'] += $exam['count_mark'];
                $result['summary_exams']['exam_by_type'][$exam['type_test_id']]['mark_average'] = $result['summary_exams']['exam_by_type'][$exam['type_test_id']]['mark_sum'] / $result['summary_exams']['exam_by_type'][$exam['type_test_id']]['count_passed'];

                /** Статистика пройденных экзаменов по баллам */
                if ($exam['count_mark'] < 1) {
                    $result['summary_exams']['exam_by_mark']['common'][0]++;
                    $result['summary_exams']['exam_by_mark']['test_types'][$exam['type_test_id']][0]++;
                } else if ($exam['count_mark'] < 2) {
                    $result['summary_exams']['exam_by_mark']['common'][1]++;
                    $result['summary_exams']['exam_by_mark']['test_types'][$exam['type_test_id']][1]++;
                } else if ($exam['count_mark'] < 3) {
                    $result['summary_exams']['exam_by_mark']['common'][2]++;
                    $result['summary_exams']['exam_by_mark']['test_types'][$exam['type_test_id']][2]++;
                } else {
                    $result['summary_exams']['exam_by_mark']['common'][3]++;
                    $result['summary_exams']['exam_by_mark']['test_types'][$exam['type_test_id']][3]++;
                }

                /** Статистика пройденных экзаменов по типам и месяцам */
                $month = (int)date('m', strtotime($exam['date_time']));
                $year = (int)date('Y', strtotime($exam['date_time']));
                $result['summary_exams']['exam_by_month']['test_types'][$exam['type_test_id']]['data'][$year][$month]['count_passed']++;
                $result['summary_exams']['exam_by_month']['test_types'][$exam['type_test_id']]['data'][$year][$month]['mark_sum'] += $exam['count_mark'];
                $result['summary_exams']['exam_by_month']['test_types'][$exam['type_test_id']]['data'][$year][$month]['mark_average'] = $result['summary_exams']['exam_by_month']['test_types'][$exam['type_test_id']]['data'][$year][$month]['mark_sum'] / $result['summary_exams']['exam_by_month']['test_types'][$exam['type_test_id']]['data'][$year][$month]['count_passed'];

                $result['summary_exams']['exam_by_month']['common']['data'][$year][$month]['count_passed']++;
                $result['summary_exams']['exam_by_month']['common']['data'][$year][$month]['mark_sum'] += $exam['count_mark'];
                $result['summary_exams']['exam_by_month']['common']['data'][$year][$month]['mark_average'] = $result['summary_exams']['exam_by_month']['common']['data'][$year][$month]['mark_sum'] / $result['summary_exams']['exam_by_month']['common']['data'][$year][$month]['count_passed'];

                /** Список успеваемости работников
                 *              worker_id                       - ключ работника
                 *              full_name                       - ФИО
                 *              position_id                     - ключ должности
                 *              position_title                  - название должности
                 *              company_id                      - ключ подразделения работника
                 *              company_title                   - название подразделения работника
                 *              mark_sum                        - количество баллов работника
                 *              mark_average                    - средний балл работника
                 *              duration_average                - средняя продолжительность
                 */
                if (!isset($result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']])) {

                    $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']] = array(
                        'worker_id' => $exam['worker_id'],
                        'full_name' => Assistant::GetFullName($exam['worker']['employee']['first_name'], $exam['worker']['employee']['patronymic'], $exam['worker']['employee']['last_name']),
                        'position_id' => $exam['position_id'],
                        'position_title' => $exam['position']['title'],
                        'company_id' => $exam['company_department_id'],
                        'company_title' => $exam['companyDepartment']['company']['title'],
                        'count_passed' => 0,
                        'mark_sum' => 0,
                        'mark_average' => 0,
                        'duration_sum' => 0,
                        'duration_average' => 0,
                    );
                }

                $result['list_personal_exam']['common']['count_passed']++;
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['count_passed']++;
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['count_passed']++;

                $result['list_personal_exam']['common']['mark_sum'] += $exam['count_mark'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['mark_sum'] += $exam['count_mark'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['mark_sum'] += $exam['count_mark'];

                $result['list_personal_exam']['common']['mark_average'] = $result['list_personal_exam']['common']['mark_sum'] / $result['list_personal_exam']['common']['count_passed'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['mark_average'] = $result['list_personal_exam']['test_types'][$exam['type_test_id']]['mark_sum'] / $result['list_personal_exam']['test_types'][$exam['type_test_id']]['count_passed'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['mark_average'] = $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['mark_sum'] / $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['count_passed'];

                $result['list_personal_exam']['common']['duration_sum'] += $exam['duration'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['duration_sum'] += $exam['duration'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['duration_sum'] += $exam['duration'];

                $result['list_personal_exam']['common']['duration_average'] = $result['list_personal_exam']['common']['duration_sum'] / $result['list_personal_exam']['common']['count_passed'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['duration_average'] = $result['list_personal_exam']['test_types'][$exam['type_test_id']]['duration_sum'] / $result['list_personal_exam']['test_types'][$exam['type_test_id']]['count_passed'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['duration_average'] = $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['duration_sum'] / $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['count_passed'];

            }

            /** Количество экзаменов по типу теста требуемых для прохождения */
            $all_need_tests = (new Query())
                ->select(['count(test.id) as count_test', 'type_test_id'])
                ->from('test')
                ->innerJoin('test_company_department', 'test_company_department.test_id=test.id')
                ->innerJoin('type_test_type', 'type_test_type.test_id=test.id')
                ->where(['company_department_id' => $companies])
                ->andWhere(['actual_status' => 1])
                ->groupBy('type_test_id')
                ->all();

            foreach ($all_need_tests as $need_test) {
                $result['summary_exams']['exam_by_type'][$need_test['type_test_id']]['count_need'] = $need_test['count_test'];
            }

            $data = array();
            foreach ($result['summary_exams']['exam_by_month']['common']['data'] as $datum) {
                foreach ($datum as $item) {
                    if ($item['count_passed'] != 0) {
                        $data[$item['year']][$item['month']] = $item;
                    }
                }
            }
            $result['summary_exams']['exam_by_month']['common']['data'] = $data;

            $test_types = array();
            foreach ($result['summary_exams']['exam_by_month']['test_types'] as $test_type) {
                foreach ($test_type['data'] as $datum) {
                    foreach ($datum as $item) {
                        if ($item['count_passed'] != 0) {
                            $test_types[$test_type['id']]['data'][$item['year']][$item['month']] = $item;
                        }
                    }
                }
            }
            $result['summary_exams']['exam_by_month']['test_types'] = $test_types;


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * GetPersonalAnalyticData() - Метод получения персональной статистики предсменного экзаменатора
     * Входные данные:
     *      worker_id   - ключ работника
     *      mines       - массив шахт
     *      companies   - массив подразделений
     *      date_start  - дата начала выборки
     *      date_end    - дата окончания выборки
     *      positions   - массив должностей
     * Выходные данные:
     *      personal_info:          - персональные сведения
     *          worker_id                       - ключ работника
     *          full_name                       - ФИО
     *          position_id                     - ключ должности
     *          position_title                  - название должности
     *          company_id                      - ключ подразделения работника
     *          company_title                - название подразделения работника
     *          birthday                        - дата рождения
     *          date_work_start                 - дата трудоустройства
     *          phone                           - номер телефона
     *          photo_src                       - путь к фотографии работника
     *      list_personal_exam:     - список успеваемости работников
     *          common
     *              count_passed                    - количество пройденных экзаменов
     *              mark_sum                        - количество баллов
     *              mark_average                    - средний балл
     *              duration_average                - средняя продолжительность
     *          test_types
     *              test_type_id                    - ключ типа теста
     *              count_passed                    - количество пройденных экзаменов
     *              mark_sum                        - количество баллов
     *              mark_average                    - средний балл
     *              duration_average                - средняя продолжительность
     *      worker_exams                    - список экзаменов работников
     *          examinations        - список экзаменов пользователя
     *              {examination_id}        - ключ экзамена
     *                  examination_id          - ключ экзамена
     *                  date_time               - дата прохождения
     *                  test_id                 - ключ теста
     *                  test_title              - название теста
     *                  type_test_id            - ключ типа теста
     *                  kind_test_id            - ключ вида теста
     *                  kind_test_title         - название вида теста
     *                  duration                - продолжительность в секундах
     *                  status_id               - ключ статуса теста
     *                  status_title            - название статуса теста
     *                  question_count          - количество вопросов общее
     *                  question_true_count     - количество вопросов с правильными ответами
     *                  answers_count           - количество ответов общее
     *                  answers_true_count      - количество правильных ответов
     *                  count_mark              - количество баллов
     *                  count_mark_all          - сколько всего баллов за правильные ответы
     *                  attachments                         - список вложений
     *                      {examination_attachment_id}         - ключ привязки экзамена и вложения
     *                          attachment_path                     - путь вложения
     *                          attachment_type                     - тип вложения(.mp4)
     *                          attachment_title                    - название вложения
     *          average_times        - статистика по экзаменам работника
     *              {type_test_id}          - ключ типа теста
     *                  type_test_id            - ключ типа теста
     *                  avr                     - средняя продолжительность
     *                  sum                     - суммарная продолжительность
     *                  count                   - количество тестов
     *      summary_exams:          - главная вкладка аналитики
     *          exam_all                    - суммарное количество пройденных экзаменов
     *          exam_by_count               - статистика пройденных экзаменов по типам
     *              common
     *                  count_passed                - количество пройденных экзаменов
     *                  count_need                  - количество экзаменов требуемых для прохождения
     *                  mark_sum                    - суммарное количество баллов
     *                  mark_average                - средний балл
     *              test_types
     *                  id                          - ключ типа теста
     *                  title                       - название типа теста
     *                  count_passed                - количество пройденных экзаменов по типу теста
     *                  count_need                  - количество экзаменов по типу теста требуемых для прохождения
     *                  mark_sum                    - суммарное количество баллов по типу теста
     *                  mark_average                - средний балл по типу теста
     *          exam_by_mark                - статистика пройденных экзаменов по баллам
     *              common                      - общая
     *                  0                           - меньше 1 балла
     *                  1                           - меньше 2 баллов
     *                  2                           - меньше 3 баллов
     *                  3                           - все остальные случаи
     *              test_types                  - по типам теста
     *                  test_type_id                - ключ типа теста
     *                      0                           - меньше 1 балла
     *                      1                           - меньше 2 баллов
     *                      2                           - меньше 3 баллов
     *                      3                           - все остальные случаи
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=GetPersonalAnalyticData&subscribe=&data={%22worker_id%22:1,%22mines%22:[],%22companies%22:[60002522,20026575],%22date_start%22:%222020-01-01%22,%22date_end%22:%222023-12-31%22,%22positions%22:[]}
     */
    public static function GetPersonalAnalyticData($data_post = NULL): array
    {
        $result = array(
            'personal_info' => array(),             // персональные сведения о работника
            'list_personal_exam' => array(          // список успеваемости работников
                'test_types' => array(),
                'common' => array(
                    'count_passed' => 0,
                    'mark_sum' => 0,
                    'mark_average' => 0,
                    'duration_sum' => 0,
                    'duration_average' => 0,
                ),
            ),
            'worker_exams' => array(),              // список пройденных экзаменов
            'summary_exams' => array(               // главная вкладка аналитики
                'exam_by_count' => array(            // статистика пройденных экзаменов по типам
                    'test_types' => array(),
                    'common' => array(
                        'count_passed' => 0,
                        'mark_sum' => 0,
                        'mark_average' => 0,
                        'count_need' => 0,
                    ),
                ),
                'exam_by_mark' => array(
                    'common' => array(              // общая
                        '0' => 0,
                        '1' => 0,
                        '2' => 0,
                        '3' => 0,
                    ),
                    'test_types' => array()         // по типам теста
                ),                                  // статистика пройденных экзаменов по баллам
            ),
        );
        $count_record = 0;

        $log = new LogAmicumFront("GetPersonalAnalyticData");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'worker_id') ||
                !property_exists($post, 'mines') ||
                !property_exists($post, 'companies') ||
                !property_exists($post, 'positions') ||
                !property_exists($post, 'date_start') ||
                !property_exists($post, 'date_end')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            $worker_id = $post->worker_id;
            $mines = $post->mines;
            $companies = $post->companies;
            $positions = $post->positions;
            $date_start = $post->date_start;
            $date_end = $post->date_end;

            /** ПЕРСОНАЛЬНЫЕ СВЕДЕНИЯ О РАБОТНИКЕ */
            $response = HandbookEmployeeController::GetWorkerInfoPersonalCard('{"worker_id":' . $worker_id . '}');
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения персональных сведений о работнике");
            }
            $result['personal_info'] = $response['Items'];

            /** СПИСОК ПРОЙДЕННЫХ ЭКЗАМЕНОВ */
            $response = self::GetListPersonalExamination('{"worker_id":' . $worker_id . ',"type_tests":[1,2,3]}');
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения списка экзаменов работника");
            }
            $result['worker_exams'] = $response['Items'];

            /** ИНИЦИАЛИЗИРУЕМ СТАТИСТИКУ ПО ТИПАМ ЭКЗАМЕНОВ */
            $type_tests = TypeTest::find()->asArray()->all();

            foreach ($type_tests as $type_test) {
                $result['summary_exams']['exam_by_count']['test_types'][$type_test['id']] = array(
                    'id' => $type_test['id'],
                    'title' => $type_test['title'],
                    'count_passed' => 0,
                    'count_need' => 0,
                    'mark_sum' => 0,
                    'mark_average' => 0,
                );

                $result['list_personal_exam']['test_types'][$type_test['id']] = array(
                    'type_test_id' => $type_test['id'],
                    'count_passed' => 0,
                    'mark_sum' => 0,
                    'mark_average' => 0,
                    'duration_sum' => 0,
                    'duration_average' => 0,
                );

                $result['summary_exams']['exam_by_mark']['test_types'][$type_test['id']] = array(
                    '0' => 0,
                    '1' => 0,
                    '2' => 0,
                    '3' => 0,
                );
            }

            /** ИНИЦИАЛИЗИРУЕМ СТАТИСТИКУ ПО МЕСЯЦАМ ЭКЗАМЕНОВ */

            $exams = Examination::find()
                ->joinWith('companyDepartment.company')
                ->joinWith('worker.employee')
                ->joinWith('position')
                ->where(['<=', 'examination.date_time', $date_end])
                ->andWhere(['>=', 'examination.date_time', $date_start])
                ->andWhere(['examination.worker_id' => $worker_id])
                ->andWhere([
                    'or',
                    ['status_id' => StatusEnumController::EXAM_DONE],
                    ['status_id' => StatusEnumController::EXAM_NOT_DONE],
                ])
                ->andFilterWhere([
                    'examination.mine_id' => $mines,
                    'examination.company_department_id' => $companies,
                    'examination.position_id' => $positions,
                ])
                ->asArray()
                ->all();

//            $log->addData($exams, '$exams', __LINE__);

            foreach ($exams as $exam) {
                /** Количество пройденных экзаменов по типу теста */
                $result['summary_exams']['exam_by_count']['test_types'][$exam['type_test_id']]['count_passed']++;
                $result['summary_exams']['exam_by_count']['common']['count_passed']++;

                /** Средний балл по типу теста */
                $result['summary_exams']['exam_by_count']['common']['mark_sum'] += $exam['count_mark'];
                $result['summary_exams']['exam_by_count']['test_types'][$exam['type_test_id']]['mark_sum'] += $exam['count_mark'];

                $result['summary_exams']['exam_by_count']['common']['mark_average'] = $result['summary_exams']['exam_by_count']['common']['mark_sum'] / $result['summary_exams']['exam_by_count']['common']['count_passed'];
                $result['summary_exams']['exam_by_count']['test_types'][$exam['type_test_id']]['mark_average'] = $result['summary_exams']['exam_by_count']['test_types'][$exam['type_test_id']]['mark_sum'] / $result['summary_exams']['exam_by_count']['test_types'][$exam['type_test_id']]['count_passed'];

                /** Статистика пройденных экзаменов по баллам */
                if ($exam['count_mark'] < 1) {
                    $result['summary_exams']['exam_by_mark']['common'][0]++;
                    $result['summary_exams']['exam_by_mark']['test_types'][$exam['type_test_id']][0]++;
                } else if ($exam['count_mark'] < 2) {
                    $result['summary_exams']['exam_by_mark']['common'][1]++;
                    $result['summary_exams']['exam_by_mark']['test_types'][$exam['type_test_id']][1]++;
                } else if ($exam['count_mark'] < 3) {
                    $result['summary_exams']['exam_by_mark']['common'][2]++;
                    $result['summary_exams']['exam_by_mark']['test_types'][$exam['type_test_id']][2]++;
                } else {
                    $result['summary_exams']['exam_by_mark']['common'][3]++;
                    $result['summary_exams']['exam_by_mark']['test_types'][$exam['type_test_id']][3]++;
                }

                /** Список успеваемости работников
                 */
                $result['list_personal_exam']['common']['count_passed']++;
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['count_passed']++;

                $result['list_personal_exam']['common']['mark_sum'] += $exam['count_mark'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['mark_sum'] += $exam['count_mark'];

                $result['list_personal_exam']['common']['mark_average'] = $result['list_personal_exam']['common']['mark_sum'] / $result['list_personal_exam']['common']['count_passed'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['mark_average'] = $result['list_personal_exam']['test_types'][$exam['type_test_id']]['mark_sum'] / $result['list_personal_exam']['test_types'][$exam['type_test_id']]['count_passed'];

                $result['list_personal_exam']['common']['duration_sum'] += $exam['duration'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['duration_sum'] += $exam['duration'];

                $result['list_personal_exam']['common']['duration_average'] = $result['list_personal_exam']['common']['duration_sum'] / $result['list_personal_exam']['common']['count_passed'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['duration_average'] = $result['list_personal_exam']['test_types'][$exam['type_test_id']]['duration_sum'] / $result['list_personal_exam']['test_types'][$exam['type_test_id']]['count_passed'];

            }

            /** Количество экзаменов по типу теста требуемых для прохождения */
            $all_need_tests = (new Query())
                ->select(['count(test.id) as count_test', 'type_test_id'])
                ->from('test')
                ->innerJoin('test_company_department', 'test_company_department.test_id=test.id')
                ->innerJoin('type_test_type', 'type_test_type.test_id=test.id')
                ->where(['company_department_id' => $companies])
                ->andWhere(['actual_status' => 1])
                ->groupBy('type_test_id')
                ->all();

            foreach ($all_need_tests as $need_test) {
                $result['summary_exams']['exam_by_count']['test_types'][$need_test['type_test_id']]['count_need'] = $need_test['count_test'];
                $result['summary_exams']['exam_by_count']['common']['count_need'] += $need_test['count_test'];
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * GetCompaniesAnalyticData() - Метод получения статистики по департаментам предсменного экзаменатора
     * Входные данные:
     *      mines       - массив шахт
     *      companies   - массив подразделений
     *      date_start  - дата начала выборки
     *      date_end    - дата окончания выборки
     *      positions   - массив должностей
     * Выходные данные:
     *      exam_by_companies       - статистика по подразделения
     *          {company_id}            - ключ подразделения
     *              company_id              - ключ подразделения
     *              company_title           - название подразделения
     *              mark_sum                - суммарное количество баллов
     *              mark_average            - среднее количество баллов
     *              count_test_passed       - количество пройденных тестов
     *              count_worker_passed     - количество работников прошедших тестирование
     *              count_worker_all        - количество работников суммарное
     *              worker_passed           - перечень работников подразделения
     *                  {worker_id}             - ключ работника
     *                      worker_id               - ключ работника
     *      exam_by_mine            - статистика по выборке
     *          count_worker_passed     - количество работников прошедших тестирование
     *          worker_passed           - перечень работников шахты
     *              {worker_id}             - ключ работника
     *                  worker_id               - ключ работника
     *          count_test_passed       - количество пройденных тестов
     *          mark_sum                - суммарное количество баллов
     *          mark_average            - среднее количество баллов
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=GetCompaniesAnalyticData&subscribe=&data={%22mines%22:[],%22companies%22:[60002522,20026575],%22date_start%22:%222020-01-01%22,%22date_end%22:%222023-12-31%22,%22positions%22:[]}
     */
    public static function GetCompaniesAnalyticData($data_post = NULL): array
    {
        $result = array(
            'exam_by_companies' => array(),
            'exam_by_mine' => array(
                'count_worker_passed' => 0,
                'worker_passed' => [],
                'count_test_passed' => 0,
                'mark_sum' => 0,
                'mark_average' => 0,
            ),
        );
        $count_record = 0;

        $log = new LogAmicumFront("GetCompaniesAnalyticData");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'mines') ||
                !property_exists($post, 'companies') ||
                !property_exists($post, 'positions') ||
                !property_exists($post, 'date_start') ||
                !property_exists($post, 'date_end')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            $mines = $post->mines;
            $companies = $post->companies;
            $positions = $post->positions;
            $date_start = $post->date_start;
            $date_end = $post->date_end;

            $exams = Examination::find()
                ->joinWith('companyDepartment.company')
                ->where(['<=', 'examination.date_time', $date_end])
                ->andWhere(['>=', 'examination.date_time', $date_start])
                ->andWhere([
                    'or',
                    ['status_id' => StatusEnumController::EXAM_DONE],
                    ['status_id' => StatusEnumController::EXAM_NOT_DONE],
                ])
                ->andFilterWhere([
                    'examination.mine_id' => $mines,
                    'examination.company_department_id' => $companies,
                    'examination.position_id' => $positions,
                ])
                ->asArray()
                ->all();

            $count_workers_by_company = (new Query())
                ->select(['company_department_id', 'count(id) as count_worker'])
                ->from('worker')
                ->andFilterWhere([
                    'company_department_id' => $companies,
                ])
                ->andWhere(['<=', 'worker.date_start', $date_end])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date_end],
                    ['is', 'worker.date_end', null]
                ])
                ->groupBy('company_department_id')
                ->indexBy('company_department_id')
                ->all();

            foreach ($exams as $exam) {

                if (!isset($result['exam_by_companies'][$exam['company_department_id']])) {
                    $result['exam_by_companies'][$exam['company_department_id']] = array(
                        'company_id' => $exam['company_department_id'],
                        'company_title' => $exam['companyDepartment']['company']['title'],
                        'mark_sum' => 0,
                        'mark_average' => 0,
                        'count_test_passed' => 0,
                        'count_worker_passed' => 0,
                        'count_worker_all' => $count_workers_by_company[$exam['company_department_id']]['count_worker'],
                        'worker_passed' => [],
                    );
                }
                $result['exam_by_mine']['count_test_passed']++;
                $result['exam_by_companies'][$exam['company_department_id']]['count_test_passed']++;

                $result['exam_by_mine']['worker_passed'][$exam['worker_id']] = $exam['worker_id'];
                $result['exam_by_mine']['count_worker_passed'] = count($result['exam_by_mine']['worker_passed']);

                $result['exam_by_companies'][$exam['company_department_id']]['worker_passed'][$exam['worker_id']] = $exam['worker_id'];
                $result['exam_by_companies'][$exam['company_department_id']]['count_worker_passed'] = count($result['exam_by_companies'][$exam['company_department_id']]['worker_passed']);

                $result['exam_by_mine']['mark_sum'] += $exam['count_mark'];
                $result['exam_by_companies'][$exam['company_department_id']]['mark_sum'] += $exam['count_mark'];


                $result['exam_by_mine']['mark_average'] = $result['exam_by_mine']['mark_sum'] / $result['exam_by_mine']['count_test_passed'];
                $result['exam_by_companies'][$exam['company_department_id']]['mark_average'] = $result['exam_by_companies'][$exam['company_department_id']]['mark_sum'] / $result['exam_by_companies'][$exam['company_department_id']]['count_test_passed'];

            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetCompanyAnalyticData() - Метод получения статистики по департаменту предсменного экзаменатора
     * Входные данные:
     *      mines       - массив шахт
     *      companies   - массив подразделений
     *      date_start  - дата начала выборки
     *      date_end    - дата окончания выборки
     *      positions   - массив должностей
     * Выходные данные:
     *      exam_by_companies       - статистика по подразделения
     *          company_id              - ключ подразделения
     *          company_title           - название подразделения
     *          mark_sum                - суммарное количество баллов
     *          mark_average            - среднее количество баллов
     *          count_test_passed       - количество пройденных тестов
     *          count_worker_passed     - количество работников прошедших тестирование
     *          count_worker_all        - количество работников суммарное
     *          worker_passed           - перечень работников подразделения
     *              {worker_id}             - ключ работника
     *                  worker_id               - ключ работника
     *      worker_exams        - список экзаменов работника
     *          examinations        - список экзаменов пользователя
     *              {examination_id}        - ключ экзамена
     *                  examination_id          - ключ экзамена
     *                  date_time               - дата прохождения
     *                  test_id                 - ключ теста
     *                  test_title              - название теста
     *                  type_test_id            - ключ типа теста
     *                  kind_test_id            - ключ вида теста
     *                  kind_test_title         - название вида теста
     *                  duration                - продолжительность в секундах
     *                  status_id               - ключ статуса теста
     *                  status_title            - название статуса теста
     *                  answers_count           - количество вопросов общее
     *                  answers_true_count      - количество правильных ответов
     *          average_times        - статистика по экзаменам работника
     *              {type_test_id}          - ключ типа теста
     *                  type_test_id            - ключ типа теста
     *                  avr                     - средняя продолжительность
     *                  sum                     - суммарная продолжительность
     *                  count                   - количество тестов
     *      exam_by_month               - статистика пройденных экзаменов по типам и месяцам
     *          test_types:                 - по типам тестов
     *              type_test_id                - тип теста
     *                  year                        - год
     *                      month                       - месяц
     *                          year                        - год
     *                          month                       - месяц
     *                          count_passed                - количество экзаменов по типу теста требуемых для прохождения в месяц
     *                          mark_sum                    - суммарное количество баллов по типу теста в месяц
     *                          mark_average                - средний балл по типу теста в месяц
     *          common                      - общая
     *              year                        - год
     *                      month                       - месяц
     *                          year                        - год
     *                          month                       - месяц
     *                          count_passed                - количество экзаменов по типу теста требуемых для прохождения в месяц
     *                          mark_sum                    - суммарное количество баллов по типу теста в месяц
     *                          mark_average                - средний балл по типу теста в месяц
     *      exam_by_type                - статистика пройденных экзаменов по типам
     *              id                          - ключ типа теста
     *              title                       - название типа теста
     *              count_passed                - количество пройденных экзаменов по типу теста
     *              count_need                  - количество экзаменов по типу теста требуемых для прохождения
     *              mark_sum                    - суммарное количество баллов по типу теста
     *              mark_average                - средний балл по типу теста
     *      exam_by_mark                - статистика пройденных экзаменов по баллам
     *          common                      - общая
     *              0                           - меньше 1 балла
     *              1                           - меньше 2 баллов
     *              2                           - меньше 3 баллов
     *              3                           - все остальные случаи
     *          test_types                  - по типам теста
     *              test_type_id                - ключ типа теста
     *                  0                           - меньше 1 балла
     *                  1                           - меньше 2 баллов
     *                  2                           - меньше 3 баллов
     *                  3                           - все остальные случаи
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=GetCompanyAnalyticData&subscribe=&data={%22mines%22:[],%22company_id%22:60002522,%22date_start%22:%222020-01-01%22,%22date_end%22:%222023-12-31%22,%22positions%22:[]}
     */
    public static function GetCompanyAnalyticData($data_post = NULL): array
    {
        $result = array(
            'company_info' => array(),
            'exam_by_count' => array(            // статистика пройденных экзаменов по типам
                'test_types' => array(),
                'common' => array(
                    'count_passed' => 0,
                    'mark_sum' => 0,
                    'mark_average' => 0,
                    'count_need' => 0,
                ),
            ),
            'exam_by_mark' => array(
                'common' => array(              // общая
                    '0' => 0,
                    '1' => 0,
                    '2' => 0,
                    '3' => 0,
                ),
                'test_types' => array()         // по типам теста
            ),                                  // статистика пройденных экзаменов по баллам
            'exam_by_month' => null,
            'list_personal_exam' => array(          // список успеваемости работников
                'test_types' => array(),
                'common' => array(
                    'count_passed' => 0,
                    'mark_sum' => 0,
                    'mark_average' => 0,
                    'duration_sum' => 0,
                    'duration_average' => 0,
                ),
            ),
        );
        $count_record = 0;

        $log = new LogAmicumFront("GetCompanyAnalyticData");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'mines') ||
                !property_exists($post, 'company_id') ||
                !property_exists($post, 'positions') ||
                !property_exists($post, 'date_start') ||
                !property_exists($post, 'date_end')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            $mines = $post->mines;
            $company_id = $post->company_id;
            $positions = $post->positions;
            $date_start = $post->date_start;
            $date_end = $post->date_end;

            /** ИНИЦИАЛИЗИРУЕМ СТАТИСТИКУ ПО ТИПАМ ЭКЗАМЕНОВ */
            $type_tests = TypeTest::find()->asArray()->all();

            foreach ($type_tests as $type_test) {
                $result['exam_by_count']['test_types'][$type_test['id']] = array(
                    'id' => $type_test['id'],
                    'title' => $type_test['title'],
                    'count_passed' => 0,
                    'mark_sum' => 0,
                    'mark_average' => 0,
                );

                $result['exam_by_mark']['test_types'][$type_test['id']] = array(
                    '0' => 0,
                    '1' => 0,
                    '2' => 0,
                    '3' => 0,
                );
                $result['exam_by_month']['test_types'][$type_test['id']] = array(
                    'id' => $type_test['id'],
                    'title' => $type_test['title'],
                    'data' => array()
                );

                $result['list_personal_exam']['test_types'][$type_test['id']] = array(
                    'type_test_id' => $type_test['id'],
                    'count_passed' => 0,
                    'mark_sum' => 0,
                    'mark_average' => 0,
                    'duration_sum' => 0,
                    'duration_average' => 0,
                    'worker_exams' => array()
                );

                $month = (int)date('m', strtotime($date_start));
                $year = (int)date('Y', strtotime($date_start));

                $month_end = (int)date('m', strtotime($date_end));
                $year_end = (int)date('Y', strtotime($date_end));

                $flag_exit = false;
                $j = 0;

                for ($i = $month; !$flag_exit; $i++) {

                    $result['exam_by_month']['test_types'][$type_test['id']]['data'][$year][$i] = array(
                        'year' => $year,
                        'month' => $i,
                        'count_passed' => 0,
                        'mark_sum' => 0,
                        'mark_average' => 0,
                    );

                    $result['exam_by_month']['common']['data'][$year][$i] = array(
                        'year' => $year,
                        'month' => $i,
                        'count_passed' => 0,
                        'mark_sum' => 0,
                        'mark_average' => 0,
                    );

                    if ($year == $year_end and $i == $month_end) {
                        $flag_exit = true;
                    }

                    if ($i == 12) {
                        $year++;
                        $i = 0;
                    }

                    if ($j == 500) {
                        $log->addData($flag_exit, '$flag_exit', __LINE__);
                        $log->addData($i, '$i', __LINE__);
                        $log->addData($year, '$year', __LINE__);
                        $log->addData($month, '$month', __LINE__);
                        $log->addData($year_end, '$year_end', __LINE__);
                        $log->addData($month_end, '$month_end', __LINE__);
                        throw new Exception($i . "Стоп" . $month);
                    }
                    $j++;
                }
            }

            $exams = Examination::find()
                ->joinWith('companyDepartment.company')
                ->joinWith('worker.employee')
                ->joinWith('position')
                ->where(['<=', 'examination.date_time', $date_end])
                ->andWhere(['>=', 'examination.date_time', $date_start])
                ->andWhere([
                    'or',
                    ['status_id' => StatusEnumController::EXAM_DONE],
                    ['status_id' => StatusEnumController::EXAM_NOT_DONE],
                ])
                ->andFilterWhere([
                    'examination.mine_id' => $mines,
                    'examination.company_department_id' => $company_id,
                    'examination.position_id' => $positions,
                ])
                ->asArray()
                ->all();

            $count_workers_by_company = (new Query())
                ->select(['company_department_id', 'count(id) as count_worker'])
                ->from('worker')
                ->andFilterWhere([
                    'company_department_id' => $company_id,
                ])
                ->andWhere(['<=', 'worker.date_start', $date_end])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date_end],
                    ['is', 'worker.date_end', null]
                ])
                ->groupBy('company_department_id')
                ->indexBy('company_department_id')
                ->all();

            $company = Company::findOne(['id' => $company_id]);

            $result['company_info'] = array(
                'company_id' => $company_id,
                'company_title' => $company->title,
                'mark_sum' => 0,
                'mark_average' => 0,
                'count_test_passed' => 0,
                'count_worker_passed' => 0,
                'count_worker_all' => $count_workers_by_company[$company_id]['count_worker'],
                'worker_passed' => [],
            );

            foreach ($exams as $exam) {
                $result['company_info']['count_test_passed']++;
                $result['company_info']['worker_passed'][$exam['worker_id']] = $exam['worker_id'];
                $result['company_info']['count_worker_passed'] = count($result['company_info']['worker_passed']);
                $result['company_info']['mark_sum'] += $exam['count_mark'];
                $result['company_info']['mark_average'] = $result['company_info']['mark_sum'] / $result['company_info']['count_test_passed'];

                /** Количество пройденных экзаменов по типу теста */
                $result['exam_by_count']['test_types'][$exam['type_test_id']]['count_passed']++;
                $result['exam_by_count']['common']['count_passed']++;

                /** Средний балл по типу теста */
                $result['exam_by_count']['common']['mark_sum'] += $exam['count_mark'];
                $result['exam_by_count']['test_types'][$exam['type_test_id']]['mark_sum'] += $exam['count_mark'];

                $result['exam_by_count']['common']['mark_average'] = $result['exam_by_count']['common']['mark_sum'] / $result['exam_by_count']['common']['count_passed'];
                $result['exam_by_count']['test_types'][$exam['type_test_id']]['mark_average'] = $result['exam_by_count']['test_types'][$exam['type_test_id']]['mark_sum'] / $result['exam_by_count']['test_types'][$exam['type_test_id']]['count_passed'];

                /** Статистика пройденных экзаменов по баллам */
                if ($exam['count_mark'] < 1) {
                    $result['exam_by_mark']['common'][0]++;
                    $result['exam_by_mark']['test_types'][$exam['type_test_id']][0]++;
                } else if ($exam['count_mark'] < 2) {
                    $result['exam_by_mark']['common'][1]++;
                    $result['exam_by_mark']['test_types'][$exam['type_test_id']][1]++;
                } else if ($exam['count_mark'] < 3) {
                    $result['exam_by_mark']['common'][2]++;
                    $result['exam_by_mark']['test_types'][$exam['type_test_id']][2]++;
                } else {
                    $result['exam_by_mark']['common'][3]++;
                    $result['exam_by_mark']['test_types'][$exam['type_test_id']][3]++;
                }

                /** Статистика пройденных экзаменов по типам и месяцам */
                $month = (int)date('m', strtotime($exam['date_time']));
                $year = (int)date('Y', strtotime($exam['date_time']));
                $result['exam_by_month']['test_types'][$exam['type_test_id']]['data'][$year][$month]['count_passed']++;
                $result['exam_by_month']['test_types'][$exam['type_test_id']]['data'][$year][$month]['mark_sum'] += $exam['count_mark'];
                $result['exam_by_month']['test_types'][$exam['type_test_id']]['data'][$year][$month]['mark_average'] = $result['exam_by_month']['test_types'][$exam['type_test_id']]['data'][$year][$month]['mark_sum'] / $result['exam_by_month']['test_types'][$exam['type_test_id']]['data'][$year][$month]['count_passed'];

                $result['exam_by_month']['common']['data'][$year][$month]['count_passed']++;
                $result['exam_by_month']['common']['data'][$year][$month]['mark_sum'] += $exam['count_mark'];
                $result['exam_by_month']['common']['data'][$year][$month]['mark_average'] = $result['exam_by_month']['common']['data'][$year][$month]['mark_sum'] / $result['exam_by_month']['common']['data'][$year][$month]['count_passed'];

                /** Список успеваемости работников
                 *              worker_id                       - ключ работника
                 *              full_name                       - ФИО
                 *              position_id                     - ключ должности
                 *              position_title                  - название должности
                 *              company_id                      - ключ подразделения работника
                 *              company_title                   - название подразделения работника
                 *              mark_sum                        - количество баллов работника
                 *              mark_average                    - средний балл работника
                 *              duration_average                - средняя продолжительность
                 */
                if (!isset($result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']])) {

                    $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']] = array(
                        'worker_id' => $exam['worker_id'],
                        'full_name' => Assistant::GetFullName($exam['worker']['employee']['first_name'], $exam['worker']['employee']['patronymic'], $exam['worker']['employee']['last_name']),
                        'position_id' => $exam['position_id'],
                        'position_title' => $exam['position']['title'],
                        'company_id' => $exam['company_department_id'],
                        'company_title' => $exam['companyDepartment']['company']['title'],
                        'count_passed' => 0,
                        'mark_sum' => 0,
                        'mark_average' => 0,
                        'duration_sum' => 0,
                        'duration_average' => 0,
                    );
                }

                $result['list_personal_exam']['common']['count_passed']++;
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['count_passed']++;
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['count_passed']++;

                $result['list_personal_exam']['common']['mark_sum'] += $exam['count_mark'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['mark_sum'] += $exam['count_mark'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['mark_sum'] += $exam['count_mark'];

                $result['list_personal_exam']['common']['mark_average'] = $result['list_personal_exam']['common']['mark_sum'] / $result['list_personal_exam']['common']['count_passed'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['mark_average'] = $result['list_personal_exam']['test_types'][$exam['type_test_id']]['mark_sum'] / $result['list_personal_exam']['test_types'][$exam['type_test_id']]['count_passed'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['mark_average'] = $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['mark_sum'] / $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['count_passed'];

                $result['list_personal_exam']['common']['duration_sum'] += $exam['duration'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['duration_sum'] += $exam['duration'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['duration_sum'] += $exam['duration'];

                $result['list_personal_exam']['common']['duration_average'] = $result['list_personal_exam']['common']['duration_sum'] / $result['list_personal_exam']['common']['count_passed'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['duration_average'] = $result['list_personal_exam']['test_types'][$exam['type_test_id']]['duration_sum'] / $result['list_personal_exam']['test_types'][$exam['type_test_id']]['count_passed'];
                $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['duration_average'] = $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['duration_sum'] / $result['list_personal_exam']['test_types'][$exam['type_test_id']]['worker_exams'][$exam['worker_id']]['count_passed'];


            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * GetListCompanyExamination() - Метод получения списка экзаменов пройденных по подразделению ***  НЕ ДОДЕЛАН ***
     * входные данные:
     *      company_id          - ключ подразделения
     *      type_tests          - массив ключей типов теста для выборки
     * Выходные данные:
     *      examinations        - список экзаменов пользователя
     *          {examination_id}        - ключ экзамена
     *              examination_id          - ключ экзамена
     *              date_time               - дата прохождения
     *              test_id                 - ключ теста
     *              test_title              - название теста
     *              type_test_id            - ключ типа теста
     *              kind_test_id            - ключ вида теста
     *              kind_test_title         - название вида теста
     *              duration                - продолжительность в секундах
     *              status_id               - ключ статуса теста
     *              status_title            - название статуса теста
     *              answers_count           - количество вопросов общее
     *              answers_true_count      - количество правильных ответов
     *      average_times        - статистика по экзаменам работника
     *          {type_test_id}          - ключ типа теста
     *              type_test_id            - ключ типа теста
     *              avr                     - средняя продолжительность
     *              sum                     - суммарная продолжительность
     *              count                   - количество тестов
     * @example 127.0.0.1/read-manager-amicum?controller=Exam&method=GetListCompanyExamination&subscribe=&data={"company_id":1,"type_tests":[1,2,3]}
     */
    public static function GetListCompanyExamination($data_post = NULL): array
    {
        $result = array(
            'examinations' => null,
            'average_times' => null,
        );
        $count_record = 0;

        $log = new LogAmicumFront("GetListCompanyExamination");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'type_tests') ||
                !property_exists($post, 'date_start') ||
                !property_exists($post, 'company_id')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            $company_id = $post->company_id;
            $date_start = $post->date_start;

            $type_tests = $post->type_tests;

            $list_exam = Examination::find()
                ->innerJoinWith('test.kindTest')
                ->joinWith('examinationAnswers')
                ->joinWith('status')
                ->joinWith('worker.employee')
                ->joinWith('position')
                ->where(['company_department_id' => $company_id])
                ->andWhere(['>=', 'examination.date_time', $date_start])
                ->andWhere([
                    'or',
                    ['status_id' => StatusEnumController::EXAM_DONE],
                    ['status_id' => StatusEnumController::EXAM_NOT_DONE],
                ])
                ->all();

            foreach ($list_exam as $exam) {
                if (array_search($exam['type_test_id'], $type_tests) !== false) {
                    $result['examinations'][$exam['worker_id']] = array(
                        'examination_id' => $exam['id'],
                        'worker_id' => $exam['worker_id'],
                        'duration' => $exam['duration'],
                        'date_time' => $exam['date_time'],
                        'test_id' => $exam['test_id'],
                        'test_title' => $exam['test']['title'],
                        'type_test_id' => $exam['type_test_id'],
                        'kind_test_id' => $exam['test']['kind_test_id'],
                        'kind_test_title' => $exam['test']['kindTest']['title'],
                        'status_id' => $exam['status_id'],
                        'status_title' => $exam['status']['title'],
                        'answers_count' => count($exam['examinationAnswers']),
                        'answers_true_count' => 0,
                    );

                    foreach ($exam->examinationAnswers as $answer) {
                        if ($answer->flag_true == 1) {
                            $result['examinations'][$exam['id']]['answers_true_count']++;
                        }
                    }
                }
                if (!isset($result['average_times'][$exam['type_test_id']])) {
                    $result['average_times'][$exam['type_test_id']] = array(
                        'type_test_id' => $exam['type_test_id'],
                        'avr' => 0,
                        'sum' => 0,
                        'count' => 0,
                    );
                }
                $result['average_times'][$exam['type_test_id']]['sum'] += $exam['duration'];
                $result['average_times'][$exam['type_test_id']]['count']++;
                $result['average_times'][$exam['type_test_id']]['avr'] = round($result['average_times'][$exam['type_test_id']]['sum'] / $result['average_times'][$exam['type_test_id']]['count'], 0);

            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * ListMap() - Метод получения списка карт
     * выходные данные:
     *  {id}
     *      id                      - ключ карты
     *      title                   - название карты
     *      attachment_id           - вложение карты
     *      count_number            - количество номеров на карте
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=ListMap&subscribe=&data={}
     */
    public static function ListMap($data_post = NULL): array
    {
        $result = null;
        $count_record = 0;

        $log = new LogAmicumFront("ListMap");

        try {
            $log->addLog("Начало выполнения метода");

            $result = Map::find()
                ->indexBy('id')
                ->asArray()
                ->all();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * GetPredExam - Метод получения сведений о прохождении предсменного тестирования
     * Входной объект:
     *      date_end            - дата окончания выборки
     *      date_start          - дата начала выборки
     * Выходной объект:
     *      pred_exams:         - массив предсменных проверок знаний
     *          []
     *              mine_id             - ключ шахтного поля
     *              worker_id           - ключ работника
     *              mo_session_id       - ключ сессии медицинского осмотра
     *              start_test_time     - время начала предсменного тестирования
     *              status_id           - ключ статуса (экзамен начат, идет, закончен)
     *              sap_kind_exam_id    - ключ вида тестирования (внешний справочник)
     *              exam_status         - статус сдачи экзамена (сдал/не сдал)
     *              count_right         - количество правильных ответов
     *              count_false         - количество не правильных ответов
     *              question_count      - количество вопросов
     *              points              - количество баллов
     *              sap_id              - ключ интеграции (quiz_session_id)
     *              date_created        - дата создания
     *              date_modified       - дата изменения
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=GetPredExam&subscribe=&data={"date_start":"2023-01-01","date_end":"2023-12-01",}
     */
    public static function GetPredExam($data_post)
    {
        $log = new LogAmicumFront("GetPredExam");
        $result = array();

        try {
            $log->addLog("Начал выполнять метод");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'date_start') ||
                !property_exists($post, 'date_end') ||
                !property_exists($post, 'company_department_id')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            $date_start = $post->date_start;
            $date_end = $post->date_end;
            $company_department_id = $post->company_department_id;

            $filter = [];
            if ($company_department_id) {
                $response = DepartmentController::FindDepartment($company_department_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка получения вложенных департаментов: ' . $company_department_id);
                }
                $filter = ['company.id' => $response['Items']];
            }
            $pred_exams = PredExamHistory::find()
                ->joinWith('employee.workers.company')
                ->joinWith('status')
                ->where('start_test_time>="' . $date_start . '"')
                ->andWhere('start_test_time<="' . $date_end . '"')
                ->andWhere($filter)
                ->asArray()
                ->all();

            $all_companies = Company::find()->indexBy('id')->asArray()->all();

            $hand_paths = [];

            $i = 0;
            foreach ($pred_exams as $pred_exam) {
                if (isset($pred_exam['employee']) and count($pred_exam['employee']['workers']) > 0) {
                    $number = count($pred_exam['employee']['workers']) - 1;

                    $company_department_id = $pred_exam['employee']['workers'][$number]['company_department_id'];
                    $department_path = "";

                    if (!isset($hand_paths[$company_department_id])) {
                        $response = HandbookDepartmentController::GetAllParentsCompaniesWithCompany($company_department_id, $all_companies);               // путь до департамента работника
                        if ($response['status'] == 1) {
                            $department_paths = $response['Items'];
                            $department_path_array = explode(" /", $department_paths);
//                            $log->addData($department_path_array, '$department_path_array', __LINE__);
                            if (count($department_path_array) > 2) {
                                $department_path = $department_path_array[1];
                            } else {
                                $department_path = $department_path_array[0];
                            }
                        }

//                        $log->addData($department_path, '$department_path', __LINE__);

                        $hand_paths[$company_department_id] = $department_path;
                    } else {
                        $department_path = $hand_paths[$company_department_id];
                    }

                    if ($pred_exam['count_right'] > 1) {
                        $status_id = StatusEnumController::EXAM_DONE;
                        $exam_status_title = "Сдал";
                    } else {
                        $status_id = StatusEnumController::EXAM_NOT_DONE;
                        $exam_status_title = "Не сдал";
                    }

                    $result[] = array(
                        'i' => ++$i,
                        'FIO' => Assistant::GetFullName($pred_exam['employee']['first_name'], $pred_exam['employee']['patronymic'], $pred_exam['employee']['last_name']),
                        'tabel_number' => $pred_exam['employee']['workers'][$number]['tabel_number'],
                        'mine_title' => $department_path,
                        'department_title' => $pred_exam['employee']['workers'][$number]['company']['title'],
                        'start_test_time' => $pred_exam['start_test_time'],
                        'status_title' => $pred_exam['status']['title'],
                        'exam_status_id' => $status_id,
                        'exam_status_title' => $exam_status_title,
                        'count_right' => $pred_exam['count_right'],
                        'count_false' => $pred_exam['count_false'],
                        'question_count' => $pred_exam['question_count'],
                        'sap_id' => $pred_exam['sap_id'],
                    );
                }
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetSummaryPredExam - Метод получения сводных сведений о прохождении предсменного тестирования - в части аналитики провальных повторных тестирований с целью последующего отстранения
     * Входной объект:
     *      date_end                - дата окончания выборки
     *      date_start              - дата начала выборки
     *      company_department_id   - ключ подразделения
     * Выходной объект:
     *      {employee_id}   - ключ человека
     *          i                   - итератор
     *          FIO                 - ФИО работника
     *          employee_id         - ключ человека
     *          worker_id           - ключ работника
     *          tabel_number        - табельный номер
     *          mine_title          - Название подразделения
     *          department_title    - название подразделения
     *          position_title      - название должности
     *          exam_groups         - группа проваленный более двух раз предсменных тестирований
     *              j                   - ключ группы
     *                  exams               - массив проваленных экзаменов
     *                      []
     *                          start_test_time     - дата и время начала тестирования
     *                          status_title        - статус процедуры тестирования
     *                          count_right         - количество правильных ответов
     *                          exam_status_id      - ключ статус экзамена
     *                          exam_status_title   - название статуса экзамена
     *                          count_false         - количество ошибок
     *                          question_count      - количество вопросов
     *                          sap_id              - ключ из Квазара
     *                  date_time_last      - первая дата после провального тестирования
     *                  briefing_date_time           - дата и время проведения внепланового инструктажа по результатам провального тестирования
     *                  briefing_status_briefing     - был или нет проведен инструктаж
     *                  exam_repeat_done    - результат тестирования после внепланового инструктажа
     *                  suspension_from_work- результат необходимости отстранения от работы по результатам текущей группы провальных тестов
     *          suspension_from_work_by_last_exam               - итоговый результат необходимости отстранения от работы, на основе последнего прохождения
     *          suspension_from_work_by_percent_successfully    - итоговый результат необходимости отстранения от работы, на основе последнего прохождения
     *          percent_successfully- процент успешного прохождения предсменного экзаменатора
     *          good                - количество раз сдал хорошо
     *          bad                 - количество раз сдал плохо
     *          need                - количество раз, которое должен был сдавать по количеству полученных нарядов
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=GetSummaryPredExam&subscribe=&data={"date_start":"2023-01-01","date_end":"2023-12-01","company_department_id":null}
     */
    public static function GetSummaryPredExam($data_post)
    {
        $log = new LogAmicumFront("GetSummaryPredExam");
        $result = array();
        $employees = null;

        try {
            $log->addLog("Начал выполнять метод");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'date_start') ||
                !property_exists($post, 'date_end') ||
                !property_exists($post, 'company_department_id')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            $date_start = Assistant::GetStartProdDateTime($post->date_start);
            $date_end = Assistant::GetEndProdDateTime($post->date_end);
            $company_department_id = $post->company_department_id;
            $company_department_ids = null;

            $log->addData($date_start, '$date_start', __LINE__);
            $log->addData($date_end, '$date_end', __LINE__);

            $filter = [];
            if ($company_department_id) {
                $company_department_ids[] = $company_department_id;
                $response = DepartmentController::FindDepartment($company_department_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка получения вложенных департаментов: ' . $company_department_id);
                }
                $filter = ['company.id' => $response['Items']];
                $company_department_ids = $response['Items'];
            }

            $pred_exams = PredExamHistory::find()
                ->joinWith('employee.workers.company')
                ->joinWith('employee.workers.position')
                ->joinWith('status')
                ->andWhere(['between', "start_test_time", $date_start, $date_end])
//                ->andWhere(['pred_exam_history.employee_id' => 2921329])
                ->andWhere($filter)
                ->orderBy(["pred_exam_history.employee_id" => SORT_ASC, "pred_exam_history.start_test_time" => SORT_ASC])
                ->asArray()
                ->all();

            $all_companies = Company::find()->indexBy('id')->asArray()->all();

            $hand_paths = [];

            /** НАХОДИМ ДВОЙНЫЕ ПРОВАЛЫ ПО СДАЧЕ ПРЕДСМЕННОГО ЭКЗАМЕНАТОРА */
            $i = 0;
            $exam_group_i = 0;
            $workers_filter_briefing = null;
            $first_pred_exam_by_worker = null;                                                                              // первые сдачи по сменам, производственным датам работников
            $log->addData($pred_exams, '$pred_exams', __LINE__);
            foreach ($pred_exams as $pred_exam) {
                $employee_id = $pred_exam['employee_id'];
                $date_time_last = $pred_exam['start_test_time'];
                $shift_obj = Assistant::GetShiftByDateTime($date_time_last);

                if (!isset($count_workers_exam[$employee_id])) {                                                 // статистика сданных и не сданных экзаменов
                    $count_workers_exam[$employee_id] = array(
                        'good' => 0,        // сдал хорошо
                        'bad' => 0,         // сдал плохо
                        'need' => 0,        // количество раз, которое должен был сдавать по количеству полученных нарядов
                    );
                }

                if (isset($pred_exam['employee']) and !isset($first_pred_exam_by_worker[$employee_id][$shift_obj['date_work']][$shift_obj['shift_id']]) and count($pred_exam['employee']['workers']) != 0) {

                    /** ПОЛУЧЕНИЕ ПОСЛЕДНЕГО ПОДРАЗДЕЛЕНИЯ РАБОТНИКА */
                    $number = count($pred_exam['employee']['workers']) - 1;
                    $worker_id = $pred_exam['employee']['workers'][$number]['id'];


                    /** ПОЛУЧЕНИЕ ПУТИ ДО ПОДРАЗДЕЛЕНИЯ РАБОТНИКА */
                    $employee_company_department_id = $pred_exam['employee']['workers'][$number]['company_department_id'];
                    $department_path = "";
                    if (!isset($hand_paths[$employee_company_department_id])) {
                        $response = HandbookDepartmentController::GetAllParentsCompaniesWithCompany($employee_company_department_id, $all_companies);               // путь до департамента работника
                        if ($response['status'] == 1) {
                            $department_paths = $response['Items'];
                            $department_path_array = explode(" /", $department_paths);

                            if (count($department_path_array) > 2) {
                                $department_path = $department_path_array[1];
                            } else {
                                $department_path = $department_path_array[0];
                            }
                        }

                        $hand_paths[$employee_company_department_id] = $department_path;
                    } else {
                        $department_path = $hand_paths[$employee_company_department_id];
                    }

                    /** ФОРМИРОВАНИЕ РЕЗУЛЬТИРУЮЩЕГО МАССИВА ЭКЗАМЕНОВ */
                    if (!isset($employees[$employee_id])) {
                        $employees[$employee_id] = array(
                            'i' => ++$i,
                            'FIO' => Assistant::GetFullName($pred_exam['employee']['first_name'], $pred_exam['employee']['patronymic'], $pred_exam['employee']['last_name']),
                            'employee_id' => $employee_id,
                            'worker_id' => $worker_id,
                            'tabel_number' => $pred_exam['employee']['workers'][$number]['tabel_number'],
                            'mine_title' => $department_path,
                            'department_title' => $pred_exam['employee']['workers'][$number]['company']['title'],
                            'position_title' => $pred_exam['employee']['workers'][$number]['position']['title'],
                            'suspension_from_work_by_last_exam' => "",
                            'suspension_from_work_by_percent_successfully' => "",
                            'percent_successfully' => 0,
                            'exam_groups' => array()
                        );
                    }

                    /** ОПРЕДЕЛЯЕМ СТАТУС СДАЧИ ЭКЗАМЕНА */
                    if ($pred_exam['count_right'] > 1) {
                        $status_id = StatusEnumController::EXAM_DONE;
                        $exam_status_title = "Сдал";
                    } else {
                        $status_id = StatusEnumController::EXAM_NOT_DONE;
                        $exam_status_title = "Не сдал";
                    }

                    /** ЭКЗАМЕН СДАН ПЛОХО */
                    if ($status_id == StatusEnumController::EXAM_NOT_DONE) {
                        if (!isset($exams[$employee_id]['status_bad'])) {
                            $exams[$employee_id] = array(
                                'status_bad' => 0,
                                'bad_exams' => null
                            );
                        }

                        $exams[$employee_id]['status_bad']++;

                        $exams[$employee_id]['bad_exams'][] = array(
                            'start_test_time' => $pred_exam['start_test_time'],
                            'status_title' => $pred_exam['status']['title'],
                            'count_right' => $pred_exam['count_right'],
                            'exam_status_id' => $status_id,
                            'exam_status_title' => $exam_status_title,
                            'count_false' => $pred_exam['count_false'],
                            'question_count' => $pred_exam['question_count'],
                            'sap_id' => $pred_exam['sap_id']
                        );
                        $count_workers_exam[$employee_id]['bad']++;
                    }

                    /** ЭКЗАМЕН СДАН ПЛОХО БОЛЬШЕ ДВУХ РАЗ */
                    if (isset($exams[$employee_id]['status_bad']) and $exams[$employee_id]['status_bad'] > 1) {
                        $employees[$employee_id]['exam_groups'][$exam_group_i] = array(
                            'exams' => $exams[$employee_id]['bad_exams'],
                            'date_time_last' => $date_time_last,
                            'exam_repeat_done' => "",
                            'suspension_from_work' => "",
                            'briefing' => (object)array(),
                            'briefing_date_time' => "",
                            'briefing_status_briefing' => "",
                        );
                        $workers_filter_briefing[$worker_id] = $worker_id;
                    }

                    /** ЭКЗАМЕН СДАН ХОРОШО */
                    if ($status_id == StatusEnumController::EXAM_DONE) {
                        $exam_group_i++;
                        $exams[$employee_id] = array(
                            'status_bad' => 0,
                            'bad_exams' => null
                        );
                        $date_time_exam_done = date("Y-m-d", strtotime($date_time_last));
                        $worker_exams_done[$worker_id . "_" . $date_time_exam_done] = $date_time_exam_done;

                        $count_workers_exam[$employee_id]['good']++;
                    }

                    /** ВЕДЕМ УЧЕТ ПЕРВЫХ СДАЧ ПРЕДСМЕННОГО ЭКЗАМЕНА В СМЕНУ */
                    $first_pred_exam_by_worker[$employee_id][$shift_obj['date_work']][$shift_obj['shift_id']] = array(
                        'status_id' => $status_id,
                        'status_title' => $exam_status_title,
                        'pred_exam' => $pred_exam,
                    );
                }
            }
//            $log->addData($first_pred_exam_by_worker, '$first_pred_exam_by_worker', __LINE__);

            /** НАХОДИМ ИНСТРУКТАЖИ */
            if ($workers_filter_briefing) {
                $response = BriefingController::GetBriefings($workers_filter_briefing, $date_start, $date_end, TypeBriefingEnumController::UNPLANNED);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка получения инструктажей");
                }
                $briefings = $response['Items'];

                foreach ($employees as $key_employee => $employee) {
                    foreach ($employee['exam_groups'] as $key_group => $exam_group) {
                        $date = date("Y-m-d", strtotime($exam_group['date_time_last']));
                        if (isset($briefings[$employee['worker_id'] . "_" . $date])) {
                            $date_time = $briefings[$employee['worker_id'] . "_" . $date]['date_time'];
                            $status_briefing = "Внеплановый инструктаж проведен";
                        } else {
                            $date_time = "";
                            $status_briefing = "Требуется провести внеплановый инструктаж";
                        }
                        $employees[$key_employee]['exam_groups'][$key_group]['briefing'] = (object)array();
                        $employees[$key_employee]['exam_groups'][$key_group]['briefing_date_time'] = $date_time;
                        $employees[$key_employee]['exam_groups'][$key_group]['briefing_status_briefing'] = $status_briefing;

                        if (isset($worker_exams_done[$employee['worker_id'] . "_" . $date])) {
                            $employees[$key_employee]['exam_groups'][$key_group]['exam_repeat_done'] = "Сдал";
                            $employees[$key_employee]['exam_groups'][$key_group]['suspension_from_work'] = "Не требуется";
                            $employees[$key_employee]['suspension_from_work_by_last_exam'] = "Не требуется отстранение от работы";
                        } else {
                            $employees[$key_employee]['exam_groups'][$key_group]['exam_repeat_done'] = "Не сдал";
                            $employees[$key_employee]['exam_groups'][$key_group]['suspension_from_work'] = "Требуется";
                            $employees[$key_employee]['suspension_from_work_by_last_exam'] = "Требуется проведение внепланового инструктажа";
                        }
                    }
                }
            }

            /** НАХОДИМ ТЕХ КТО ВООБЩЕ НЕ СДАВАЛ */
            $response = OrderController::GetWorkersInOrder($company_department_ids, $date_start, $date_end, $workers_filter_briefing);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения работников по наряду без экзаменов");
            }

            $workers_witout_exam = $response['Items'];

            foreach ($workers_witout_exam as $worker) {
                $employee_id = $worker['worker']['employee_id'];
                $worker_id = $worker['worker_id'];
                $employee_company_department_id = $worker['worker']['company_department_id'];

                $department_path = "";
                if (!isset($hand_paths[$employee_company_department_id])) {
                    $response = HandbookDepartmentController::GetAllParentsCompaniesWithCompany($employee_company_department_id, $all_companies);               // путь до департамента работника
                    if ($response['status'] == 1) {
                        $department_paths = $response['Items'];
                        $department_path_array = explode(" /", $department_paths);

                        if (count($department_path_array) > 2) {
                            $department_path = $department_path_array[1];
                        } else {
                            $department_path = $department_path_array[0];
                        }
                    }

                    $hand_paths[$employee_company_department_id] = $department_path;
                } else {
                    $department_path = $hand_paths[$employee_company_department_id];
                }

                $employees[$employee_id] = array(
                    'i' => ++$i,
                    'FIO' => Assistant::GetFullName($worker['worker']['employee']['first_name'], $worker['worker']['employee']['patronymic'], $worker['worker']['employee']['last_name']),
                    'employee_id' => $employee_id,
                    'worker_id' => $worker_id,
                    'tabel_number' => $worker['worker']['tabel_number'],
                    'mine_title' => $department_path,
                    'department_title' => $worker['worker']['company']['title'],
                    'position_title' => $worker['worker']['position']['title'],
                    'exam_groups' => array(),
                    'suspension_from_work_by_last_exam' => "",
                    'suspension_from_work_by_percent_successfully' => "",
                    'percent_successfully' => 0
                );
            }

            /** НАХОЖДЕНИЕ ПРОЦЕНТА ПРОХОЖДЕНИЯ ПРЕДСМЕННОГО ТЕСТИРОВАНИЯ ЗА МЕСЯЦ */
            $response = OrderController::GetWorkerOrders($company_department_ids, $date_start, $date_end);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения работников по наряду без экзаменов");
            }

            $all_worker_need_exam = $response['Items'];

            foreach ($all_worker_need_exam as $order) {
                if (!isset($count_workers_exam[$order['employee_id']])) {                                                 // статистика сданных и не сданных экзаменов
                    $count_workers_exam[$order['employee_id']] = array(
                        'good' => 0,        // сдал хорошо
                        'bad' => 0,         // сдал плохо
                        'need' => 0,        // количество раз, которое должен был сдавать по количеству полученных нарядов
                    );
                }
                $count_workers_exam[$order['employee_id']]['need']++;
            }

//            $log->addData($count_workers_exam, '$count_workers_exam', __LINE__);
            if ($employees) {
                foreach ($employees as $key_employee_id => $employee) {

                    $bad_count = ($count_workers_exam[$key_employee_id]['bad'] + $count_workers_exam[$key_employee_id]['need']);
                    $employees[$key_employee_id]['percent_successfully'] = !$bad_count ? 0 : round(($count_workers_exam[$key_employee_id]['good'] / $bad_count) * 100, 2);
                    $employees[$key_employee_id]['good'] = $count_workers_exam[$key_employee_id]['good'];
                    $employees[$key_employee_id]['bad'] = $count_workers_exam[$key_employee_id]['bad'];
                    $employees[$key_employee_id]['need'] = $count_workers_exam[$key_employee_id]['need'];
                    if ($employees[$key_employee_id]['percent_successfully'] < 70) {
                        $employees[$key_employee_id]['suspension_from_work_by_percent_successfully'] = "Требуется провести инструктаж по успеваемости";
                    } else {
                        $employees[$key_employee_id]['suspension_from_work_by_percent_successfully'] = "Не требуется проведение инструктажа по успеваемости";
                    }

                    if (empty($employee['exam_groups'])) {
                        $employees[$key_employee_id]['exam_groups'] = (object)array();
                    }
                }
            }

            $result = $employees;
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    public static function GetSummaryPredExamTwo($data_post)
    {
        $log = new LogAmicumFront("GetSummaryPredExam");
        $result = array();
        $employees = null;

        try {
            $log->addLog("Начал выполнять метод");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'date_start') ||
                !property_exists($post, 'date_end') ||
                !property_exists($post, 'company_department_id')
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            $date_start = Assistant::GetStartProdDateTime($post->date_start);
            $date_end = Assistant::GetEndProdDateTime($post->date_end);
            $company_department_id = $post->company_department_id;
            $company_department_ids = null;

            $log->addData($date_start, '$date_start', __LINE__);
            $log->addData($date_end, '$date_end', __LINE__);

            $filter = [];
            if ($company_department_id) {
                $company_department_ids[] = $company_department_id;
                $response = DepartmentController::FindDepartment($company_department_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка получения вложенных департаментов: ' . $company_department_id);
                }
                $filter = ['company.id' => $response['Items']];
                $company_department_ids = $response['Items'];
            }

            $pred_exams = PredExamHistory::find()
                ->joinWith('employee.workers.company')
                ->joinWith('employee.workers.position')
                ->joinWith('status')
                ->andWhere(['between', "start_test_time", $date_start, $date_end])
//                ->andWhere(['pred_exam_history.employee_id' => 2921329])
                ->andWhere($filter)
                ->orderBy(["pred_exam_history.employee_id" => SORT_ASC, "pred_exam_history.start_test_time" => SORT_ASC])
                ->asArray()
                ->all();

            $all_companies = Company::find()->indexBy('id')->asArray()->all();

            $hand_paths = [];

            /** НАХОДИМ ДВОЙНЫЕ ПРОВАЛЫ ПО СДАЧЕ ПРЕДСМЕННОГО ЭКЗАМЕНАТОРА */
            $i = 0;
            $exam_group_i = 0;
            $workers_filter_briefing = null;
            $first_pred_exam_by_worker = null;                                                                              // первые сдачи по сменам, производственным датам работников
            $log->addData($pred_exams, '$pred_exams', __LINE__);
            foreach ($pred_exams as $pred_exam) {
                $employee_id = $pred_exam['employee_id'];
                $date_time_last = $pred_exam['start_test_time'];
                $shift_obj = Assistant::GetShiftByDateTime($date_time_last);

                if (!isset($count_workers_exam[$employee_id])) {                                                 // статистика сданных и не сданных экзаменов
                    $count_workers_exam[$employee_id] = array(
                        'good' => 0,        // сдал хорошо
                        'bad' => 0,         // сдал плохо
                        'need' => 0,        // количество раз, которое должен был сдавать по количеству полученных нарядов
                    );
                }

                if (isset($pred_exam['employee']) and !isset($first_pred_exam_by_worker[$employee_id][$shift_obj['date_work']][$shift_obj['shift_id']]) and count($pred_exam['employee']['workers']) != 0) {

                    /** ПОЛУЧЕНИЕ ПОСЛЕДНЕГО ПОДРАЗДЕЛЕНИЯ РАБОТНИКА */
                    $number = count($pred_exam['employee']['workers']) - 1;
                    $worker_id = $pred_exam['employee']['workers'][$number]['id'];


                    /** ПОЛУЧЕНИЕ ПУТИ ДО ПОДРАЗДЕЛЕНИЯ РАБОТНИКА */
                    $employee_company_department_id = $pred_exam['employee']['workers'][$number]['company_department_id'];
                    $department_path = "";
                    if (!isset($hand_paths[$employee_company_department_id])) {
                        $response = HandbookDepartmentController::GetAllParentsCompaniesWithCompany($employee_company_department_id, $all_companies);               // путь до департамента работника
                        if ($response['status'] == 1) {
                            $department_paths = $response['Items'];
                            $department_path_array = explode(" /", $department_paths);

                            if (count($department_path_array) > 2) {
                                $department_path = $department_path_array[1];
                            } else {
                                $department_path = $department_path_array[0];
                            }
                        }

                        $hand_paths[$employee_company_department_id] = $department_path;
                    } else {
                        $department_path = $hand_paths[$employee_company_department_id];
                    }

                    /** ФОРМИРОВАНИЕ РЕЗУЛЬТИРУЮЩЕГО МАССИВА ЭКЗАМЕНОВ */
                    if (!isset($employees[$employee_id])) {
                        $employees[$employee_id] = array(
                            'i' => ++$i,
                            'FIO' => Assistant::GetFullName($pred_exam['employee']['first_name'], $pred_exam['employee']['patronymic'], $pred_exam['employee']['last_name']),
                            'employee_id' => $employee_id,
                            'worker_id' => $worker_id,
                            'tabel_number' => $pred_exam['employee']['workers'][$number]['tabel_number'],
                            'mine_title' => $department_path,
                            'department_title' => $pred_exam['employee']['workers'][$number]['company']['title'],
                            'position_title' => $pred_exam['employee']['workers'][$number]['position']['title'],
                            'suspension_from_work_by_last_exam' => "",
                            'suspension_from_work_by_percent_successfully' => "",
                            'percent_successfully' => 0,
                            'exam_groups' => array()
                        );
                    }

                    /** ОПРЕДЕЛЯЕМ СТАТУС СДАЧИ ЭКЗАМЕНА */
                    if ($pred_exam['count_right'] > 1) {
                        $status_id = StatusEnumController::EXAM_DONE;
                        $exam_status_title = "Сдал";
                    } else {
                        $status_id = StatusEnumController::EXAM_NOT_DONE;
                        $exam_status_title = "Не сдал";
                    }

                    /** ЭКЗАМЕН СДАН ПЛОХО */
                    if ($status_id == StatusEnumController::EXAM_NOT_DONE) {
                        if (!isset($exams[$employee_id]['status_bad'])) {
                            $exams[$employee_id] = array(
                                'status_bad' => 0,
                                'bad_exams' => null
                            );
                        }

                        $exams[$employee_id]['status_bad']++;

                        $exams[$employee_id]['bad_exams'][] = array(
                            'start_test_time' => $pred_exam['start_test_time'],
                            'status_title' => $pred_exam['status']['title'],
                            'count_right' => $pred_exam['count_right'],
                            'exam_status_id' => $status_id,
                            'exam_status_title' => $exam_status_title,
                            'count_false' => $pred_exam['count_false'],
                            'question_count' => $pred_exam['question_count'],
                            'sap_id' => $pred_exam['sap_id']
                        );
                        $count_workers_exam[$employee_id]['bad']++;
                    }

                    /** ЭКЗАМЕН СДАН ПЛОХО БОЛЬШЕ ДВУХ РАЗ */
                    if (isset($exams[$employee_id]['status_bad']) and $exams[$employee_id]['status_bad'] > 1) {
                        $employees[$employee_id]['exam_groups'][$exam_group_i] = array(
                            'exams' => $exams[$employee_id]['bad_exams'],
                            'date_time_last' => $date_time_last,
                            'exam_repeat_done' => "",
                            'suspension_from_work' => "",
                            'briefing' => (object)array(),
                            'briefing_date_time' => "",
                            'briefing_status_briefing' => "",
                        );
                        $workers_filter_briefing[$worker_id] = $worker_id;
                    }

                    /** ЭКЗАМЕН СДАН ХОРОШО */
                    if ($status_id == StatusEnumController::EXAM_DONE) {
                        $exam_group_i++;
                        $exams[$employee_id] = array(
                            'status_bad' => 0,
                            'bad_exams' => null
                        );
                        $date_time_exam_done = date("Y-m-d", strtotime($date_time_last));
                        $worker_exams_done[$worker_id . "_" . $date_time_exam_done] = $date_time_exam_done;

                        $count_workers_exam[$employee_id]['good']++;
                    }

                    /** ВЕДЕМ УЧЕТ ПЕРВЫХ СДАЧ ПРЕДСМЕННОГО ЭКЗАМЕНА В СМЕНУ */
                    $first_pred_exam_by_worker[$employee_id][$shift_obj['date_work']][$shift_obj['shift_id']] = array(
                        'status_id' => $status_id,
                        'status_title' => $exam_status_title,
                        'pred_exam' => $pred_exam,
                    );
                }
            }
//            $log->addData($first_pred_exam_by_worker, '$first_pred_exam_by_worker', __LINE__);

            /** НАХОДИМ ИНСТРУКТАЖИ */
            if ($workers_filter_briefing) {
                $response = BriefingController::GetBriefings($workers_filter_briefing, $date_start, $date_end, TypeBriefingEnumController::UNPLANNED);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка получения инструктажей");
                }
                $briefings = $response['Items'];

                foreach ($employees as $key_employee => $employee) {
                    foreach ($employee['exam_groups'] as $key_group => $exam_group) {
                        $date = date("Y-m-d", strtotime($exam_group['date_time_last']));
                        if (isset($briefings[$employee['worker_id'] . "_" . $date])) {
                            $date_time = $briefings[$employee['worker_id'] . "_" . $date]['date_time'];
                            $status_briefing = "Внеплановый инструктаж проведен";
                        } else {
                            $date_time = "";
                            $status_briefing = "Требуется провести внеплановый инструктаж";
                        }
                        $employees[$key_employee]['exam_groups'][$key_group]['briefing'] = (object)array();
                        $employees[$key_employee]['exam_groups'][$key_group]['briefing_date_time'] = $date_time;
                        $employees[$key_employee]['exam_groups'][$key_group]['briefing_status_briefing'] = $status_briefing;

                        if (isset($worker_exams_done[$employee['worker_id'] . "_" . $date])) {
                            $employees[$key_employee]['exam_groups'][$key_group]['exam_repeat_done'] = "Сдал";
                            $employees[$key_employee]['exam_groups'][$key_group]['suspension_from_work'] = "Не требуется";
                            $employees[$key_employee]['suspension_from_work_by_last_exam'] = "Не требуется отстранение от работы";
                        } else {
                            $employees[$key_employee]['exam_groups'][$key_group]['exam_repeat_done'] = "Не сдал";
                            $employees[$key_employee]['exam_groups'][$key_group]['suspension_from_work'] = "Требуется";
                            $employees[$key_employee]['suspension_from_work_by_last_exam'] = "Требуется проведение внепланового инструктажа";
                        }
                    }
                }
            }

            /** НАХОДИМ ТЕХ КТО ВООБЩЕ НЕ СДАВАЛ */
            $response = OrderController::GetWorkersInOrder($company_department_ids, $date_start, $date_end, $workers_filter_briefing);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения работников по наряду без экзаменов");
            }

            $workers_witout_exam = $response['Items'];

            foreach ($workers_witout_exam as $worker) {
                $employee_id = $worker['worker']['employee_id'];
                $worker_id = $worker['worker_id'];
                $employee_company_department_id = $worker['worker']['company_department_id'];

                $department_path = "";
                if (!isset($hand_paths[$employee_company_department_id])) {
                    $response = HandbookDepartmentController::GetAllParentsCompaniesWithCompany($employee_company_department_id, $all_companies);               // путь до департамента работника
                    if ($response['status'] == 1) {
                        $department_paths = $response['Items'];
                        $department_path_array = explode(" /", $department_paths);

                        if (count($department_path_array) > 2) {
                            $department_path = $department_path_array[1];
                        } else {
                            $department_path = $department_path_array[0];
                        }
                    }

                    $hand_paths[$employee_company_department_id] = $department_path;
                } else {
                    $department_path = $hand_paths[$employee_company_department_id];
                }

                $employees[$employee_id] = array(
                    'i' => ++$i,
                    'FIO' => Assistant::GetFullName($worker['worker']['employee']['first_name'], $worker['worker']['employee']['patronymic'], $worker['worker']['employee']['last_name']),
                    'employee_id' => $employee_id,
                    'worker_id' => $worker_id,
                    'tabel_number' => $worker['worker']['tabel_number'],
                    'mine_title' => $department_path,
                    'department_title' => $worker['worker']['company']['title'],
                    'position_title' => $worker['worker']['position']['title'],
                    'exam_groups' => array(),
                    'suspension_from_work_by_last_exam' => "",
                    'suspension_from_work_by_percent_successfully' => "",
                    'percent_successfully' => 0
                );
            }

            /** НАХОЖДЕНИЕ ПРОЦЕНТА ПРОХОЖДЕНИЯ ПРЕДСМЕННОГО ТЕСТИРОВАНИЯ ЗА МЕСЯЦ */
            $response = OrderController::GetWorkerOrders($company_department_ids, $date_start, $date_end);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения работников по наряду без экзаменов");
            }

            $all_worker_need_exam = $response['Items'];

            foreach ($all_worker_need_exam as $order) {
                if (!isset($count_workers_exam[$order['employee_id']])) {                                                 // статистика сданных и не сданных экзаменов
                    $count_workers_exam[$order['employee_id']] = array(
                        'good' => 0,        // сдал хорошо
                        'bad' => 0,         // сдал плохо
                        'need' => 0,        // количество раз, которое должен был сдавать по количеству полученных нарядов
                    );
                }
                $count_workers_exam[$order['employee_id']]['need']++;
            }

//            $log->addData($count_workers_exam, '$count_workers_exam', __LINE__);
            if ($employees) {
                foreach ($employees as $key_employee_id => $employee) {

                    $bad_count = ($count_workers_exam[$key_employee_id]['bad'] + $count_workers_exam[$key_employee_id]['need']);
                    $employees[$key_employee_id]['percent_successfully'] = !$bad_count ? 0 : round(($count_workers_exam[$key_employee_id]['good'] / $bad_count) * 100, 2);
                    $employees[$key_employee_id]['good'] = $count_workers_exam[$key_employee_id]['good'];
                    $employees[$key_employee_id]['bad'] = $count_workers_exam[$key_employee_id]['bad'];
                    $employees[$key_employee_id]['need'] = $count_workers_exam[$key_employee_id]['need'];
                    if ($employees[$key_employee_id]['percent_successfully'] < 70) {
                        $employees[$key_employee_id]['suspension_from_work_by_percent_successfully'] = "Требуется провести инструктаж по успеваемости";
                    } else {
                        $employees[$key_employee_id]['suspension_from_work_by_percent_successfully'] = "Не требуется проведение инструктажа по успеваемости";
                    }

                    if (empty($employee['exam_groups'])) {
                        $employees[$key_employee_id]['exam_groups'] = (object)array();
                    }
                }
            }

            $result = $employees;
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SendEmailReportSummaryPredExam - Отправка сводных сведений о прохождении предсменного тестирования на электронную почту
     * @param $data_post
     * @return array|array[]
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=SendEmailReportSummaryPredExam&subscribe=&data={}
     */
    public static function SendEmailReportSummaryPredExam($data_post = null)
    {
        $log = new LogAmicumFront("SendEmailReportSummaryPredExam");
        $result = array();
        $models = array();

        try {
            /** ФОРМИРУЕМ ПЕРИОД СОСТАВЛЕНИЯ ОТЧЕТА */
//            $date_end = Assistant::GetDateTimeNow();
//            $date_start = date("Y-m-d", strtotime(Assistant::GetDateTimeNow() . "-30days"));
            $date_now = Assistant::GetDateTimeNow();
            $dates = Assistant::GetFirstAndLastDayInDate($date_now);
            $date_end = $dates['date_end'];
            $date_start = $dates['date_start'];

            /** ПОЛУЧАЕМ ДАННЫЕ ДЛЯ ПОСТРОЕНИЯ ОТЧЕТА */
            $response = self::GetSummaryPredExam(json_encode(array(
                'date_start' => $date_start,
                'date_end' => $date_end,
                'company_department_id' => null,
            )));
//            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception("Ошибка получения данных для отчета");
            }

            $workers_exam = $response['Items'];

            $position_array = array(
                'Специалист',
                'Начальник отдела',
                'Взрывник подземный',
                'Горномонтажник подземный',
                'Горнорабочий на маркшейдерских работах',
                'Горнорабочий очистного забоя',
                'Горнорабочий по ремонту горных выработок',
                'Горнорабочий подземный',
                'Машинист буровой установки подземный',
                'Машинист горновыемочных машин',
                'Машинист подземных установок',
                'Машинист подъемной машины подземный',
                'Машинист электровоза шахтового подземный',
                'Проходчик',
                'Раздатчик взрывчатых материалов',
                'Стволовой подземный',
                'Электрослесарь подземный',
            );

            if ($workers_exam) {
                $j = 0;
                foreach ($workers_exam as $worker) {
                    if (array_search($worker['position_title'], $position_array)) {
                        $j++;

                        if ($worker['exam_groups'] and !empty($worker['exam_groups']) and $worker['percent_successfully'] <= 70) {
                            $models[$j] = [
                                'i' => $worker['i'],
                                'FIO' => $worker['FIO'],
                                'tabel_number' => $worker['tabel_number'],
                                'mine_title' => $worker['mine_title'],
                                'department_title' => $worker['department_title'],
                                'position_title' => $worker['position_title'],
                                'suspension_from_work_by_last_exam' => $worker['suspension_from_work_by_last_exam'],
                                'suspension_from_work_by_percent_successfully' => $worker['suspension_from_work_by_percent_successfully'],
                                'percent_successfully' => $worker['percent_successfully'],
                                'date_time_last' => "",
                                'briefing_date_time' => "",
                                'briefing_status_briefing' => "",
                                'exam_repeat_done' => "",
                                'suspension_from_work' => "",
                                'start_test_time' => "",
                                'status_title' => "",
                                'count_right' => "",
                                'exam_status_id' => "",
                                'exam_status_title' => "",
                                'count_false' => "",
                                'question_count' => "",
                                'sap_id' => "",
                            ];

                            foreach ($worker['exam_groups'] as $exam_group) {
                                $models[$j]['date_time_last'] = $exam_group['date_time_last'] ? date("d.m.Y H:i:s", strtotime($exam_group['date_time_last'])) : "";
                                $models[$j]['briefing_date_time'] = $exam_group['briefing_date_time'] ? date("d.m.Y H:i:s", strtotime($exam_group['briefing_date_time'])) : "";
                                $models[$j]['briefing_status_briefing'] = $exam_group['briefing_status_briefing'];
                                $models[$j]['exam_repeat_done'] = $exam_group['exam_repeat_done'];
                                $models[$j]['suspension_from_work'] = $exam_group['suspension_from_work'];

                                foreach ($exam_group['exams'] as $exam) {
                                    $models[$j] = [
                                        'i' => $worker['i'],
                                        'FIO' => $worker['FIO'],
                                        'tabel_number' => $worker['tabel_number'],
                                        'mine_title' => $worker['mine_title'],
                                        'department_title' => $worker['department_title'],
                                        'position_title' => $worker['position_title'],
                                        'suspension_from_work_by_last_exam' => $worker['suspension_from_work_by_last_exam'],
                                        'suspension_from_work_by_percent_successfully' => $worker['suspension_from_work_by_percent_successfully'],
                                        'percent_successfully' => $worker['percent_successfully'],
                                    ];

                                    $models[$j]['date_time_last'] = $exam_group['date_time_last'] ? date("d.m.Y H:i:s", strtotime($exam_group['date_time_last'])) : "";
                                    $models[$j]['briefing_date_time'] = $exam_group['briefing_date_time'] ? date("d.m.Y H:i:s", strtotime($exam_group['briefing_date_time'])) : "";
                                    $models[$j]['briefing_status_briefing'] = $exam_group['briefing_status_briefing'];
                                    $models[$j]['exam_repeat_done'] = $exam_group['exam_repeat_done'];
                                    $models[$j]['suspension_from_work'] = $exam_group['suspension_from_work'];

                                    $models[$j]['start_test_time'] = $exam['start_test_time'] ? date("d.m.Y H:i:s", strtotime($exam['start_test_time'])) : "";
                                    $models[$j]['status_title'] = $exam['status_title'];
                                    $models[$j]['count_right'] = $exam['count_right'];
                                    $models[$j]['exam_status_id'] = $exam['exam_status_id'];
                                    $models[$j]['exam_status_title'] = $exam['exam_status_title'];
                                    $models[$j]['count_false'] = $exam['count_false'];
                                    $models[$j]['question_count'] = $exam['question_count'];
                                    $models[$j]['sap_id'] = $exam['sap_id'];
                                    $j++;
                                }
                            }

                        } else if (($worker['good'] + $worker['bad']) == 0 && $worker['need'] > 0) {
                            $models[$j] = [
                                'i' => $worker['i'],
                                'FIO' => $worker['FIO'],
                                'tabel_number' => $worker['tabel_number'],
                                'mine_title' => $worker['mine_title'],
                                'department_title' => $worker['department_title'],
                                'position_title' => $worker['position_title'],
                                'suspension_from_work_by_last_exam' => $worker['suspension_from_work_by_last_exam'],
                                'suspension_from_work_by_percent_successfully' => $worker['suspension_from_work_by_percent_successfully'],
                                'percent_successfully' => $worker['percent_successfully'],
                                'date_time_last' => "Не сдавал ни разу предсменное тестирование",
                                'briefing_date_time' => "",
                                'briefing_status_briefing' => "",
                                'exam_repeat_done' => "",
                                'suspension_from_work' => "",
                                'start_test_time' => "",
                                'status_title' => "",
                                'count_right' => "",
                                'exam_status_id' => "",
                                'exam_status_title' => "",
                                'count_false' => "",
                                'question_count' => "",
                                'sap_id' => "",
                            ];
                        }
                    }
                }
            }

            $file_name = 'summary_pred_exam.xlsx';

            if (PHP_OS == "Linux") {
                $upload_dir = '/var/www/html/amicum/frontend/web/mines-excel';                                          //объявляем и инициируем переменную для хранения пути к папке с файлом
                $path = $upload_dir . "/" . $file_name;
            } else {
                $upload_dir = 'C:\xampp\htdocs\amicum\frontend\web\mines-excel';
                $path = $upload_dir . "\\" . $file_name;
            }


            Excel::export([
                'savePath' => $upload_dir,
                'fileName' => $file_name,
//                'autoSize' => true,
                'format' => 'Xlsx',
                'isMultipleSheet' => false,
                'models' => $models,
                'columns' => [
//                    'i',
                    [
                        'attribute' => 'FIO',
                        'width' => '35.14',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'tabel_number',
                        'width' => '17.57',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'mine_title',
                        'width' => '29.14',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'department_title',
                        'width' => '35.71',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'position_title',
                        'width' => '32.43',
                        'wrap' => true
                    ],

                    [
                        'attribute' => 'suspension_from_work_by_last_exam',
                        'width' => '28.29',
                        'format' => 'text',
                        'wrap' => true
                    ],
//                    'suspension_from_work_by_percent_successfully',
                    [
                        'attribute' => 'percent_successfully',
                        'width' => '16.14',
                        'format' => 'text',
                        'wrap' => true
                    ],


                    [
                        'attribute' => 'briefing_date_time',
                        'width' => '29',
                        'format' => 'text',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'briefing_status_briefing',
                        'width' => '29.43',
                        'format' => 'text',
                        'wrap' => true
                    ],
                    //                    'date_time_last',
                    [
                        'attribute' => 'exam_repeat_done',
                        'width' => '30.57',
                        'format' => 'text',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'suspension_from_work',
                        'width' => '52',
                        'format' => 'text',
                        'wrap' => true
                    ],


                    [
                        'attribute' => 'start_test_time',
                        'width' => '19.71',
                        'format' => 'text',
                        'wrap' => true
                    ],
//                    'status_title',
                    [
                        'attribute' => 'count_right',
                        'width' => '21',
                        'format' => 'text',
                        'wrap' => true
                    ],
//                    'exam_status_id',
                    [
                        'attribute' => 'exam_status_title',
                        'width' => '18.57',
                        'format' => 'text',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'count_false',
                        'width' => '12.71',
                        'format' => 'text',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'question_count',
                        'width' => '13.43',
                        'format' => 'text',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'sap_id',
                        'width' => '11.43',
                        'format' => 'text',
                        'wrap' => true
                    ],
                ],
                'headers' => [
//                    'i' => '№ п/п',
                    'FIO' => 'ФИО работника',
                    'tabel_number' => 'Табельный номер',
                    'mine_title' => 'Название шахты',
                    'department_title' => 'Название подразделения',
                    'position_title' => 'Название должности',

                    'suspension_from_work_by_last_exam' => 'Итоговый на основе последнего прохождения',
//                    'suspension_from_work_by_percent_successfully' => 'Итоговый на основании успеваемости',
                    'percent_successfully' => 'Успеваемость, %',


                    'briefing_date_time' => 'Дата и время проведения внепланового инструктажа',
                    'briefing_status_briefing' => 'Был или нет проведен инструктаж',
                    //                    'date_time_last' => 'Последняя дата провального тестирования',
                    'exam_repeat_done' => 'Результат тестирования после внепланового инструктажа',
                    'suspension_from_work' => 'Результат необходимости отстранения от работы по результатам текущей группы провальных тестов',

                    'start_test_time' => 'Дата и время начала тестирования',
//                    'status_title' => 'Статус процедуры тестирования',
                    'count_right' => 'Количество правильных ответов',
//                    'exam_status_id' => 'Ключ статус экзамена',
                    'exam_status_title' => 'Название статуса экзамена',
                    'count_false' => 'Количество ошибок',
                    'question_count' => 'Количество вопросов',
                    'sap_id' => 'Ключ из Квазара',
                ],
            ]);

            $response = XmlController::SendSafetyEmailWithAttach(
                "Сводная аналитика прохождения предсменного экзаменатора",
                "Документ во вложении",
                //['IshkovVS@uk.mechel.com', 'ProkopevaEO@uk.mechel.com'],
                ['artamonoviv@uk.mechel.com'],
                $path);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception("Ошибка отправки отчета");
            }

        } catch (Throwable $ex) {

            $log->addError($ex->getMessage(), $ex->getLine());
        }


        #Второй отчет
        $result = array();
        $models = array();

        try {
            /** ФОРМИРУЕМ ПЕРИОД СОСТАВЛЕНИЯ ОТЧЕТА */
//            $date_end = Assistant::GetDateTimeNow();
//            $date_start = date("Y-m-d", strtotime(Assistant::GetDateTimeNow() . "-30days"));
            $date_now = Assistant::GetDateTimeNow();
            $dates = Assistant::GetFirstAndLastDayInDate($date_now);
            $date_end = $dates['date_end'];
            $date_start = $dates['date_start'];

            /** ПОЛУЧАЕМ ДАННЫЕ ДЛЯ ПОСТРОЕНИЯ ОТЧЕТА */
            $response = self::GetSummaryPredExamTwo(json_encode(array(
                'date_start' => $date_start,
                'date_end' => $date_end,
                'company_department_id' => null,
            )));
//            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception("Ошибка получения данных для отчета");
            }

            $workers_exam = $response['Items'];

            $position_array = array(
                'Специалист',
                'Начальник отдела',
                'Взрывник подземный',
                'Горномонтажник подземный',
                'Горнорабочий на маркшейдерских работах',
                'Горнорабочий очистного забоя',
                'Горнорабочий по ремонту горных выработок',
                'Горнорабочий подземный',
                'Машинист буровой установки подземный',
                'Машинист горновыемочных машин',
                'Машинист подземных установок',
                'Машинист подъемной машины подземный',
                'Машинист электровоза шахтового подземный',
                'Проходчик',
                'Раздатчик взрывчатых материалов',
                'Стволовой подземный',
                'Электрослесарь подземный',
            );

            if ($workers_exam) {
                $j = 0;
                foreach ($workers_exam as $worker) {
                    if (array_search($worker['position_title'], $position_array)) {
                        $j++;

                        if ($worker['exam_groups'] and !empty($worker['exam_groups']) and $worker['percent_successfully'] <= 70) {
                            $models[$j] = [
                                'i' => $worker['i'],
                                'FIO' => $worker['FIO'],
                                'tabel_number' => $worker['tabel_number'],
                                'mine_title' => $worker['mine_title'],
                                'department_title' => $worker['department_title'],
                                'position_title' => $worker['position_title'],
                                'suspension_from_work_by_last_exam' => $worker['suspension_from_work_by_last_exam'],
                                'suspension_from_work_by_percent_successfully' => $worker['suspension_from_work_by_percent_successfully'],
                                'percent_successfully' => $worker['percent_successfully'],
                                'date_time_last' => "",
                                'briefing_date_time' => "",
                                'briefing_status_briefing' => "",
                                'exam_repeat_done' => "",
                                'suspension_from_work' => "",
                                'start_test_time' => "",
                                'status_title' => "",
                                'count_right' => "",
                                'exam_status_id' => "",
                                'exam_status_title' => "",
                                'count_false' => "",
                                'question_count' => "",
                                'sap_id' => "",
                            ];

                            foreach ($worker['exam_groups'] as $exam_group) {
                                $models[$j]['date_time_last'] = $exam_group['date_time_last'] ? date("d.m.Y H:i:s", strtotime($exam_group['date_time_last'])) : "";
                                $models[$j]['briefing_date_time'] = $exam_group['briefing_date_time'] ? date("d.m.Y H:i:s", strtotime($exam_group['briefing_date_time'])) : "";
                                $models[$j]['briefing_status_briefing'] = $exam_group['briefing_status_briefing'];
                                $models[$j]['exam_repeat_done'] = $exam_group['exam_repeat_done'];
                                $models[$j]['suspension_from_work'] = $exam_group['suspension_from_work'];

                                foreach ($exam_group['exams'] as $exam) {
                                    $models[$j] = [
                                        'i' => $worker['i'],
                                        'FIO' => $worker['FIO'],
                                        'tabel_number' => $worker['tabel_number'],
                                        'mine_title' => $worker['mine_title'],
                                        'department_title' => $worker['department_title'],
                                        'position_title' => $worker['position_title'],
                                        'suspension_from_work_by_last_exam' => $worker['suspension_from_work_by_last_exam'],
                                        'suspension_from_work_by_percent_successfully' => $worker['suspension_from_work_by_percent_successfully'],
                                        'percent_successfully' => $worker['percent_successfully'],
                                    ];

                                    $models[$j]['date_time_last'] = $exam_group['date_time_last'] ? date("d.m.Y H:i:s", strtotime($exam_group['date_time_last'])) : "";
                                    $models[$j]['briefing_date_time'] = $exam_group['briefing_date_time'] ? date("d.m.Y H:i:s", strtotime($exam_group['briefing_date_time'])) : "";
                                    $models[$j]['briefing_status_briefing'] = $exam_group['briefing_status_briefing'];
                                    $models[$j]['exam_repeat_done'] = $exam_group['exam_repeat_done'];
                                    $models[$j]['suspension_from_work'] = $exam_group['suspension_from_work'];

                                    $models[$j]['start_test_time'] = $exam['start_test_time'] ? date("d.m.Y H:i:s", strtotime($exam['start_test_time'])) : "";
                                    $models[$j]['status_title'] = $exam['status_title'];
                                    $models[$j]['count_right'] = $exam['count_right'];
                                    $models[$j]['exam_status_id'] = $exam['exam_status_id'];
                                    $models[$j]['exam_status_title'] = $exam['exam_status_title'];
                                    $models[$j]['count_false'] = $exam['count_false'];
                                    $models[$j]['question_count'] = $exam['question_count'];
                                    $models[$j]['sap_id'] = $exam['sap_id'];
                                    $j++;
                                }
                            }

                        } else if (($worker['good'] + $worker['bad']) == 0 && $worker['need'] > 0) {
                            $models[$j] = [
                                'i' => $worker['i'],
                                'FIO' => $worker['FIO'],
                                'tabel_number' => $worker['tabel_number'],
                                'mine_title' => $worker['mine_title'],
                                'department_title' => $worker['department_title'],
                                'position_title' => $worker['position_title'],
                                'suspension_from_work_by_last_exam' => $worker['suspension_from_work_by_last_exam'],
                                'suspension_from_work_by_percent_successfully' => $worker['suspension_from_work_by_percent_successfully'],
                                'percent_successfully' => $worker['percent_successfully'],
                                'date_time_last' => "Не сдавал ни разу предсменное тестирование",
                                'briefing_date_time' => "",
                                'briefing_status_briefing' => "",
                                'exam_repeat_done' => "",
                                'suspension_from_work' => "",
                                'start_test_time' => "",
                                'status_title' => "",
                                'count_right' => "",
                                'exam_status_id' => "",
                                'exam_status_title' => "",
                                'count_false' => "",
                                'question_count' => "",
                                'sap_id' => "",
                            ];
                        }
                    }
                }
            }

            $file_name = 'summary_pred_exam2.xlsx';

            if (PHP_OS == "Linux") {
                $upload_dir = '/var/www/html/amicum/frontend/web/mines-excel';                                          //объявляем и инициируем переменную для хранения пути к папке с файлом
                $path = $upload_dir . "/" . $file_name;
            } else {
                $upload_dir = 'C:\xampp\htdocs\amicum\frontend\web\mines-excel';
                $path = $upload_dir . "\\" . $file_name;
            }


            Excel::export([
                'savePath' => $upload_dir,
                'fileName' => $file_name,
//                'autoSize' => true,
                'format' => 'Xlsx',
                'isMultipleSheet' => false,
                'models' => $models,
                'columns' => [
//                    'i',
                    [
                        'attribute' => 'FIO',
                        'width' => '35.14',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'tabel_number',
                        'width' => '17.57',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'mine_title',
                        'width' => '29.14',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'department_title',
                        'width' => '35.71',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'position_title',
                        'width' => '32.43',
                        'wrap' => true
                    ],

                    [
                        'attribute' => 'suspension_from_work_by_last_exam',
                        'width' => '28.29',
                        'format' => 'text',
                        'wrap' => true
                    ],
//                    'suspension_from_work_by_percent_successfully',
                    [
                        'attribute' => 'percent_successfully',
                        'width' => '16.14',
                        'format' => 'text',
                        'wrap' => true
                    ],


                    [
                        'attribute' => 'briefing_date_time',
                        'width' => '29',
                        'format' => 'text',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'briefing_status_briefing',
                        'width' => '29.43',
                        'format' => 'text',
                        'wrap' => true
                    ],
                    //                    'date_time_last',
                    [
                        'attribute' => 'exam_repeat_done',
                        'width' => '30.57',
                        'format' => 'text',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'suspension_from_work',
                        'width' => '52',
                        'format' => 'text',
                        'wrap' => true
                    ],


                    [
                        'attribute' => 'start_test_time',
                        'width' => '19.71',
                        'format' => 'text',
                        'wrap' => true
                    ],
//                    'status_title',
                    [
                        'attribute' => 'count_right',
                        'width' => '21',
                        'format' => 'text',
                        'wrap' => true
                    ],
//                    'exam_status_id',
                    [
                        'attribute' => 'exam_status_title',
                        'width' => '18.57',
                        'format' => 'text',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'count_false',
                        'width' => '12.71',
                        'format' => 'text',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'question_count',
                        'width' => '13.43',
                        'format' => 'text',
                        'wrap' => true
                    ],
                    [
                        'attribute' => 'sap_id',
                        'width' => '11.43',
                        'format' => 'text',
                        'wrap' => true
                    ],
                ],
                'headers' => [
//                    'i' => '№ п/п',
                    'FIO' => 'ФИО работника',
                    'tabel_number' => 'Табельный номер',
                    'mine_title' => 'Название шахты',
                    'department_title' => 'Название подразделения',
                    'position_title' => 'Название должности',

                    'suspension_from_work_by_last_exam' => 'Итоговый на основе последнего прохождения',
//                    'suspension_from_work_by_percent_successfully' => 'Итоговый на основании успеваемости',
                    'percent_successfully' => 'Успеваемость, %',


                    'briefing_date_time' => 'Дата и время проведения внепланового инструктажа',
                    'briefing_status_briefing' => 'Был или нет проведен инструктаж',
                    //                    'date_time_last' => 'Последняя дата провального тестирования',
                    'exam_repeat_done' => 'Результат тестирования после внепланового инструктажа',
                    'suspension_from_work' => 'Результат необходимости отстранения от работы по результатам текущей группы провальных тестов',

                    'start_test_time' => 'Дата и время начала тестирования',
//                    'status_title' => 'Статус процедуры тестирования',
                    'count_right' => 'Количество правильных ответов',
//                    'exam_status_id' => 'Ключ статус экзамена',
                    'exam_status_title' => 'Название статуса экзамена',
                    'count_false' => 'Количество ошибок',
                    'question_count' => 'Количество вопросов',
                    'sap_id' => 'Ключ из Квазара',
                ],
            ]);

            $response = XmlController::SendSafetyEmailWithAttach(
                "Сводная аналитика прохождения предсменного экзаменатора",
                "Документ во вложении",
                ['artamonoviv@uk.mechel.com'],
                $path);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception("Ошибка отправки отчета");
            }

        } catch (Throwable $ex) {

            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveExaminationAttachment - Метод сохранения вложения для тестирования
     * Входной объект:
     *      "examination_attachment":{
     *          "attachment_id":-1,
     *          "examination_id":1,
     *          "examination_attachment_id":-2,
     *          "attachment_status":"new", - так же "del", "update"
     *          "attachment_path":"",
     *          "attachment_title":"",
     *          "attachment_type":"png",
     *          "attachment_blob":""
     *      }
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=SaveExaminationAttachment&subscribe=&data={}
     */
    public static function SaveExaminationAttachment($data_post)
    {
        $log = new LogAmicumFront("SaveExaminationAttachment");
        $result = null;

        try {
            $log->addLog("Начал выполнять метод");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'examination_attachment') ||
                !property_exists($post->examination_attachment, 'attachment_id') || $post->examination_attachment->attachment_id == '' ||
                !property_exists($post->examination_attachment, 'examination_id') || $post->examination_attachment->examination_id == '' ||
                !property_exists($post->examination_attachment, 'examination_attachment_id') || $post->examination_attachment->examination_attachment_id == '' ||
                !property_exists($post->examination_attachment, 'attachment_status') || $post->examination_attachment->attachment_status == '' ||
                !property_exists($post->examination_attachment, 'attachment_title') || $post->examination_attachment->attachment_title == '' ||
                !property_exists($post->examination_attachment, 'attachment_type') || $post->examination_attachment->attachment_type == '' ||
                !property_exists($post->examination_attachment, 'attachment_blob') || $post->examination_attachment->attachment_blob == ''
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            $examination_attachment = $post->examination_attachment;

            $log->addLog("Получил все входные параметры");

            if (!Examination::findOne(['id' => $examination_attachment->examination_id])) {
                throw new Exception("Тестирование $examination_attachment->examination_id не найдено");
            }

            if ($examination_attachment->attachment_status == "del") {
                ExaminationAttachment::deleteAll(['id' => $examination_attachment->examination_attachment_id]);
                $examination_attachment->examination_attachment_id = null;

            } else {
                $session = Yii::$app->session;
                $model_examination_attachment = ExaminationAttachment::findOne(['id' => $examination_attachment->examination_attachment_id]);
                $model_attachment = Attachment::findOne(['id' => $examination_attachment->attachment_id]);
                if (!$model_attachment || $examination_attachment->attachment_status == "new") {
                    $model_attachment = new Attachment();
                    $examination_attachment->attachment_path = Assistant::UploadFile(
                        $examination_attachment->attachment_blob,
                        $examination_attachment->attachment_title,
                        'attachment'
                    );
                    $examination_attachment->attachment_blob = null;
                    $model_attachment->path = $examination_attachment->attachment_path;
                    $model_attachment->date = BackendAssistant::GetDateFormatYMD();
                    $model_attachment->worker_id = $session['worker_id'];
                    $model_attachment->title = $examination_attachment->attachment_title;
                    $model_attachment->attachment_type = $examination_attachment->attachment_type;
                    $model_attachment->section_title = "Экзаменатор";
                    if (!$model_attachment->save()) {
                        $log->addData($model_attachment->errors, '$model_attachment->errors', __LINE__);
                        throw new Exception("Ошибка сохранения модели Attachment");
                    }
                    $examination_attachment->attachment_id = $model_attachment->id;
                    $log->addLog('Вложение успешно сохранено');
                }

                if (!$model_examination_attachment) {
                    $model_examination_attachment = new ExaminationAttachment();
                }
                $model_examination_attachment->attachment_id = $examination_attachment->attachment_id;
                $model_examination_attachment->examination_id = $examination_attachment->examination_id;
                if (!$model_examination_attachment->save()) {
                    $log->addData($model_examination_attachment->errors, '$model_examination_attachment->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели ExaminationAttachment");
                }
                $examination_attachment->examination_attachment_id = $model_attachment->id;
                $log->addLog('Модель ExaminationAttachment успешно сохранена');
            }

            $result['examination_attachment'] = $examination_attachment;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetExaminationAttachmentList - Метод получения списка вложений тестирования
     * Входной объект: {"examination_id":1}
     * Выходной объект:
     *      "2":{
     *          "attachment_id":10,
     *          "examination_id":1,
     *          "examination_attachment_id":2,
     *          "attachment_path":"",
     *          "attachment_title":"",
     *          "attachment_type":"png"
     *      },
     *      "...":
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=GetExaminationAttachmentList&subscribe=&data={"examination_id":1}
     */
    public static function GetExaminationAttachmentList($data_post)
    {
        $log = new LogAmicumFront("GetExaminationAttachmentList");
        $result = null;

        try {
            $log->addLog("Начал выполнять метод");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'examination_id') || $post->examination_id == ''
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            $examination_id = $post->examination_id;

            $log->addLog("Получил все входные параметры");

            $model_examination_attachments = ExaminationAttachment::find()
                ->joinWith('attachment')
                ->where(['examination_id' => $examination_id])
                ->all();

            foreach ($model_examination_attachments as $model_examination_attachment) {
                $examination_attachment['attachment_id'] = $model_examination_attachment->attachment_id;
                $examination_attachment['examination_id'] = $model_examination_attachment->examination_id;
                $examination_attachment['examination_attachment_id'] = $model_examination_attachment->id;
                $examination_attachment['attachment_path'] = $model_examination_attachment->attachment->path;
                $examination_attachment['attachment_title'] = $model_examination_attachment->attachment->title;
                $examination_attachment['attachment_type'] = $model_examination_attachment->attachment->attachment_type;
                $result[$model_examination_attachment->id] = $examination_attachment;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetDocumentParagraphPbList - Метод получения списка документов с их пунктами
     * Входной объект: {}
     * Выходной объект:
     *      "[document_id]":{
     *          "id":1,
     *          "document_title":"",
     *          "parent_document_id":1,
     *          "paragraphs_pb":[
     *              {
     *                  "id":1,
     *                  "document_id": 37,
     *                  "text": "п. 473",
     *                  "description": "п. 473",
     *              },
     *              ...
     *          ]
     *      },
     *      "...":
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=GetDocumentParagraphPbList&subscribe=&data={}
     */
    public static function GetDocumentParagraphPbList()
    {
        $log = new LogAmicumFront("GetDocumentParagraphPbList");
        $result = null;

        try {
            $log->addLog("Начал выполнять метод");

            $documents = Document::find()
                ->select(['document.id', 'document.title', 'document.parent_document_id'])
                ->joinWith('paragraphPbs')
                ->where(['vid_document_id' => VidDocumentEnumController::NORMATIVE_DOCUMENT])
                ->asArray()
                ->all();

            $log->addLog("Получил данные");

            foreach ($documents as $doc) {
                $result[$doc['id']] = array(
                    'id' => $doc['id'],
                    'document_title' => $doc['title'],
                    'parent_document_id' => $doc['parent_document_id'],
                    'paragraphs_pb' => $doc['paragraphPbs']
                );
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveMediaGroup - Метод сохранения группы медиа
     * Входной объект:
     *      {
     *          "media_group_id":1,         - идентификатор группы медиа
     *          "media_group_title":"",     - название группы медиа
     *          "flag_delete":1             - не обязателен если 1 удаляет группу
     *      }
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=SaveMediaGroup&subscribe=&data={"media_group_id":1,"media_group_title":"test"}
     */
    public static function SaveMediaGroup($data_post)
    {
        $log = new LogAmicumFront("SaveMediaGroup");
        $result = null;

        try {
            $log->addLog("Начал выполнять метод");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'media_group_id') || $post->media_group_id == '' ||
                !property_exists($post, 'media_group_title') || $post->media_group_title == ''
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            if (isset($post->media_group_title) && $post->media_group_title != '') {
                $media_group_title = $post->media_group_title;
            } else {
                $media_group_title = 'Новая группа';
            }

            if (isset($post->flag_delete) && $post->flag_delete == 1) {
                $flag_delete = true;
            } else {
                $flag_delete = false;
            }

            $media_group_id = $post->media_group_id;

            $log->addLog("Получил все входные параметры");

            if ($flag_delete) {
                MediaGroup::deleteAll(['id' => $media_group_id]);
                $result['media_group_id'] = $media_group_id;
            } else {
                $model_media_group = MediaGroup::findOne(['id' => $media_group_id]);

                if (!$model_media_group) {
                    $model_media_group = new MediaGroup();
                }
                $model_media_group->title = $media_group_title;
                if (!$model_media_group->save()) {
                    $log->addData($model_media_group->errors, '$model_media_group->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели MediaGroup");
                }
                $result['media_group_id'] = $model_media_group->id;
                $result['media_group_title'] = $model_media_group->title;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveMedia - Метод сохранения медиа
     * Входной объект:
     *      "media":{
     *          "media_id":1,
     *          "media_group_id":2,
     *          "attachment_id":1,
     *          "attachment_status":"new",  - так же "del", "update"
     *          "attachment_path":"",
     *          "attachment_title":"",
     *          "attachment_type":"png",
     *          "attachment_blob":""
     *          "media_themes":[
     *              {
     *                  "id":1,
     *                  "media_theme_id":1,
     *                  "flag_del":0,       - 1 удалит связь
     *              }
     *              ...
     *          ]
     *      }
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=SaveMedia&subscribe=&data={}
     */
    public static function SaveMedia($data_post)
    {
        $log = new LogAmicumFront("SaveMedia");
        $result = null;

        try {
            $log->addLog("Начал выполнять метод");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'media') ||
                !property_exists($post->media, 'media_id') || $post->media->media_id == '' ||
                !property_exists($post->media, 'media_group_id') || $post->media->media_group_id == '' ||
                !property_exists($post->media, 'attachment_id') || $post->media->attachment_id == '' ||
                !property_exists($post->media, 'attachment_status') || $post->media->attachment_status == '' ||
                !property_exists($post->media, 'attachment_title') || $post->media->attachment_title == '' ||
                !property_exists($post->media, 'attachment_type') || $post->media->attachment_type == '' ||
                !property_exists($post->media, 'attachment_blob') || $post->media->attachment_blob == ''
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            $media = $post->media;

            $log->addLog("Получил все входные параметры");

            if (!MediaGroup::findOne(['id' => $media->media_group_id])) {
                throw new Exception("Группы $media->media_group_id не найдено");
            }

            if ($media->attachment_status == "del") {
                Media::deleteAll(['id' => $media->media_id]);
                $media->media_id = null;
            } else {
                $model_media = Media::findOne(['id' => $media->media_id]);
                $model_attachment = Attachment::findOne(['id' => $media->attachment_id]);
                if (!$model_attachment || $media->attachment_status == "new") {
                    $session = Yii::$app->session;
                    $model_attachment = new Attachment();
                    $media->attachment_path = Assistant::UploadFile(
                        $media->attachment_blob,
                        $media->attachment_title,
                        'attachment'
                    );
                    $media->attachment_blob = null;
                    $model_attachment->path = $media->attachment_path;
                    $model_attachment->section_title = "Медиа";
                    $model_attachment->date = BackendAssistant::GetDateFormatYMD();
                    $model_attachment->worker_id = $session['worker_id'];
                }
                $model_attachment->title = $media->attachment_title;
                $model_attachment->attachment_type = $media->attachment_type;
                if (!$model_attachment->save()) {
                    $log->addData($model_attachment->errors, '$model_attachment->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели Attachment");
                }
                $media->attachment_id = $model_attachment->id;
                $log->addLog('Вложение успешно сохранено');

                if (!$model_media) {
                    $model_media = new Media();
                }
                $model_media->attachment_id = $media->attachment_id;
                $model_media->media_group_id = $media->media_group_id;
                if (!$model_media->save()) {
                    $log->addData($model_media->errors, '$model_media->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели Media");
                }
                $media->media_id = $model_media->id;
                $log->addLog('Модель Media успешно сохранена');

                if (isset($media->media_themes)) {
                    foreach ($media->media_themes as $media_theme) {
                        if ($media_theme->flag_del == 1) {
                            MediaMediaTheme::deleteAll(['id' => $media_theme->id]);
                            unset($media_theme);
                        } else {
                            $model_m_theme = MediaTheme::findOne(['id' => $media_theme->media_theme_id]);
//                            if (!$model_m_theme) {
//                                $model_m_theme = new MediaTheme();
//                            }
//                            $model_m_theme->title = $media_theme->title;
//                            if (!$model_m_theme->save()) {
//                                $log->addData($model_m_theme->errors, '$model_m_theme->errors', __LINE__);
//                                throw new Exception("Ошибка сохранения модели MediaTheme");
//                            }
//                            $media_theme->media_theme_id = $model_m_theme->id;

                            if ($model_m_theme) {
                                $model_m_m_theme = MediaMediaTheme::findOne(['id' => $media_theme->id]);
                                if (!$model_m_m_theme) {
                                    $model_m_m_theme = new MediaMediaTheme();
                                }
                                $model_m_m_theme->media_id = $media->media_id;
                                $model_m_m_theme->media_theme_id = $media_theme->media_theme_id;
                                if (!$model_m_m_theme->save()) {
                                    $log->addData($model_m_m_theme->errors, '$model_m_m_theme->errors', __LINE__);
                                    throw new Exception("Ошибка сохранения модели MediaMediaTheme");
                                }
                                $media_theme->id = $model_m_m_theme->id;
                            } else {
                                $log->addLog("Тема $media_theme->media_theme_id не найдена");
                            }
                        }
                    }
                }
            }

            $result['media'] = $media;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetMediaToGroupList - Метод получения списка медиа по группам
     * Выходной объект:
     *      [media_group_id]:{
     *          "media_group_id":1,
     *          "media_group_title":"",
     *          "medias":{
     *              [media_id]:{
     *                  "media_id":1,
     *                  "media_group_id":2,
     *                  "attachment_id":10,
     *                  "attachment_path":"",
     *                  "attachment_title":"",
     *                  "attachment_type":"png"
     *                  "media_themes":[
     *                      {
     *                          "id":1,
     *                          "media_theme_id":1,
     *                      }
     *                      ...
     *                  ]
     *              },
     *              ...
     *          }
     *      },
     *      "...":
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=GetMediaToGroupList&subscribe=&data={}
     */
    public static function GetMediaToGroupList()
    {
        $log = new LogAmicumFront("GetMediaToGroupList");
        $result = null;

        try {
            $log->addLog("Начал выполнять метод");

            $media_groups = MediaGroup::find()
                ->joinWith('media.attachment')
                ->joinWith('media.mediaMediaThemes')
                ->all();

            $log->addLog("Получил данные");

            foreach ($media_groups as $media_group) {
                foreach ($media_group->media as $media) {
                    foreach ($media->mediaMediaThemes as $media_media_theme) {
                        $media_themes[] = array(
                            'id' => $media_media_theme->id,
                            'media_theme_id' => $media_media_theme->media_theme_id
                        );
                    }
                    if (!isset($media_themes)) {
                        $media_themes = array();
                    }

                    $medias[$media->id] = array(
                        'media_id' => $media->id,
                        'media_group_id' => $media->media_group_id,
                        'attachment_id' => $media->attachment_id,
                        'attachment_path' => $media->attachment->path,
                        'attachment_title' => $media->attachment->title,
                        'attachment_type' => $media->attachment->attachment_type,
                        'media_themes' => $media_themes
                    );
                    unset($media_themes);
                }
                if (!isset($medias)) {
                    $medias = (object)array();
                }
                $result[$media_group->id] = array(
                    'media_group_id' => $media_group->id,
                    'media_group_title' => $media_group->title,
                    'medias' => $medias
                );
                unset($medias);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetMediaThemeList - Метод получения списка тем медиа
     * Выходной объект:
     *      [id]:{
     *          "id":1
     *          "title":""
     *      },
     *      ...
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=GetMediaThemeList&subscribe=&data={}
     */
    public static function GetMediaThemeList()
    {
        $log = new LogAmicumFront("GetMediaThemeList");
        $result = null;

        try {
            $log->addLog("Начал выполнять метод");

            $result = MediaTheme::find()->indexBy('id')->all();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SavePlanTrainingList - Метод сохранения листа планов обучения
     * Входной объект:
     *  [plan_training_id] {
     *      plan_training_id
     *      company_department_id
     *      position_id
     *      mine_id
     *      worker_id
     *      kind_plan_training_id
     *      date
     *      date_time_create
     *      flag_del
     *      kind_tests {
     *          {
     *              kind_test_id
     *              flag_del
     *          }
     *          ...
     *      }
     *      tests {
     *          {
     *              test_id
     *              flag_del
     *          }
     *          ...
     *      }
     *  }
     *  ...
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=SavePlanTrainingList&subscribe=&data={}
     */
    public static function SavePlanTrainingList($data_post)
    {
        $log = new LogAmicumFront("SavePlanTrainingList");
        $result = null;

        try {
            $log->addLog("Начал выполнять метод");

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            foreach ($post as $plan_training) {
                $response = self::savePlanTraining($plan_training);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка сохранения плана обучения");
                }
                $plan_training_id = $response['plan_training_id'];
                $result[$plan_training_id] = $response['Items'];
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    public static function savePlanTraining($plan_training)
    {
        $log = new LogAmicumFront("savePlanTraining");
        $result = null;
        $plan_training_id = 0;

        try {
            $log->addLog("Начал выполнять метод");

            if (!isset($plan_training->plan_training_id) || $plan_training->plan_training_id == '') {
                throw new Exception("Не передан plan_training_id");
            }

            if (isset($plan_training->flag_del) && $plan_training->flag_del == 1) {
                PlanTraining::deleteAll(['id' => $plan_training->plan_training_id]);
                $plan_training->plan_training_id = null;
            } else {
                if (!KindPlanTraining::findOne(['id' => $plan_training->kind_plan_training_id])) {
                    throw new Exception("Не найден KindPlanTraining");
                }

                $model_plan_training = PlanTraining::findOne(['id' => $plan_training->plan_training_id]);
                if (!$model_plan_training) {
                    $model_plan_training = new PlanTraining();
                    $model_plan_training->date_time_create = Assistant::GetDateTimeNow();
                }
                $model_plan_training->company_department_id = $plan_training->company_department_id;
                $model_plan_training->position_id = $plan_training->position_id;
                $model_plan_training->mine_id = $plan_training->mine_id;
                $model_plan_training->worker_id = $plan_training->worker_id;
                $model_plan_training->kind_plan_training_id = $plan_training->kind_plan_training_id;
                $model_plan_training->date = $plan_training->date;
                if (!$model_plan_training->save()) {
                    $log->addData($model_plan_training->errors, '$model_plan_training->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели PlanTraining");
                }
                $plan_training_id = $model_plan_training->id;
                $plan_training->plan_training_id = $plan_training_id;
                $plan_training->date_time_create = $model_plan_training->date_time_create;

                foreach ($plan_training->kind_tests as $kind_test) {
                    if (isset($kind_test->flag_del) && $kind_test->flag_del == 1) {
                        PlanTrainingKindTest::deleteAll(['kind_test_id' => $kind_test->kind_test_id, 'plan_training_id' => $plan_training_id]);
                    } else {
                        if (!KindTest::findOne(['id' => $kind_test->kind_test_id])) {
                            throw new Exception("Не найден KindTest");
                        }

                        $model_p_t_k_t = PlanTrainingKindTest::findOne(['kind_test_id' => $kind_test->kind_test_id, 'plan_training_id' => $plan_training_id]);
                        if (!$model_p_t_k_t) {
                            $model_p_t_k_t = new PlanTrainingKindTest;
                        }
                        $model_p_t_k_t->plan_training_id = $plan_training_id;
                        $model_p_t_k_t->kind_test_id = $kind_test->kind_test_id;
                        if (!$model_p_t_k_t->save()) {
                            $log->addData($model_p_t_k_t->errors, '$model_p_t_k_t->errors', __LINE__);
                            throw new Exception("Ошибка сохранения модели PlanTrainingKindTest");
                        }
                        $kind_tests[$model_p_t_k_t->kind_test_id] = array(
                            'kind_test_id' => $model_p_t_k_t->kind_test_id,
                        );
                    }
                }
                if (!isset($kind_tests)) {
                    $kind_tests = (object)array();
                }
                $plan_training->kind_tests = $kind_tests;

                foreach ($plan_training->tests as $test) {
                    if (isset($test->flag_del) && $test->flag_del == 1) {
                        PlanTrainingTest::deleteAll(['test_id' => $test->test_id, 'plan_training_id' => $plan_training_id]);
                    } else {
                        if (!Test::findOne(['id' => $test->test_id])) {
                            throw new Exception("Не найден Test");
                        }

                        $model_p_t_t = PlanTrainingTest::findOne(['test_id' => $test->test_id, 'plan_training_id' => $plan_training_id]);
                        if (!$model_p_t_t) {
                            $model_p_t_t = new PlanTrainingTest;
                        }
                        $model_p_t_t->plan_training_id = $plan_training_id;
                        $model_p_t_t->test_id = $test->test_id;
                        if (!$model_p_t_t->save()) {
                            $log->addData($model_p_t_t->errors, '$model_p_t_t->errors', __LINE__);
                            throw new Exception("Ошибка сохранения модели PlanTrainingTest");
                        }
                        $tests[$model_p_t_t->test_id] = array(
                            'test_id' => $model_p_t_t->test_id,
                        );
                    }
                }
                if (!isset($tests)) {
                    $tests = (object)array();
                }
                $plan_training->tests = $tests;

                $result = $plan_training;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], ['plan_training_id' => $plan_training_id], $log->getLogAll());
    }

    /**
     * GetPlanTrainingList - Метод получение списка планов обучения
     * Входные данные:
     *      mines               - массив шахт
     *      companies           - массив подразделений
     *      positions           - массив должностей
     *      kind_plan_training  - массив типов планов обучения
     * Выходной объект:
     *  [plan_training_id] {
     *      plan_training_id
     *      company_department_id
     *      position_id
     *      mine_id
     *      worker_id
     *      kind_plan_training_id
     *      date
     *      date_time_create
     *      kind_tests {
     *          {
     *              kind_test_id
     *              kind_test_title
     *          }
     *          ...
     *      }
     *      tests {
     *          {
     *              test_id
     *              test_title
     *          }
     *          ...
     *      }
     *  }
     *  ...
     * @example http://127.0.0.1/read-manager-amicum?controller=Exam&method=GetPlanTrainingList&subscribe=&data={"mines":[],"companies":[],"positions":[],"kind_plan_training":[]}
     */
    public static function GetPlanTrainingList($data_post)
    {
        $log = new LogAmicumFront("GetPlanTrainingList");
        $result = null;

        try {
            $log->addLog("Начал выполнять метод");

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'mines') || $post->mines == '' ||
                !property_exists($post, 'companies') || $post->companies == '' ||
                !property_exists($post, 'positions') || $post->positions == '' ||
                !property_exists($post, 'kind_plan_training') || $post->kind_plan_training == ''
            ) {
                throw new Exception("Обязательные параметры не переданы");
            }

            $mines = $post->mines;
            $companies = $post->companies;
            $positions = $post->positions;
            $kind_plan_training = $post->kind_plan_training;

            $log->addLog("Получил все входные параметры");

            $plan_trainings = PlanTraining::find()
                ->joinWith('planTrainingKindTests.kindTest')
                ->joinWith('planTrainingTests.test')
                ->andFilterWhere([
                    'mine_id' => $mines,
                    'company_department_id' => $companies,
                    'position_id' => $positions,
                    'kind_plan_training_id' => $kind_plan_training
                ])
                ->all();

            foreach ($plan_trainings as $plan_training) {

                foreach ($plan_training['planTrainingKindTests'] as $plan_t_k_t) {
                    $kind_tests[$plan_t_k_t['kindTest']['id']] = array(
                        'kind_test_id' => $plan_t_k_t['kindTest']['id'],
                        'kind_test_title' => $plan_t_k_t['kindTest']['title']
                    );
                }
                if (!isset($kind_tests)) {
                    $kind_tests = (object)array();
                }

                foreach ($plan_training['planTrainingTests'] as $plan_t_t) {
                    $tests[$plan_t_t['test']['id']] = array(
                        'test_id' => $plan_t_t['test']['id'],
                        'test_title' => $plan_t_t['test']['title']
                    );
                }
                if (!isset($tests)) {
                    $tests = (object)array();
                }

                $result[$plan_training['id']] = array(
                    'plan_training_id' => $plan_training['id'],
                    'company_department_id' => $plan_training['company_department_id'],
                    'position_id' => $plan_training['position_id'],
                    'mine_id' => $plan_training['mine_id'],
                    'worker_id' => $plan_training['worker_id'],
                    'kind_plan_training_id' => $plan_training['kind_plan_training_id'],
                    'date' => $plan_training['date'],
                    'date_time_create' => $plan_training['date_time_create'],
                    'kind_tests' => $kind_tests,
                    'tests' => $tests
                );
                unset($kind_tests);
                unset($tests);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
