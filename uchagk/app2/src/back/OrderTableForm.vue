<!--
  - Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
  -
  -->

<!--
Страница табличной формы выдачи наряда
Входные параметры:
- отсутствуют.
Подключенные компоненты:
    - executiveList         компонент отображения списка членов ВГК и ГМ
    - briefingList          компонент отображения списка инструктажей
    - shiftCrew             компонент отображения списка работников, сгруппированных по профессиям и по типу работ (на поверхности/в шахте)
    - orderList             компонент отображения списка работников, которые идут в наряде
    - chartTimeLine         комопнент отображения циклограммы
    - warningOperations     компонент отображения списка операций, направленных на предупреждение нарушений (работы по линий ВТБ(АБ))
    - violationOperations   компонент отображения списка предписаний
    - placeAndOperations    компонент отображения списка мест(объектов/забоев) и операций, проводимых в нем
    - dayPicker             компонент отображения календаря
    - simpleDropdownList    компонент отображения выпадающего списка смен(или бригад) в блоке фильтра
    - departmentList        компонент отображения выпадающего списка участков

Используется
    - в качестве печатной формы на страинице "Заполнить отчет" во вьюшке fill-report.vue
    - в качестве модального окна на странице "Отчет за предыдущий период" в компоненте ReportModalOrder.vue

**/-->
<template>
  <div ref="windowTableForm" :class="maxWindow" class="os-table-form-wrapper tableFormWrapper">
    <div class="tableFormWrapper-header">
      <!-- Подпись и утверждение -->
      <div class="tableFormWrapper-header-signatures">
        <div class="tableFormWrapper-header-signatures-consistently">
          <p>Согласовано</p><span>АБ(ВТБ)________________</span>
        </div>
        <div class="tableFormWrapper-header-signatures-confirm">
          <p>Утверждаю</p><span>Начальник смены __________/________</span>
        </div>
      </div>
    </div>

    <div class="tableFormWrapper-content">
      <div class="tableFormWrapper-content-left">

        <!-- Список членов ВГК -->
        <div class="tableFormWrapper-content-left-executiveList">
          <executive-list
              :workerRescuersList="allRescuers"
              @addRescuerToList="addVgk"
              @deleteVgkFromOrder="deleteVgkFromOrder"/>
        </div>

        <!-- Список Ежесменного интсруктажа -->
        <div class="tableFormWrapper-content-left-briefingList">
          <briefing-list
              :briefingList="briefingList"
              @addBriefing="addBriefing"
              @delBriefing="delBriefing"
          />
        </div>

        <!-- Список по профессиям работников в шахте -->
        <div class="tableFormWrapper-content-left-shiftCrew">
          <worker-list-grouped-by-role
              :outgoingInfo="orderBrigade.outgoing"/>
        </div>
      </div>

      <div class="tableFormWrapper-content-center">
        <!-- Список выдачи наряда -->
        <div class="tableFormWrapper-content-center-orderList">
          <order-list
              :currentBrigade="currentBrigade"
              :currentChane="currentChane"
              :handbookBrigadeChane="AllBrigadesInCompanyDepartment"

              @addWorkerInOrder="addWorker"
              @changeCurrentChaneBrigade="changeCurrentChaneBrigade"
              @openCardInfoWorker="openCardInfoWorker"/>
        </div>
        <!-- Циклограмма убрано, т.к. эта хрень не уменьшается и не сворачивается - нужно переделать компонент-->
        <!--                <div class="tableFormWrapper-content-center-timeLine">-->
        <!--                    <report-time-line-->
        <!--                            :selectedShift="chosenShiftForFuture"-->
        <!--                            :chosenDate="chosenDate"-->
        <!--                            :orderBrigade="orderBrigade"-->
        <!--                            :disabled="disabled"-->
        <!--                            @addCyclogramInOrder="addCyclogramInOrder"-->
        <!--                    />-->
        <!--                </div>-->

      </div>

      <div class="tableFormWrapper-content-right">
        <!--  -->
        <div class="tableFormWrapper-content-right-warning">
          <warning-operations
              :placeOperations="aerologicalOperationsList"
          />
        </div>
        <!--  -->

        <div class="tableFormWrapper-content-right-violation">
          <violation-operations :companyDepartmentId="chosenDepartment.id"
                                :injunctionsList="injunctionsList"
          />
        </div>
        <!--  -->

        <div class="tableFormWrapper-content-right-place">
          <places-and-operations
              @change="orderObject"
          />
          <!--todo: проверить необходимость метода @change-->
        </div>

      </div>
      <employee-card
          v-if="activeModal=='cardEmployee'"
          :idWorker="selectedWorkerId"
          @changeActiveModal="showWorkerInfo"
      />
    </div>

    <div class="tableFormWrapper-footer">
      <div class="tableFormWrapper-footer-outfitIssued"><span>Наряд выдал _______________/___________</span></div>
      <div class="tableFormWrapper-footer-outfitAccepted"><span>Наряд принял _______________/___________</span>
      </div>
    </div><!-- Блок для печати формы -->
    <!-- Блок для печати формы -->
    <!--        <div class="tableFormWrapper-printedFormOrder">-->
    <!--            <printedFormOrder-->
    <!--                    :orderData="orderBrigade"-->
    <!--                    :orderDataProd="orderBrigadeProd"-->
    <!--                    :orderDataPb="orderBrigadePb"-->
    <!--                    :briefingList="briefingList"-->
    <!--                    :chosenDate="chosenDate"-->
    <!--                    :chosenShift="chosenShift"-->
    <!--                    :chosenDepartment="chosenDepartment"-->
    <!--                    :chosenChane="chosenChane"-->
    <!--                    :chosenBrigade="chosenBrigade"-->
    <!--            ></printedFormOrder>-->
    <!--        </div>-->
    <!-- Блок для печати формы -->
    <div class="tableFormWrapper-printedFormVoucher">
      <printedFormVoucher
          :chosenBrigade="chosenBrigade"
          :chosenChane="chosenChane"
          :chosenDate="chosenDate"
          :chosenDepartment="chosenDepartment"
          :chosenShift="chosenShift"
          :listWorkerAGK="listWorkerAGK"
          :orderData="orderBrigade"
      ></printedFormVoucher>
    </div>
  </div>
</template>

<script>
/**
 * Первая колонка
 **/
const executiveList = () => import('@/modules/order-system/components/table-form/tables/rescuersList.vue'),
    briefingList = () => import('@/modules/order-system/components/table-form/tables/briefingsList.vue'),
    workerListGroupedByRole = () => import("@/modules/order-system/components/table-form/tables/workerListGroupedByRole.vue"),
    /**
     * Вторая колонка
     **/
    orderList = () => import('@/modules/order-system/components/table-form/tables/workersTable.vue'),
    reportTimeLine = () => import('@/modules/order-system/components/table-form/tables/combineCyclogram.vue'),
    /**
     * Третья колонка
     **/
    warningOperations = () => import('@/modules/order-system/components/table-form/tables/aerologicalOperationsTable.vue'),
    violationOperations = () => import('@/modules/order-system/components/table-form/tables/injunctionsTable.vue'),
    placesAndOperations = () => import('@/modules/order-system/components/table-form/tables/orderOperationsAndPlaces.vue'),
    employeeCard = () => import('@/components/orderSystem/employeeCard/employeeCard.vue'),
    /**
     * Выпадающие списки, календари
     */
    dayPicker = () => import("@/modules/order-system/components/table-form/modal/dayPicker.vue"),
    simpleDropdownList = () => import("@/modules/order-system/components/table-form/modal/simpleDropdownList"),
    templateOrderDropdownMain = () => import("@/modules/order-system/components/shift-schedule/modals/ListTemplateOrder.vue"),
    createTemplateOrderDropdownMain = () => import("@/modules/order-system/components/shift-schedule/modals/CreateTemplateOrder.vue"),
    departmentList = () => import("@/modules/order-system/components/shift-schedule/modals/ListDepartment.vue");
