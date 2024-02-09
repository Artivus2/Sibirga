<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\handbooks;
//ob_start();

use frontend\controllers\Assistant;
use frontend\models\AccessCheck;
use frontend\models\Company;
use frontend\models\TypicalObject;
use yii;
use yii\db\Exception;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;


class HandbookMineController extends Controller
{
    public function actionIndex()
    {
        $objectList = TypicalObject::find()
            ->select(['title', 'id'])
            ->asArray()
            ->all();
        $companyList = Company::find()
            ->select(['title', 'id'])
            ->asArray()
            ->all();
        $minesArray = MineController::buildMineArray();
        return $this->render('index',
            [
                'model' => $minesArray,
                'objectList' => $objectList,
                'companyList' => $companyList,
            ]);
    }


    /**
     * Назначение: Функция добавления новой шахты
     * Название метода: actionAddMine()
     * @package frontend\controllers\handbooks
     *
     * @see
     * @example
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.05.2019 13:12
     * @since ver
     */
    public function actionAddMine()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();                                                                                              //массив ошибок
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                                                               //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 39))                                         //если пользователю разрешен доступ к функции
            {
                $post = Yii::$app->request->post();                                                                     //получение данных от ajax-запроса
                $add_mine = MineController::AddMine($post['title'], $post['objectId'], $post['companyId']);             //добавляем новую шахту
                $errors = array_merge($errors, $add_mine['errors']);
                $model = $add_mine['model'];
            } else                                                                                                        //иначе если доступа нет, тогда записываем в массив ошибок и обновляем таблицу
            {
                $errors[] = 'У вас недостаточно прав для выполнения этого действия';
            }
        } else {
            $errors[] = 'Сессия неактивна';
        }
        $model = MineController::buildMineArray();
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Назначение: Функция редактирования существующей шахты
     * Название метода: actionEditMine()
     * @package frontend\controllers\handbooks
     *
     * @see
     * @example
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.05.2019 13:05
     * @since ver
     */
    public function actionEditMine()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 40)) {                                                                                                           //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                $edit_mine = MineController::EditMine($post['id'], $post['title'], $post['objectId'], $post['companyId']);
                $errors = array_merge($errors, $edit_mine['errors']);

            } else {
                $errors[] = 'У вас недостаточно прав для выполнения этого действия';
            }
        } else {
            $errors[] = 'Сессия неактивна';
        }
        $model = MineController::buildMineArray();
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // формат json
        Yii::$app->response->data = $result;

    }

    /**
     * Назначение: Функция удаления существующей шахты
     * Название метода: actionDeleteMine()
     * @throws \Throwable
     * @throws yii\db\StaleObjectException
     * Документация на портале:
     * @example
     *
     * @package frontend\controllers\handbooks
     *
     * @see
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.05.2019 13:11
     * @since ver
     */
    public function actionDeleteMine()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();                                                                                              //массив ошибок
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                                                               //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 41)) {                                                                                                           //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();                                                                     //получение данных от ajax-запроса
                $delete_mine = [];
                try {
                    $delete_mine = MineController::DeleteMine($post['id']);
                    $errors = array_merge($errors, $delete_mine['errors']);
                } catch (Exception $e) {
                    $errors[] = "Есть связанные данные с этой шахтой. Удаление не возможно!";
                }
            } else {
                $errors[] = 'У вас недостаточно прав для выполнения этого действия';
            }
        } else {
            $errors[] = 'Сессия неактивна';
        }
        $model = MineController::buildMineArray();
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Назначение: Поиск по данных по шахте
     * Название метода: actionMarkSearchMine()
     * @package frontend\controllers\handbooks
     *
     * @see
     * @example
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.05.2019 13:11
     * @since ver
     */
    public function actionMarkSearchMine()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $mine_handbook = array();
        $result = array();
        if (isset($post['search_title'])) {
            $search_title = $post['search_title'];
            $sql_condition = "mine_title like '%$search_title%' OR object_title like '%$search_title%' OR company_title like '%$search_title%'";
            $mine_handbook_list = (new Query())
                ->select([
                    'mine_id',
                    'mine_title',
                    'object_id',
                    'object_title',
                    'company_id',
                    'company_title'
                ])
                ->from('view_mine_handbook')
                ->where($sql_condition)
                ->orderBy(['mine_title' => SORT_ASC])
                ->all();
            if ($mine_handbook_list) {
                $j = 0;
                foreach ($mine_handbook_list as $mine) {
                    $mine_handbook[$j]['id'] = $mine['mine_id'];
                    $mine_handbook[$j]['title'] = Assistant::MarkSearched($search_title, $mine['mine_title']);
                    $mine_handbook[$j]['objectId'] = $mine['object_id'];
                    $mine_handbook[$j]['objectTitle'] = Assistant::MarkSearched($search_title, $mine['object_title']);
                    $mine_handbook[$j]['companyId'] = $mine['company_id'];
                    $mine_handbook[$j]['companyTitle'] = Assistant::MarkSearched($search_title, $mine['company_title']);
                    $j++;
                }
            }
        } else {
            $errors[] = 'Параметры не переданы';
        }
        $result = array('errors' => $errors, 'mine' => $mine_handbook);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }
}