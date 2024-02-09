
// import OSStore from '../modules/order-system/store/OSStore.js'; // подключение модуля store нарядной системы
// import CDZStore from './modules/control-danger-zones/control-danger-zone.js'
//import DSStore from './modules/document-stats/document-stat'
//import DocStore from './modules/documents/documents'
//import PFSStore from './modules/prof-security/prof-security'
import { createStore } from 'vuex'
 

 
export default new createStore({

    state: () => ({         
                                                                                                         // константы - настройка АМИКУМ на фронт (настройки отчетов
    amicumDefaultsShifts: 3,                                                                       // количество смен по умолчанию на предприяти
    amicumDefaults: {
        countShift: 3,                                                                                  // количество смен по умолчанию на предприятии
        companyTitle: "ПАО ЮЖНЫЙ КУЗБАСС"                                                                          // Название управляющей компании
        },
        loginModalVisibilityFlag: false,                                                                                // флаг отображения формы авторизации для повторной авторизации
        userSessionData: JSON.parse(localStorage.getItem("serialWorkerData")),                                      // получаем сессию с бекэнда для заполнения вкладок в верхнем меню и для фильтрации


        /** Объект корректировки наряда используется в компоненте interactiveChainOrder на странице os-interactive-form
         * для отправки на бэк в метод CorrectOrder в контроллере OrderSystemController.
         * Свойства добавляются в этот объект по ходу корректировки **/
        correctOrder: {                                                                                                 // Объект в state
            correct_order: {                                                                                            // Объект для отправки на сервер
                order_status_description: null,                                                                         // Описание причины корректировки наряда
                company_department_id: null,                                                                            // id департамента
                order_id: null,                                                                                         // id наряда
                attachment: null,                                                                                       // Вложение
                brigade_id: null,                                                                                       // id бригады
                new_order_place: null,                                                                                  // Объект, содержащий новые места (order_place), новые работники с операциями (order_workers)
                correct_exist_order: null,                                                                              // Объект с данными о корректировке существующих работников (и операций)
                add_worker_at_order: null,                                                                              // Объект с добавленными сотрудниками
                new_instructions: null                                                                                  // Объект с новыми инструктажами
            }
        },
        activeModal: '',                                                                                                // активная вкладка 1 уровня
        activeModal2: '',                                                                                               // активная вкладка 2 уровня
        handbookChaneType: {},                                                                                          // справочник типов звеньев
        handbookWorkers: {},                                                                                            // список всех воркеров в справочнике
        allCompaniesAndDepartments: [],                                                                                 // список департаментов и подразделений используется для модалок и выпадашек
        allCompaniesAndDepartmentsWithWorkers: {},                                                                      // список всех компаний/департаментов и их работников используется для списка в модалке работников
        allCompaniesAndDepartmentsWithOutWorkers: {},                                                                   // список всех компаний/департаментов БЕЗ работников используется для списка в модалке департаментов
        companyDepartmentsList: [],
        /** выбранный департамент **/
        currentDepartment: {
            id: 101,                   // переменная из сессии - текущий id конкретного департамента работника
            title: "Прочее",                       // переменная из сессии - текущее название конкретного департамента работника
            brigade_list: {}
        },
        chosenBrigadeTemplate: {
            brigade_id: '',
            brigade_description: '',
            brigader_id: '',
            flag_save: false,
            chanes: {}
        },                                                                                                              // Шаблон бригады
        newBrigadeLength: 0,                                                                                            // индекс для добавления бригады
        shiftsContext: {},                                                                                              // список смен для выпадающего списка
        kindWorkersTime: {},                                                                                            // список видов рабочего времени
        listWorkingTime: {},                                                                                            // список рабочего времени
        roles: [],                                                                                                      // список ролей из справочника (array)
        handbookRoles: {},                                                                                              // справочник ролей object
        handbookObject: {},                                                                                             // справочник видов мест
        handbookPlast: {},                                                                                              // справочник пластов
        handbookMine: {},                                                                                               // справочник шахтных полей
        allKindPlacesList: {},                                                                                          // список мест для выпадающего списка
        handbookCompany: {},                                                                                            // справочник компаний
        handbookUnit: [],                                                                                               // справочник единиц измерения
        handbookSeason: {},                                                                                             // справочник сезонов
        handbookUnitObject: {},                                                                                         // справочник единиц измерения объект
        handbookDocument: {},                                                                                           // справочник документов
        handbookKindSiz: {},                                                                                            // справочник видов СИЗ
        handbookSubgroupSiz: {},                                                                                        // справочник подгрупп СИЗ
        allRescuers: {},                                                                                                // список всех работников ВГК на смене в данном участке - применяется при выдаче наряда
        attendanceChainPerShift: [],                                                                                    // выхождаемость звена
        instructionsPB: {},                                                                                             // инструктажи
        chaneTemplate: undefined,                                                                                       // шаблон графика за предыдущий месяц
        newTemplate: undefined,                                                                                         // новый шаблон графика за текущий месяц
        OperationListForDropdown: {},                                                                                   // список операций, сгруппированных по типу и виду, для выпадающего списка
        undergroundPlaceList: {},                                                                                       // классификатор мест по типу подземная места
        operationsList: {},                                                                                             // справочник операций
        chosenDate: '',                                                                                                 // выбранная дата
        stopUpdateVGK: 0,                                                                                               // принудительная остановка обновления списка ВГК при достижении 0 списка людей
        chainOrder: {},                                                                                                 // групповой наряд в интерактивке
        places: {},                                                                                                     // Список мест без вложенностей
        handbookPlace: {},                                                                                              // Список мест без ППК ПАБ
        allWorkers: {},
        orderForTableForm: {
            ListWorkersByGrafic: {},                                                                                    // список работников из графика
            department_order: {},                                                                                       // список нарядов
            injunctions: {},                                                                                            // Список предписаний
            worker_vgk: []                                                                                              // список членов ВГК
        },                                                                                                              // объект наряда для табличной формы
        aerologicalSafetyOperations: {},                                                                                // объект для хранения списка операций на линий АБ(ВТБ)

        orderWorkersGroupedByChains: {},
        dragOperation: {                                                                                                // переменная буфер для перетаскивания операций на работника
            dragType: '',                                                                                               // тип операции драг place, worker, operation
            orderPlaceId: -1,
            orderOperationId: -1,
            selectedOrderOperations: [],                                                                                // массив перетаскиваемых orderOperationId
            placeId: null,
            operationId: null,
            operationIndex: null,
        },
        dragWorker: {
            dragType: '',                                                                                               // тип операции драг place, worker, operation, chane
            selectedWorkers: [],                                                                                        // массив перетаскиваемы workerId
            workerId: null,                                                                                             // ключ перетаскиваемого работника
            chaneId: null,                                                                                              // звено перетаскиваемого работника/ов
            brigadeId: null,                                                                                            // бригада перетаскиваемого работника/ов
        },
        // эталон
        dragOperationItem: {                                                                                            // переменная буфер для перетаскивания операций на работника
            dragType: '',                                                                                               // тип операции драг place, worker, operation
            orderPlaceId: 0,
            placeId: 0,
            orderOperationId: 0,
            operationId: 0,
            selectedOrderOperations: [],                                                                                // массив перетаскиваемых orderOperationId
            operationIndex: 0,
        },
        dragWorkerItem: {
            dragType: '',                                                                                               // тип операции драг place, worker, operation, chane
            brigadeId: 0,                                                                                               // бригада перетаскиваемого работника/ов
            chaneId: 0,                                                                                                 // звено перетаскиваемого работника/ов
            workerId: 0,                                                                                                // ключ перетаскиваемого работника
            selectedWorkers: [],                                                                                        // массив перетаскиваемы workerId
        },
        /**************Раздел справочников ******************/
        eventsData: {},                                                                                                 // ассоциативный массив для хранение данных по событиям
        equipmentListGroup: {},                                                                                         //  справочника оборудования сгруппированного по типа и объектам
        handbookEquipment: {},                                                                                          //  справочника оборудования без группировок
        typeOperationsData: {},                                                                                         // ассоциативный массив для хранения данных по операциям
        actualParameters: {},                                                                                           // ассоциативный массив для хранения данных по параметрам
        handbookSiz: {},                                                                                                // справочник средств индивидуальной защиты
        /**************Раздел уведомлений******************/
        notificationInjunction: {},                                                                                     // список предписаний для окна предписаний
        notificationPAB: {},                                                                                            // список ПАБов для окна ПАБ
        notificationAudit: {},                                                                                          // список объектов для проведения аудита для окна "Запланирован аудит"
        notificationEpb: {},                                                                                            // список ЭПБ для окна ЭПБ
        notificationSiz: {},                                                                                            // список СИЗов для окна СИЗ
        handbookPlaceList: {},
        placeIterator: 0,
        operationIterator: 0,
        shiftsList: {
            "1": {
                id: 1,
                title: 'I смена'
            },
            "2": {
                id: 2,
                title: 'II смена'
            },
            "3": {
                id: 3,
                title: 'III смена'
            },
            "4": {
                id: 4,
                title: 'IV смена'
            },
        },                                                                                                              // список смен для горизонтального меню
    }),

})