const workerByDepartmentListDropDown = () => import("@/modules/order-system/components/shift-schedule/modals/ListWorkersByDepartment.vue");
const printedFormVoucher = () => import('@/modules/order-system/components/table-form/tables/printedFormVoucher.vue'); //  печатная форма горного мастера
// const printedFormOrder = () => import('@/modules/order-system/components/table-form/tables/printedFormOrder.vue'); //  печатная форма горного мастера
// injunctionModalWindow = () => import('../components/table-form/modal/injunctionModalWindow');
/**
 * Дополнительные плагины
 */
//import { normalize, schema } from 'normalizr';                                                                    //normalizr - плагин для упрощения массиво и объектов с несколькими уровнями вложенности
import webSocket from '@/service/webSocket.js'
import localStore from "@/modules/order-system/components/table-form/tables/orderControlStore.js";

export default {
  name: 'table-form',
  props: {
    chosenShift: {
      type: Object
    },
    chosenDate: {
      type: Date
    },
    chosenDepartment: {
      type: Object
    },
    chosenMine: {
      type: Object
    },
  },
  components: {
    executiveList,                                                                                                      // Компонент для отображения списка руководителей
    briefingList,                                                                                                       // Список ежесменных инструктажей
    workerListGroupedByRole,                                                                                            // Список по профессиям работников в шахте
    orderList,                                                                                                          // Выдача наряда работнику
    reportTimeLine,                                                                                                     // Циклограмма работы комбайна
    warningOperations,                                                                                                  // Предупреждение нарушений
    violationOperations,                                                                                                // Предписание на наряд
    placesAndOperations,                                                                                                // Список заданий на наряд


    simpleDropdownList,                                                                                                 // Выпадающий список выбора смены / бригады
    dayPicker,                                                                                                          // Окно календаря
    departmentList,                                                                                                     // выпадающий список компаний и подразделений
    workerByDepartmentListDropDown,                                                                                     // модальное окно с выбором работника - есть поиск и группировка по департаменту
    templateOrderDropdownMain,                                                                                          // выпадашка выбора шаблона
    createTemplateOrderDropdownMain,                                                                                    // выпадашка создания шаблона

    //injunctionModalWindow,                                                                                            // модальное окно предписания
    employeeCard,                                                                                                       // Модальное окно карточки сотрудника
    printedFormVoucher,                                                                                                 //  печатная форма горного мастера
    // printedFormOrder,                                                                                                //  печатная форма горного мастера
  },
  data() {
    return {
      chosenChane: {},
      chosenBrigade: {},
      currentChaneId: -1,                                                                                               // текущее выбранное звено ключ
      currentBrigadeId: -1,                                                                                             // текущая выбранная бригада ключ
      currentChane: 'all',                                                                                              // текущее выбранное звено или все звенья
      currentBrigade: 'all',                                                                                            // текущая выбранная бригада или все бригады
      debug_mode: false,                                                                                                // флаг переключения режима отладки
      modalMode: false,                                                                                                 // флаг переключение режима модального окна и слой
      correctOrderMode: false,                                                                                          // флаг переключения режима корректировки наряда
      maxWindow: '',                                                                                                    // переменная для хранения класса для контейнера страницы, указывающая полноэкранный просмотр
      // activeModal: '',                                                                                               // переменная для хранения имени активного модального окна или выпадающего списка
      // activeModal2: '',                                                                                              // переменная для хранения имени активного модального окна или выпадающего списка 2 уровня
      AllBrigadesInCompanyDepartment: [],                                                                               // массив для хранения всех бригад на участке


      openWorkerInfo: false,                                                                                            // флаг переключения видимости карточки сотрудника
      aerologicalSafetyOperations: [],                                                                                  // массив операций для компонента отображения списка операций для работ по линии ВТБ (АБ)
      operationsInOrder: {},                                                                                            // переменная для хранения всех операций, кроме тех, что являются работами по линии ВТБ(АБ) (объект)
      orderObject: {},                                                                                                  // переменная для хранения объекта наряда
      brigadeWorkersInOrder: {},                                                                                        // переменная для храения списка работников в наряде (объект)                 //переменная для хранения списка членов ВГК и ГМ (объек)
      arrWarning: [],
      selectedWorkerId: 0,                                                                                              // переменная для хранения id работника, на которм нажали правую кнопку мыши и выбрали пункт меню Карточка сотрудника

      placeIterator: 0,
      operationIterator: 0,
      disabled: true,
    }
  },
  /**
   * Блок вычисляемых свойств
   **/
  computed: {
    // список ответственных за оборку забоя и т.д. Объект по воркерам
    listWorkerAGK() {
      let warnings = [],
          errors = [],
          result = {};
      let order_workers = {};
      let orderInfo = {};
      try {
        let brigadeChaneWorker = localStore.getters.BRIGADECHANEWORKER;
        let orderPlaces = localStore.getters.ORDERPLACES;

        for (let brigadeId in brigadeChaneWorker) {
          for (let chaneid in brigadeChaneWorker[brigadeId].chanes) {
            for (let workerId in brigadeChaneWorker[brigadeId].chanes[chaneid].workers) {
              let workerObj = brigadeChaneWorker[brigadeId].chanes[chaneid].workers[workerId];
              let operationsWorker = brigadeChaneWorker[brigadeId].chanes[chaneid].workers[workerId].operation_production;
              for (let orderOperationId in operationsWorker) {
                let flagAdd = 0;
                let operationWorker = operationsWorker[orderOperationId];
                let orderPlaceId = operationWorker.order_place_id;
                let operationId = orderPlaces[orderPlaceId].operation_production[orderOperationId].operation_id;
                // если работа по линии АБ 1 или по производственному контролю 2, то кладем в список, иначе мимо кассы

                if (
                    operationId === 137 ||
                    operationId === 273 ||
                    operationId === 280 ||
                    operationId === 209 ||
                    operationId === 173 ||
                    operationId === 129 ||
                    operationId === 114 ||
                    operationId === 128 ||
                    operationId === 208 ||
                    operationId === 43 ||
                    operationId === 85 ||
                    operationId === 114 ||
                    operationId === 172 ||
                    operationId === 279 ||
                    operationId === 44 ||
                    operationId === 86 ||
                    operationId === 173 ||
                    operationId === 280 ||
                    operationId === 117 ||
                    operationId === 199 ||
                    operationId === 118 ||
                    operationId === 200
                ) {
                  if (order_workers[workerId] == undefined) {
                    let worker_item = {
                      worker_id: workerObj.worker_id,                              // ключ работника
                    };
                    this.$set(order_workers, workerId, worker_item);
                  }
                }
              }

            }
          }
        }

        // console.log("listWorkerAGK. Результат", order_workers);
      } catch (error) {
        this.disabled = true;
        console.log("listWorkerAGK. Исключение");
        console.log(error);
      }
      //console.warn(warnings);
      // errors.length ? console.error(errors) : '';
      return order_workers;
    },
    // Установить/получить список членов ВГК
    allRescuers: {
      get() {
        return localStore.getters.ALLRESCUERS;
      },
      set(value) {
        localStore.dispatch("setAllRescuers", value);
      }
    },

    // Установить/получить текущую активную вкладку 1 уровня
    templateOrderList: {
      get() {
        return localStore.getters.TEMPLATEORDERLIST;
      },
      set(value) {
        localStore.dispatch("setTemplateOrderList", value);
      }
    },
    // Установить/получить текущую активную вкладку 1 уровня
    activeModal: {
      get() {
        return this.$store.getters.ACTIVEMODAL;
      },
      set(value) {
        this.$store.dispatch("setActiveModal", value);
      }
    },
    // Установить/получить текущую активную вкладку 2 уровня
    activeModal2: {
      get() {
        return this.$store.getters.ACTIVEMODAL2;
      },
      set(value) {
        this.$store.dispatch("setActiveModal2", value);
      }
    },

    /**
     * вычисляемое свойство - получение списка департаметов рекурсивным образом из БД - повыщение скорости обработки и получения данных
     */
    allCompDepar() {
      return this.$store.getters.GETALLCOMPDEPAR;
    },

    selectedBrigadeAndChain() {
      return this.chosenChane.chane_id ? this.chosenBrigade.brigade_description + ' / ' + this.chosenChane.chane_title : this.chosenBrigade.brigade_description;
    },
    /**
     * Получение наряда на ВСЕ виды работ по производству
     * @returns {getters.getOrderInfo} - возвращает полученные данные из хранилища по наряду
     */
    orderBrigade() {
      let warnings = [],
          errors = [];
      let order_workers = {};
      let order_chanes = {};
      let orderInfo = {};
      try {
        let brigadeChaneWorker = localStore.getters.BRIGADECHANEWORKER;
        for (let brigadeId in brigadeChaneWorker) {
          for (let chaneid in brigadeChaneWorker[brigadeId].chanes) {
            for (let workerId in brigadeChaneWorker[brigadeId].chanes[chaneid].workers) {
              if (Object.keys(brigadeChaneWorker[brigadeId].chanes[chaneid].workers[workerId].operation_production).length) {
                let workerObj = brigadeChaneWorker[brigadeId].chanes[chaneid].workers[workerId];
                this.$set(order_workers, workerId, workerObj);
              }
              if (order_chanes[chaneid] == undefined) {
                this.$set(order_chanes, chaneid,
                    {
                      chane_id: brigadeChaneWorker[brigadeId].chanes[chaneid].chane_id,
                      chane_title: this.allBrigadesInDepartment[brigadeId].chanes[chaneid].chane_title,
                      workers: {}
                    }
                );
              }
              if (order_workers[workerId]) {
                this.$set(order_chanes[chaneid].workers, workerId, order_workers[workerId]);
              }
            }
          }
        }
        let orderPlaces = localStore.getters.ORDERPLACES;
        for (let orderPlaceId in orderPlaces) {
          for (let orderOperation in orderPlaces[orderPlaceId].operation_production) {
            let unit_id = orderPlaces[orderPlaceId].operation_production[orderOperation].unit_id;

            orderPlaces[orderPlaceId].operation_production[orderOperation].unit_title = this.$store.getters.getUnitById(unit_id) ? this.$store.getters.getUnitById(unit_id).short : ""
          }
        }
        orderInfo = {
          order_workers: order_workers,
          order_places: orderPlaces,
          order_chanes: order_chanes,
        };

      } catch (error) {
        this.disabled = true;
        console.log("orderBrigade. Исключение");
        console.log(error);
      }
      //console.warn(warnings);
      errors.length ? console.error(errors) : '';
      return orderInfo;
    },
    /**
     * Получение наряда на виды работ по производству
     * @returns {getters.getOrderInfo} - возвращает полученные данные из хранилища по наряду
     */
    orderBrigadeProd() {
      let warnings = [],
          errors = [];
      let order_workers = {};
      let order_chanes = {};
      let orderInfo = {};
      try {
        if (Object.keys(this.allBrigadesInDepartment).length) {
          let brigadeChaneWorker = localStore.getters.BRIGADECHANEWORKER;
          let orderPlaces = localStore.getters.ORDERPLACES;
          for (let brigadeId in brigadeChaneWorker) {
            for (let chaneid in brigadeChaneWorker[brigadeId].chanes) {
              for (let workerId in brigadeChaneWorker[brigadeId].chanes[chaneid].workers) {
                let workerObj = brigadeChaneWorker[brigadeId].chanes[chaneid].workers[workerId];
                let operationsWorker = brigadeChaneWorker[brigadeId].chanes[chaneid].workers[workerId].operation_production;
                for (let orderOperationId in operationsWorker) {
                  let flagAdd = 0;
                  let operationWorker = operationsWorker[orderOperationId];
                  let orderPlaceId = operationWorker.order_place_id;
                  let groupOperationAr = orderPlaces[orderPlaceId].operation_production[orderOperationId].operation_groups;
                  // если работа по линии АБ 1 или по производственному контролю 2, то НЕ кладем в список, иначе кладем
                  // warnings.push("orderBrigadeProd. место", orderPlaceId);
                  // warnings.push("orderBrigadeProd. операция", orderOperationId);
                  // warnings.push("orderBrigadeProd. спарвочник мест", orderPlaces);
                  // warnings.push("orderBrigadeProd. Группа операций", groupOperationAr);
                  // warnings.push("orderBrigadeProd. длина массива", groupOperationAr.length);
                  if (groupOperationAr.length) {
                    for (let idx in groupOperationAr) {
                      if (groupOperationAr[idx] != 1 && groupOperationAr[idx] != 2) {
                        flagAdd = 1;
                        // warnings.push("orderBrigadeProd. ДОБАВИЛ НАРЯД ПО ИФ ГРУПП", groupOperationAr[idx]);
                      } else {
                        // warnings.push("orderBrigadeProd. ЭТО ОПЕРАЦИЯ ПБ", groupOperationAr[idx]);
                      }
                    }
                  } else {
                    flagAdd = 1;
                    // warnings.push("orderBrigadeProd. ДОБАВИЛ НАРЯД НЕ БЫЛО ГРУППЫ", groupOperationAr.length);
                  }
                  if (flagAdd === 1) {
                    if (order_workers[workerId] == undefined) {
                      let worker_item = {
                        worker_id: workerObj.worker_id,                              // ключ работника
                        worker_full_name: workerObj.worker_full_name,                         // ФИО работника
                        worker_role_id: workerObj.worker_role_id,                           // ключ роли
                        worker_role_title: workerObj.worker_role_title,                        // наименование роли
                        worker_position_id: workerObj.worker_position_id,                     // ключ должности
                        worker_position_title: workerObj.worker_position_title,                    // наименование должности
                        worker_position_qualification: workerObj.worker_position_qualification,            // кваливифкация/разряд
                        operation_production: {},                     // список привязанных работ (нарядов)
                        chainer: workerObj.chainer,                               // флаг звеньевого
                        type_skud: workerObj.type_skud,                                 // Флаг СКУД (прошел или нет работник в АБК
                      };
                      this.$set(order_workers, workerId, worker_item);
                    }
                    this.$set(order_workers[workerId].operation_production, orderOperationId, workerObj.operation_production[orderOperationId]);
                  }
                }
                if (order_chanes[chaneid] == undefined) {
                  this.$set(order_chanes, chaneid,
                      {
                        chane_id: brigadeChaneWorker[brigadeId].chanes[chaneid].chane_id,
                        chane_title: this.allBrigadesInDepartment[brigadeId].chanes[chaneid].chane_title,
                        workers: {}
                      }
                  );
                }
                if (order_workers[workerId]) {
                  this.$set(order_chanes[chaneid].workers, workerId, order_workers[workerId]);
                }
              }
            }
          }

          orderInfo = {
            order_workers: order_workers,
            order_places: orderPlaces,
            order_chanes: order_chanes,
          };
        }

      } catch (error) {
        this.disabled = true;
        console.log("orderBrigadeProd. Исключение");
        console.log(error);
      }
      // console.warn(warnings);
      return orderInfo;
    },
    /**
     * Получение наряда на работы по линии АБ и ПБ
     * @returns {getters.getOrderInfo} - возвращает полученные данные из хранилища по наряду
     */
    orderBrigadePb() {
      let warnings = [],
          errors = [],
          result = {};
      let order_workers = {};
      let order_chanes = {};
      let orderInfo = {};
      try {
        if (Object.keys(this.allBrigadesInDepartment).length) {
          let brigadeChaneWorker = localStore.getters.BRIGADECHANEWORKER;
          let orderPlaces = localStore.getters.ORDERPLACES;
          warnings.push("orderBrigadePb. Начало метода brigadeChaneWorker", brigadeChaneWorker);
          warnings.push("orderBrigadePb. Начало метода orderPlaces", orderPlaces);
          for (let brigadeId in brigadeChaneWorker) {
            for (let chaneid in brigadeChaneWorker[brigadeId].chanes) {
              for (let workerId in brigadeChaneWorker[brigadeId].chanes[chaneid].workers) {
                let workerObj = brigadeChaneWorker[brigadeId].chanes[chaneid].workers[workerId];
                let operationsWorker = brigadeChaneWorker[brigadeId].chanes[chaneid].workers[workerId].operation_production;
                for (let orderOperationId in operationsWorker) {
                  let flagAdd = 0;
                  let operationWorker = operationsWorker[orderOperationId];
                  let orderPlaceId = operationWorker.order_place_id;
                  let groupOperationAr = orderPlaces[orderPlaceId].operation_production[orderOperationId].operation_groups;
                  // если работа по линии АБ 1 или по производственному контролю 2, то кладем в список, иначе мимо кассы
                  // warnings.push("orderBrigadePb. место", orderPlaceId);
                  // warnings.push("orderBrigadePb. операция", orderOperationId);
                  // warnings.push("orderBrigadePb. спарвочник мест", orderPlaces);
                  // warnings.push("orderBrigadePb. Группа операций", groupOperationAr);
                  // warnings.push("orderBrigadePb. длина массива", groupOperationAr.length);
                  if (groupOperationAr.length) {
                    for (let idx in groupOperationAr) {
                      if (groupOperationAr[idx] == 1 || groupOperationAr[idx] == 2) {
                        flagAdd = 1;
                        warnings.push("orderBrigadePb. ДОБАВИЛ НАРЯД ПО ИФ ГРУПП", groupOperationAr[idx]);
                      } else {
                        warnings.push("orderBrigadeProd. ЭТО ОПЕРАЦИЯ ПРОД", groupOperationAr[idx]);
                      }
                    }
                  } else {
                    flagAdd = 0;
                    // warnings.push("orderBrigadePb. Группы нет, в наряд не добавил", groupOperationAr.length);
                  }

                  if (flagAdd === 1) {
                    if (order_workers[workerId] == undefined) {
                      let worker_item = {
                        worker_id: workerObj.worker_id,                              // ключ работника
                        worker_full_name: workerObj.worker_full_name,                         // ФИО работника
                        worker_role_id: workerObj.worker_role_id,                           // ключ роли
                        worker_role_title: workerObj.worker_role_title,                        // наименование роли
                        worker_position_id: workerObj.worker_position_id,                     // ключ должности
                        worker_position_title: workerObj.worker_position_title,                    // наименование должности
                        worker_position_qualification: workerObj.worker_position_qualification,            // кваливифкация/разряд
                        operation_production: {},                     // список привязанных работ (нарядов)
                        chainer: workerObj.chainer,                               // флаг звеньевого
                        type_skud: workerObj.type_skud,                                 // Флаг СКУД (прошел или нет работник в АБК
                      };
                      this.$set(order_workers, workerId, worker_item);
                    }
                    this.$set(order_workers[workerId].operation_production, orderOperationId, workerObj.operation_production[orderOperationId]);

                  }
                }
                if (order_chanes[chaneid] == undefined) {
                  this.$set(order_chanes, chaneid,
                      {
                        chane_id: brigadeChaneWorker[brigadeId].chanes[chaneid].chane_id,
                        chane_title: this.allBrigadesInDepartment[brigadeId].chanes[chaneid].chane_title,
                        workers: {}
                      }
                  );
                }
                if (order_workers[workerId]) {
                  this.$set(order_chanes[chaneid].workers, workerId, order_workers[workerId]);
                }
              }
            }
          }

          orderInfo = {
            order_workers: order_workers,
            order_places: orderPlaces,
            order_chanes: order_chanes,
          };
        }
        warnings.push("orderBrigadePb. Окончание метода");
      } catch (error) {
        this.disabled = true;
        console.log("orderBrigadePb. Исключение");
        console.log(error);
      }
      // console.log(warnings);
      // errors.length ? console.error(errors) : '';
      return orderInfo;
    },
    /**
     * Свойство, которое возвращает отформатированную дату
     * так как дополнительные аргументы не были переданы, то
     * возвращается дата в формате ДД.ММ.ГГГГ
     **/
    formattedDate() {
      return getFormattedDate(this.chosenDate);                                                               //вызов функции форматирования даты с передачей в нее объекта выбранной даты
    },
    //выбранная бригада
    chosenBrigadeLast: {
      get() {
        return this.chosenBrigade;
      }
    },
    //все бригады на участке
    allBrigadesInDepartment: {
      get() {
        return this.AllBrigadesInCompanyDepartment;
      }
    },

    allShifts: {
      get() {
        return this.$store.getters.SHIFTSFORMENU;
      }
    },
    //ближайшая смена
    chosenShiftForFuture: {
      get() {
        return this.chosenShift;
      },
      set(shiftObj) {
        // ////console.log('chosenShiftForFuture setter ', shiftObj);
        this.chosenShift = shiftObj;
      }
    },

    //список предписаний
    injunctionsList: {
      get() {
        // console.log ("injunctionsList", localStore.getters.INJUNCTIONS);
        return localStore.getters.INJUNCTIONS;
      }
    },

    // справочник статусов
    handbookStatusList: {
      get() {
        // console.log ("injunctionsList", localStore.getters.INJUNCTIONS);
        return localStore.getters.HANDBOOKSTATUS;
      }
    },

    // список инструктажей
    briefingList: {
      get() {
        return localStore.getters.ORDERINSTRUCTIONS;
      }
    },

    aerologicalOperationsList: {
      get() {
        const sourceOperationsList = this.$store.getters.getAerologicalSafetyOperations;
        //console.log("table-form. aerologicalOperationsList. get. sourceOperationList ", sourceOperationsList);
        if (sourceOperationsList && sourceOperationsList.order_places) {
          return sourceOperationsList.order_places;
        }
        return {};
      }
    },
  },
  methods: {
    // Закрытие модалки второго уровня, в данном случае используется для модалки работников
    setActiveModalDefault() {
      this.activeModal = "";
    },
    // Заполнить наряд шаблоном выбранным
    setTemplateOrder(templateOrderObj) {
      this.activeModal = "";
      this.activeModal2 = "";
      let templateObject = {
        brigadeId: this.chosenBrigadeLast.brigade_id,
        chaneId: this.chosenChane.chane_id,
        templateOrderObj: templateOrderObj,
        department: this.chosenDepartment,
        shiftId: Number(this.chosenShift.id),
        orderDateTime: getFormattedDate(this.chosenDate, 'hyphen')
      };
      localStore.dispatch('loadTemplateInOrder', templateObject);
    },
    // Создать шаблон наряда в БД
    createTemplateOrder(templateOrderTitle) {
      this.activeModal = "";
      this.activeModal2 = "";
      let templateObject = {
        brigadeId: this.chosenBrigadeLast.brigade_id,
        chaneId: this.chosenChane.chane_id,
        templateOrderTitle: templateOrderTitle,
        department: this.chosenDepartment,
        shiftId: Number(this.chosenShift.id),
        orderDateTime: getFormattedDate(this.chosenDate, 'hyphen')
      };
      localStore.dispatch('createTemplateInOrder', templateObject);
    },

    // метод добавления работника в Наряд . ПЕРЕДЕЛАЛ
    addWorker(workerObj) {
      console.log("table-form. addWorker. добавляемый объект работника", workerObj);
      let worker_role_id = '9';                                                     // инициализируем роль/профессию работника по умолчанию 9 - прочее
      if (workerObj.worker_role_id) {                                    // если его сконфигурировали в графике выходов, то все норм - береме его роль от туда, иначе роль берется по умолчанию
        worker_role_id = workerObj.worker_role_id;
      }
      let worker = {
        chane_id: this.currentChaneId,
        brigade_id: this.currentBrigadeId,
        worker_id: workerObj.worker_id,
        worker_role_id: worker_role_id,
      };

      if (this.currentChaneId !== -1) {
        localStore.dispatch('addOneWorkerInOrder', worker);
      } else {
        //console.log('table-form.vue, addWorker. нету звена', this.chosenChane.chane_id);
      }
    },


    // добавление предсменного инструктажа к наряду
    addBriefing(briefingObj) {
      //console.log('table-form.vue, addBriefing. dataForAddingOperationToWorker', dataForAddingOperationToWorker);
      let briefingObject = {
        brigadeId: this.chosenBrigadeLast.brigade_id,
        chaneId: this.chosenChane.chane_id,
        briefingId: briefingObj.briefingId,
        department: this.chosenDepartment,
        shiftId: Number(this.chosenShift.id),
        orderDateTime: getFormattedDate(this.chosenDate, 'hyphen')
      };

      localStore.dispatch('addBriefingInOrder', briefingObject);
    },
    // удаление предсменного инструктажа из наряду
    delBriefing(briefingObj) {
      //console.log('table-form.vue, delBriefing. dataForAddingOperationToWorker', dataForAddingOperationToWorker);
      let briefingObject = {
        brigadeId: this.chosenBrigadeLast.brigade_id,
        chaneId: this.chosenChane.chane_id,
        briefingId: briefingObj.briefingId,
        department: this.chosenDepartment,
        shiftId: Number(this.chosenShift.id),
        orderDateTime: getFormattedDate(this.chosenDate, 'hyphen')
      };

      localStore.dispatch('delBriefingInOrder', briefingObject);
    },
    closeWindow() {
      /**
       * Метод закрывает окно "Выдача наряда"
       */
      if (this.modalMode) {
        const tableForm = this.$refs.windowTableForm;
        tableForm.style.display = 'none'
      } else {
        this.$router.push('/order-system/order-system');
      }
    },
    /**
     * Метод при вызове раскрывает окно "Выдача наряда" на весь экран
     */
    setMaxWindow() {
      this.maxWindow === 'tableFormWrapper-max' ? this.maxWindow = '' : this.maxWindow = 'tableFormWrapper-max';
    },
    /**
     * Метод который устанавливает имя модального окна в переменную activeModal, в следствии чего данное модальное
     * окно отображается на экране
     * Входные параметры:
     * modalName (String) - наименование модального окна
     * Выходные параметры:
     * Отсутствуют
     */
    changeActiveModal(modalName, clickedDiv = null) {
      ////console.log(arguments);
      if (modalName === this.activeModal) {
        this.activeModal = '';
        if (clickedDiv && clickedDiv.querySelector('i')) {
          clickedDiv.querySelector('i').style.transform = "rotate(0deg)";
        }
      } else {
        if (modalName === 'brigadesList') {
          if (Object.keys(this.AllBrigadesInCompanyDepartment).length) {
            this.activeModal = modalName;
            if (clickedDiv && clickedDiv.querySelector('i')) {
              clickedDiv.querySelector('i').style.transform = "rotate(180deg)";
            }
          }
        } else {
          this.activeModal = modalName;
          if (clickedDiv && clickedDiv.querySelector('i')) {
            clickedDiv.querySelector('i').style.transform = "rotate(180deg)";
          }
        }


      }

    },
    /**
     * Метод открытия модального окна списка шаблонов нарядов
     **/
    openTemplateListModal() {
      let warnings = [];
      try {
        warnings.push("openTemplateListModal. Начал выполнять метод");
        if (this.chosenChane.chane_id != -1) {
          let modal = document.getElementById('ListTemplateOrderDropdown'); // Находим модальное окно добавления звена по классу
          if (!modal) {
            throw new Error("openTemplateListModal. элемент WorkersByDepartmentDropdown выпадающий список не найден");
          }

          modal.style.position = 'absolute'; // Задаём абсолютное позиционирование
          let positionModal = this.calcXYModal(event.clientX, event.clientY, 400, 200, "header");
          if (positionModal.status === 0) {
            throw new Error("openTemplateListModal. Ошибка при расчете места открытия окна");
          }
          warnings.push("openTemplateListModal. расчитали сдвиг: ", positionModal);
          modal.style.left = positionModal.left;
          modal.style.top = positionModal.top;
        } else {
          showNotify("Не сконфигурированы звенья в графике выходов", "danger");
        }
        this.activeModal2 = '';
        this.activeModal = 'ListTemplateOrderDropdownBody';

        warnings.push("openTemplateListModal. Закончил выполнять метод");
      } catch (err) {
        console.log("openTemplateListModal. Исключение");
        console.log(err);
      }
      console.log(warnings);
    },
    /**
     * Метод открытия модального окна создания шаблона наряда по текущему
     **/
    createTemplateListModal() {
      let warnings = [];
      try {
        warnings.push("createTemplateListModal. Начал выполнять метод");
        if (this.chosenChane.chane_id != -1) {
          let modal = document.getElementById('CreateTemplateOrderDropdown'); // Находим модальное окно добавления звена по классу
          if (!modal) {
            throw new Error("createTemplateListModal. элемент WorkersByDepartmentDropdown выпадающий список не найден");
          }

          modal.style.position = 'absolute'; // Задаём абсолютное позиционирование
          let positionModal = this.calcXYModal(event.clientX, event.clientY, 500, 200, "header");
          if (positionModal.status === 0) {
            throw new Error("createTemplateListModal. Ошибка при расчете места открытия окна");
          }
          warnings.push("createTemplateListModal. расчитали сдвиг: ", positionModal);
          modal.style.left = positionModal.left;
          modal.style.top = positionModal.top;
        } else {
          showNotify("Не сконфигурированы звенья в графике выходов", "danger");
        }
        this.activeModal2 = '';
        this.activeModal = 'CreateTemplateOrderDropdown';

        warnings.push("createTemplateListModal. Закончил выполнять метод");
      } catch (err) {
        console.log("createTemplateListModal. Исключение");
        console.log(err);
      }
      console.log(warnings);
    },
    /**
     * Метод расчета сдвига модалки или контекстного меню перед открытием
     * текущие размеры окна определяются автоматически
     * @param targetX           - координата X нажатия мышки
     * @param targetY           - координата Y нажатия мышки
     * @param contextMenuWidth  - ширина модалки в px
     * @param contextMenuHeight - длина модалки в px
     * @returns {{top: string, left: string}}
     * @example positionModal = this.calcXYModal(event.clientX, event.clientY, 400, 50,"schedule_component");
     *          modal.style.left = positionModal.left;
     *          modal.style.top = positionModal.top;
     */
    calcXYModal(targetX, targetY, contextMenuWidth, contextMenuHeight, nameBlockComponent) {
      let warnings = [];
      let response = null;
      let errors = [];
      try {
        let positionTarget = {};
        /**
         * получаем размеры окна браузера
         **/
        warnings.push("calcXYModal. Начал выполнять метод");
        let windowWidth = window.innerWidth,
            windowHeight = window.innerHeight;
        /**
         * получаем координаты и размеры компонента в котором находится ячейка. т.к. для ячейки координаты считаются относительно компонента центрального элемента
         * @type {ClientRect | DOMRect}
         */
        let componentBlock = document
            .getElementsByClassName(nameBlockComponent)[0]
            .getBoundingClientRect();

        /**
         * вычисляем разницу сдвига контекстного меню для вместимости в рамки основного окна
         **/

        let deltaContextMenuWith = 0;

        if (windowWidth < (targetX + contextMenuWidth)) {
          deltaContextMenuWith = contextMenuWidth;
        }
        let deltaContextMenuHeight = 0;
        if (windowHeight < (targetY + contextMenuHeight)) {
          deltaContextMenuHeight = contextMenuHeight;
        }
        warnings.push("calcXYModal. Закончил выполнять метод");
        /**
         * применяем сдвиг к контекстному меню
         **/
        positionTarget = {
          top: `${targetY - componentBlock.y - deltaContextMenuHeight}px`,
          left: `${targetX - componentBlock.x - deltaContextMenuWith}px`,
          warnings: warnings,
          errors: [],
          status: 1
        };

        response = positionTarget;
      } catch (err) {
        errors.push("openListWorkersByDepartment. Исключение");
        errors.push(err);
        console.log(errors);
        response = {
          warnings: warnings,
          errors: errors,
          status: 0
        };
      }
      console.log(warnings, errors);
      return response;
    },

    //переключает режимы открытия информации о работнике
    showWorkerInfo() {
      //Метод меняет булево значение в переменной openWorkerInfo на противоположное тем самым
      // показывая / скрывая модальное окно карточки сотрудника
      this.activeModal = '';
      this.activeModal2 = '';
    },
    //открывает информацио о работнике из модального окна
    openCardInfoWorker(workerId) {
      //Метод меняет булево значение в переменной openWorkerInfo на противоположное тем самым
      // показывая / скрывая модальное окно карточки сотрудника
      this.selectedWorkerId = workerId;
      this.activeModal = 'cardEmployee';
    },
    //запускает модалку на печать
    sendPrint() {
      /**
       * Метод при вызове, открывает стандартное окно браузера для вывода страницы на печать
       */

      window.print();
    },
    changeShift(shift) {
      if (this.debug_mode) {
        ////console.log('Пользователь выбрал смену -----', shift);
      }
      /**
       * Метод меняет значение переменной chosenShift, на значение выбранное в списке выбора
       * смены
       * Входные параметры:
       * shift (Object) - объект с информацией о выбранной смене
       */
      if (this.chosenShiftForFuture.title !== shift.title) {
        this.chosenShiftForFuture = shift;

        this.getOrderFromServer();                                                                          // получение наряда с сервера
        this.activeModal = '';
        this.activeModal2 = '';
      }
      document.querySelector('.header__shiftBtn i').style.transform = "rotate(0deg)";
    },
    changeBrigade(brigade) {
      /**
       * Метод меняет значение переменной chosenBrigade, на значение выбранное в списке выбора
       * бригады
       * Входные параметры:
       * brigade (Object) - объект с информацией о выбранной бригаде
       */
      // if (this.chosenBrigade.title !== brigade.title) {
      //     this.chosenBrigade.title = brigade.title
      // }
      this.chosenBrigade = brigade;
      this.activeModal = '';
      this.activeModal2 = '';
      document.querySelector('.header__brigadeBtn i').style.transform = "rotate(0deg)";
      //this.getWorkersInOrder('brigade');                                                                    //получение списка воркеров
    },
    /**
     * Метод установки выбранной даты в свойство chosenDate, которое используется для дальнейших вычислений и запросов
     * @params usersDate (datetime object) - объект даты
     **/
    changeDate(usersDate) {
      if (this.debug_mode) {
        ////console.log(`os-table-form.vue. changeDate. Выбрана дата = ${usersDate}`);
      }
      this.chosenDate = usersDate;
      this.getOrderFromServer();                                                                              // получение наряда с сервера

      this.activeModal = '';
    },

    changeCurrentDepartment(departmentObj) {
      /**
       * Функция по сохранению выбранного в фильтре подразделения в блок data
       * @param departmentObj (Object) - объект выбранного подразделения
       */
          // при срабатывании метода идет запрос на сервер и получается полностью новый наряд на департамент, после чего он заполняется здесь (в сторе)
      let warnings = [];
      try {
        warnings.push("changeCurrentDepartment. Начал выполнять метод");
        this.chosenDepartment = departmentObj;                                                              // записываем выбранный департамент в текущий рабочий департамент
        if (this.debug_mode) {
          warnings.push('changeCurrentDepartment. Выбранный участок', this.chosenDepartment);
        }
        this.getOrderFromServer();
        this.activeModal = '';
        this.activeModal2 = '';// получение наряда с сервера
        document.querySelector('.header__departmentBtn i').style.transform = "rotate(0deg)";
        warnings.push("changeCurrentDepartment. Закончил выполнять метод");

        if (hasProperty(localStorage, 'serialWorkerData')) {
          let serialWorkerData = {};
          serialWorkerData = JSON.parse(localStorage.getItem("serialWorkerData"));
          serialWorkerData.userCompanyDepartmentId = this.chosenDepartment.id;
          serialWorkerData.userCompany = this.chosenDepartment.title;

          localStorage.setItem("serialWorkerData", JSON.stringify(serialWorkerData));
        }

      } catch (err) {
        console.log("changeCurrentDepartment. Исключение");
        console.log(err);
      }
      // console.log(warnings);
    },

    getOrderFromServer() {
      /**
       * Функция по сохранению выбранного в фильтре подразделения в блок data
       * @param departmentObj (Object) - объект выбранного подразделения
       */
          // при срабатывании метода идет запрос на сервер и получается полностью новый наряд на департамент, после чего он заполняется здесь (в сторе)
      let warnings = [];
      try {
        warnings.push("getOrderFromServer. Начал выполнять метод");
        if (this.chosenDepartment.id && this.chosenShiftForFuture.id && this.chosenDate && this.chosenMine.id) {        // проверка наличия всех входных данных для отправки запроса на сервер
          warnings.push("getOrderFromServer. все входные данные есть");
          let jsonData = {                                                                                              // конфигурация для отправки на сервер в метод GetOrder контроллера OrderSystem
            company_department_id: this.chosenDepartment.id,                                                            // текущий запрашиваемых департамент
            shift_id: this.chosenShiftForFuture.id,                                                                     // текущая выбранная смена
            date_time: getFormattedDate(this.chosenDate, 'hyphen'),                                               // текущая выбранная дата, на которую мы формируем запрос
            mine_id: this.chosenMine.id,
          };

          this.brigadeWorkersInOrder = {};                                                                              // Обнуляем основной исходный объект Наряда перед запросом на сервер
          this.ajaxGetBrigadesByCompanyDepartment(jsonData.company_department_id);                                      // вызов функции получения списка бригад по заданному участку
          localStore.dispatch('ajaxGetTemplateOrderList', jsonData.company_department_id);                              // вызов функции получения списка шаблона нарядов на производство работ
          localStore.dispatch('ajaxGetInstructionsPB');                                                                 // вызов метода для получения инстуктожей - для заполнения наряда
          if (!this.handbookStatusList || !Object.keys(this.handbookStatusList).length) {
            localStore.dispatch('ajaxGetListStatus');                                                                   // вызов метода для получения справочника статусов
          }
          // //this.sendRequestToGetOperationsForAbVtb(jsonData.company_department_id);                                 // вызов функции получения списка операций и мест для блока работ по линий АБ(ВТБ)
          // this.$store.dispatch('ajaxGetOrderInfo', JSON.stringify(jsonData));                                        // вызов функции получения информации о наряде
          localStore.dispatch('ajaxGetOrder', JSON.stringify(jsonData));                                                // вызов функции получения информации о наряде
          warnings.push("getOrderFromServer. Получил наряд с сервера", this.orderData);

        }
        warnings.push("getOrderFromServer. Выбранный департамент", this.chosenDepartment.id);
        warnings.push("getOrderFromServer. Выбранная смена", this.chosenShiftForFuture.id);
        warnings.push("getOrderFromServer. Выбранная дата", this.chosenDate);
        this.activeModal = '';                                                                                          // скрытие выпадашки для выбора департамента
        this.activeModal2 = '';                                                                                         // скрытие выпадашки для выбора департамента

        warnings.push("getOrderFromServer. Закончил выполнять метод");
      } catch (err) {
        console.log("getOrderFromServer. Исключение");
        console.log(err);
      }
      // console.log(warnings);
    },

    setListenerForDocumentClick(event) {
      // //console.log('table-form.vue, onclick target', event.target);
      $('.header-triangle').css('transform', 'rotate(0deg)');
      this.activeModal = '';
    },
    // функции получения списка бригад по заданному участку
    async ajaxGetBrigadesByCompanyDepartment(companyDepartmentId) {
      let config = {
        controller: "ordersystem\\WorkSchedule",
        method: "GetListBrigade",
        subscribe: '',
        date_time_request: new Date(),
        page_request: window.location.href,
        data: JSON.stringify({
          company_department_id: companyDepartmentId,
          only_brigades: 0
        })
      };
      let brigades = await sendAjax(config);
      this.AllBrigadesInCompanyDepartment = brigades.Items;
      if (!brigades.Items || (Array.isArray(brigades.Items) && brigades.Items.length === 0)) {        //если Items = null или пустой массив
        ////console.log('ajaxGetBrigadesByCompanyDepartment нет бригад на участке');
        this.chosenBrigade = {                                                                      //то устанавливаем заглушку выбранной бригады
          brigade_id: '',
          brigader_id: '',
          brigade_description: "Нет бригад",
        };
        this.chosenChane = {
          brigade_id: null,
          chane_id: null,
          chaner_id: null,
          chane_type: null,
          chane_title: ""
          // chane_title: "Нет звеньев"
        };
        this.brigadeWorkersInOrder = {};                                                                    //очищаем список воркеров

      } else {                                                                                        //иначе
        console.log('ajaxGetBrigadesByCompanyDepartment есть список бригад:', this.AllBrigadesInCompanyDepartment);
        let firstBrigade = {
          brigade_id: 'all',
          brigader_id: null,
          brigade_description: "Все бригады",
          chanes: {
            all: {
              chane_id: 'all',
              chane_title: 'Все звенья',
              brigade_id: 'all',
              chaner_id: null,
              chane_type: null,
              workers: {}
            }
          }
        };
        this.$set(this.AllBrigadesInCompanyDepartment, 'all', firstBrigade);
        ////console.log('ajaxGetBrigadesByCompanyDepartment есть список бригад');

        ////console.log('ajaxGetBrigadesByCompanyDepartment объект первой бригады списка', firstBrigade);
        this.chosenBrigade = {                                                                  //заполняем информацию о бригаде в свойство chosenBrigade
          brigade_id: firstBrigade.brigade_id,
          brigader_id: firstBrigade.brigader_id,
          brigade_description: firstBrigade.brigade_description,
        };
        if (firstBrigade.chanes && Object.keys(firstBrigade.chanes).length) {
          this.chosenChane = firstBrigade.chanes[Object.keys(firstBrigade.chanes)[0]];
          // скрываем див, который перекрывает всю область кроме строки с фильтром
        } else {
          this.chosenChane = {
            brigade_id: null,
            chane_id: null,
            chaner_id: null,
            chane_type: null,
            chane_title: ""
            // chane_title: "Нет звеньев"
          };
          //отображаем див, который перекрывает всю область кроме строки с фильтром
        }
        ////console.log('ajaxGetBrigadesByCompanyDepartment объект выбранной бригады', this.chosenBrigade);
        // this.getWorkersInOrder("company_department");
      }

    },
    calculateUpcomingShift() {
      let response = calculateUpcomingShift(this.$store.getters.AMICUMDEFAULTSSHIFT, this.chosenDate);
      this.chosenShiftForFuture = response.chosenShift;
    },

    // метод добавления ВГК
    addVgk(workerObj) {
      //console.log("table-form. methods. addWorker. Начало метода", );
      //console.time("addWorker");
      console.log("table-form. addVgk. добавляемый объект работника в ВГК", workerObj);

      let workerObject = {
        workerInfo: workerObj,
      };
      localStore.dispatch('addVgkInOrder', workerObject);

      //console.timeEnd("addVgk");
      //console.log("table-form. methods. addVgk. Конец метода");
    },
    // метод удаления ВГК с наряда
    deleteVgkFromOrder(vgkObj) {
      //console.log("table-form. methods. addWorker. Начало метода", );
      //console.time("addWorker");
      console.log("table-form. deleteVgkFromOrder. добавляемый объект работника в ВГК", vgkObj);

      let workerObject = {
        workerInfo: vgkObj,
      };
      localStore.dispatch('delVgkInOrder', workerObject);

      //console.timeEnd("addVgk");
      //console.log("table-form. methods. addVgk. Конец метода");
    },
    saveOrder(event) {

      if (this.chosenChane.chane_id) {
        this.$refs.saveOrderBtn.style.background = "#7C6580";

        if (this.correctOrderMode) {                                                                            // если сейчас режим корректировки наряда
          this.$store.dispatch('sendCorrectOrder');                                                           // то вызываем метод по отправке данных для корректировки суще
        } else {
          localStore.dispatch("ajaxSaveOrderFromTableForm");
        }
      } else {
        this.$refs.saveOrderBtn.style.background = "#aaaaaa";
        showNotify('На участке нет бригад, сохранение невозможно', 'danger');
      }
    },
    sendRequestToGetOperationsForAbVtb(companyDepartmentId) {
      let dataForRequest = {
        company_department_id: companyDepartmentId,
        date: getFormattedDate(this.chosenDate, 'hyphen')
      };
      //console.log('table-form.vue, sendRequestToGetOperationsForAbVtb. dataForRequest', dataForRequest);
      this.$store.dispatch('ajaxGetAerologicalSafetyOperations', JSON.stringify(dataForRequest));
    },
    showChains(brigadeNodeElement) {
      if (brigadeNodeElement.nextElementSibling) {
        $(brigadeNodeElement.nextElementSibling).slideToggle('fast');
      }
    },
    selectCurrentChain(chaneObject, brigadeObject) {
      // console.log('selectCurrentChain. на входе', chaneObject);
      if (chaneObject.chane_id === 'all') {
        this.currentBrigade = 'all';
        this.currentChane = 'all';
      } else {
        this.currentBrigade = chaneObject.brigade_id;
        this.currentChane = chaneObject.chane_id;
      }
      this.chosenChane = chaneObject;
      this.chosenBrigade = brigadeObject;
      this.activeModal2 = "";
      this.activeModal = "";
    },
    selectCurrentBrigade(brigadeObject) {
      // console.log('selectCurrentBrigade. на входе', brigadeObject);
      if (brigadeObject.brigade_id === 'all') {
        this.currentBrigade = 'all';
      } else {
        this.currentBrigade = brigadeObject.brigade_id;
      }
      this.currentChane = 'all';
      this.chosenChane = {
        chane_id: 'all',
        chane_title: 'Все звенья',
        brigade_id: 'all',
        chaner_id: null,
        chane_type: null,
        workers: {}
      };
      this.chosenBrigade = brigadeObject;
      this.activeModal2 = "";
      this.activeModal = "";
    },
    // установка текущего звена и бригады выбранных в списке работников и их нарядов
    changeCurrentChaneBrigade(chaneBrigade) {
      this.currentBrigadeId = chaneBrigade.brigade_id;
      this.currentChaneId = chaneBrigade.chane_id;
    },

    addCyclogramInOrder(cyclogarmData) {
      console.log("table-form. methods. addCyclogramInOrder. Начало метода", cyclogarmData);
      console.time("addCyclogramInOrder");
      let cyclogramInfo = {
        brigade_id: this.chosenBrigade.brigade_id,
        department_id: this.chosenDepartment.id,
        date: getFormattedDate(this.chosenDate, 'hyphen', true),
        shift_id: this.chosenShift.id,
        cyclogram: cyclogarmData,
        chane_id: this.chosenChane.chane_id
      };
      this.$store.commit('setCyclogramTableFormData', cyclogramInfo);                                         // Сохранение данных в store
      console.timeEnd("addCyclogramInOrder");
      console.log("table-form. methods. addCyclogramInOrder. Конец метода");
    },
  },
  watch: {},
  created() {
  },
  beforeMount() {
    //this.ajaxGetBrigadesByCompanyDepartment(this.chosenDepartment.id);
  },
  mounted() {
    document.addEventListener('click', this.setListenerForDocumentClick);
    // $('.WorkersByDepartmentDropdown').draggable({containment: "#app", scroll: false});
    let subscribeArray = [];
    subscribeArray.push("worker_skud_in_out");
    webSocket.subscribeWebSocket(subscribeArray);
    // console.log("table-order. created Закончил");
  },
  beforeUpdate() {

  },
  beforeDestroy() {
    document.removeEventListener('click', this.setListenerForDocumentClick);
  }
}

</script>

<style scoped lang="less">
/*@page {*/
/*    margin: 1cm 0.5cm;*/
/*    padding: 0;*/
/*    !*size: landscape;*!*/
/*}*/

.page-background {
  left: 0;
  top: 30px;
  width: 100%;
  height: calc(100% - 30px);
  background: transparent;
  position: absolute;
  z-index: 1;
}


.tableFormWrapper {
  margin: 0 auto;
  min-width: 1200px;
  background-color: #fff;
  height: 100%;
  position: relative;
  display: block;
  @media print {
    min-width: 500px;           //!!!!!!!!!!!!!!!!!
  }

  &-max {
    width: calc(100% - 60px);
    position: absolute;
    top: 0;
    left: 30px;
    z-index: 1001;
    height: 99vh;

    .tableFormWrapper-content {
      height: calc(100vh - 120px);
    }
  }

  &-header {
    display: none;
    flex-direction: column;
    align-items: center;
    width: 100%;
    position: relative;
    @media print {
      margin-top: 80px;
      width: 92%;
      display: none; //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
      flex-direction: column;
      justify-content: center;
    }

    &-signatures {
      display: flex;
      width: 100%;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;

      &-consistently {
        margin-left: 30px;
        text-align: left;
      }

      &-confirm {
        margin-right: 50px;
        text-align: right;
      }
    }
  }

  &-content {
    display: flex;
    flex-wrap: wrap;
    height: 100%;
    min-height: 650px;
    width: 100%;
    @media print {
      display: none; //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
      height: calc(100% - 335px);
      min-height: 800px;
      /*flex-wrap: nowrap;*/
      /*min-width: 1380px;*/
      /*width: 1380px;*/
    }

    &-left {
      display: flex;
      flex-direction: column;
      height: 100%;
      width: 14%;
      /*              @media print {
                        height: 100%;
                        min-height: 100%;
                        width: 270px;
                    }*/

      &-executiveList {
        height: 25%;
        /* @media print {
             height: 35%;
             min-height: 35%;
         }*/
      }

      &-briefingList {
        margin-top: 4px;
        height: 30%;
        /* @media print {
             height: 25%;
             min-height: 25%;
         }*/
      }

      &-shiftCrew {
        margin-top: 4px;
        height: 45%;
        /* @media print {
             height: 40%;
             !*border: 5px solid black;*!
             min-height: 40%;
         }*/
      }
    }

    &-center {
      display: flex;
      flex-direction: column;
      height: 100%;
      width: calc(43% - 8px);
      margin-left: 4px;
      margin-right: 4px;
      @media print {
        height: 100%;
        min-height: 100%;
        width: 734px;
        border: 1px solid black;
      }

      &-orderList {
        height: 100%;
        @media print {
          height: 100%;
          min-height: 100%;
          width: 100%;
          border: 1px solid black;
        }
      }

      &-timeLine {
        //height: 45%;
        //min-height: 340px;
        border: 2px solid #56698F;
        margin-top: 4px;
        @media print {
          display: none;
          height: 0;
          min-height: 0;
        }
      }
    }

    &-right {
      display: flex;
      flex-direction: column;
      height: 100%;
      width: 43%;
      /*                @media print {
                          height: 100%;
                          !*width: 400px;*!
                          min-width: 680px;
                          margin-left: 4px;
                          width: 680px;
                          border: 1px solid black;
                      }*/

      &-warning {
        height: 25%;
        /*  @media print {
              height: 25%;
              min-width: 580px;
              width: 680px;
              border: 1px solid black;
          }*/
      }

      &-violation {
        margin-top: 4px;
        margin-bottom: 4px;
        height: 30%;
        /* @media print {
             height: 25%;
             min-width: 580px;
             width: 680px;
             border: 1px solid black;
         }*/
      }

      &-place {
        height: 45%;
        /*  @media print {
              height: 50%;
              width: 680px;
              min-width: 680px;
              !*min-width: 400px;*!
              !*width: 400px;*!
          }*/
      }
    }
  }

  &-footer {
    display: none;
    width: 100%;
    height: 60px;
    overflow: hidden;
    position: relative;
    color: #fff;
    align-items: center;
    page-break-after: always;
    @media print {
      display: none; //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
      min-width: 100%;
      width: 100%;
    }

    &-outfitIssued {
      color: #000;
      margin: 0 20px 0 30px;
    }

    &-outfitAccepted {
      color: #000;
    }

    &-bottom {
      position: absolute;
      width: 30%;
      height: 100%;
      padding: 10px 0;
      right: -30px;
      display: flex;
      @media (max-width: 1380px) {
        width: 40%;
      }

      &-save {
        width: 40%;
        transform: skew(-30deg);
        margin-right: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #7C6580;
        cursor: pointer;
        @media print {
          display: none;
        }

        &:hover {
          background-color: #9b7ea0;
        }

        span {
          transform: skew(30deg);
        }
      }

      &-print {
        width: 60%;
        padding-right: 30px;
        transform: skew(-30deg);
        margin-right: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #4D897C;
        cursor: pointer;
        @media print {
          display: none;
        }

        &:hover {
          background-color: #5BA293;
        }

        span {
          transform: skew(30deg);
          margin-left: 20px;
        }

        img {
          transform: skew(30deg);
          width: 30px;
        }
      }
    }
  }

  &-printedFormVoucher {
    width: 100%;
    display: none;
    @media print {
      display: block;
      width: 28cm;

      & > div {
        width: 100% !important;
        max-width: unset;
      }
    }
  }

  /*&-printedFormOrder {*/
  /*    width: 100%;*/
  /*    display: none;*/
  /*    @media print {*/
  /*        display: block;*/
  /*    }*/
  /*}*/
}

.brigade-option {
  .brigade-title {
    margin: 0;
    padding: 5px;
    text-align: left;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    background-color: #0003;

    &:hover {
      background-color: #00000015;
    }
  }
}

.collapsed-chain-list {
  padding-left: 20px;
  display: none;
  text-align: left;

  .chain-row {
    background-color: #fff;
    padding: 5px;
    margin: 0;
    border-bottom: 1px solid #fff;
    cursor: pointer;

    &:hover {
      background-color: #eeeeee;
    }
  }
}

.os-headerDropDownList {
  position: relative;
  z-index: 1;
  width: 100%;
  max-height: 200px;
  overflow-y: auto;
  background-color: #fff;
  box-shadow: 2px 2px 5px rgba(0, 0, 0, .5);

  p {
    margin: 0;
    padding: 5px;
    border-bottom: 1px solid #eee;
    cursor: pointer;

    &:hover {
      background-color: #eee;
    }
  }
}

@media (max-width: 1555px) {

  .tableFormWrapper {

    &-content {
      height: auto;
      background-color: white;

      &-left {
        height: auto;
        width: 24%;

        &-executiveList {
          height: 300px;
        }

        &-briefingList {
          height: 300px;
        }

        &-shiftCrew {
          height: 400px;
        }
      }

      &-center {
        height: auto;
        width: calc(76% - 4px);
        margin-right: 0;

        &-orderList {
          height: 100%;
          /*height: 550px;*/
        }

        &-timeLine {
          display: none;
          height: 0;
          /*height: 450px;*/
        }
      }

      &-right {
        margin-left: 0;
        height: auto;
        width: 100%;
        margin-top: 4px;

        &-warning {
          height: 350px;
        }

        &-violation {
          height: 350px;
        }

        &-place {
          height: 400px;
        }
      }
    }
  }
}

@media print {
  .tableFormWrapper {
    height: auto;
    background-color: white;

    &-content {
      height: auto;
      width: auto;
      flex-wrap: nowrap;
      min-width: 1380px;

      &-left {
        height: auto;
        min-height: auto;
        display: block;
        width: 270px;

        &-executiveList {

          height: auto;
        }

        &-briefingList {

          height: auto;
        }

        &-shiftCrew {
          height: auto;

        }
      }

      &-right {
        height: 100%;
        //width: 400px;
        min-width: 680px;
        margin-left: 4px;
        width: 680px;
        border: 1px solid black;

        &-warning {
          height: 25%;
          min-width: 580px;
          width: 680px;
          border: 1px solid black;
        }

        &-violation {
          height: auto;
          min-width: 580px;
          width: 680px;
          border: 1px solid black;
          min-height: 200px;
        }

        &-place {
          height: auto;
          width: 680px;
          min-width: 680px;
        }
      }
    }
  }
}
</style>
