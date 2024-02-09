import Vue from 'vue';
import Vuex from "vuex";


const state = {
    activeModal: '',                                                                              // активное модальное окно/выпадающий список/контекстное меню
    chosenDate: {
        year: new Date().getFullYear(),
        monthTitle: new Date().toLocaleString('ru', {
            month: 'long'
        })
    },                                                                                                     // выбранная дата (год и месяц)
    handbookDepartment: {},                                                                                             // справочник департаментов

    allOrdersInCompany: {},
    // allOrdersVtbAb: {},
    ordersObject: {},                                                                                                   // главный объект истории нарядов - используется для получения полной истории наряда при выборе (смена, участок, дата)
    ordersArray: [],                                                                                                    // главный массив истории нарядов - используется для построения списка
    chosenMine: {                                                                                                       // выбранная шахта
        id: JSON.parse(localStorage.getItem('serialWorkerData')).userMineId,                                        // id шахты
        title: JSON.parse(localStorage.getItem('serialWorkerData')).userMineTitle,                                  // название шахты
    },

};
const getters = {
    CHOSENMINE: state => state.chosenMine,                                                                              //геттер по умолчанию выбранной шахты
    ACTIVEMODAL: state => state.activeModal,                                                                            // геттер по получению наименования активного модального окна/выпадающего списка/контекстного меню
    CHOSENDATE: state => state.chosenDate,                                                                              // геттер по получению выбранной даты
    HANDBOOKDEPARTMENT: state => state.handbookDepartment,                                                              // Получение данных департаментов
    CHOSENDEPARTMENT: state => state.chosenDepartment,
    ALLORDERSINCOMPANY: state => state.allOrdersInCompany,
    // ALLORDERSVTBAB: state => state.allOrdersVtbAb,
    ORDER_ARRAY: state => state.ordersArray,                                                                            // получение массива данных по наряду для построения списка
    ORDER_OBJECT: state => state.ordersObject,                                                                          // получение объекта данных

};
const actions = {
    setChosenMine({commit}, newValue) {                                                                                 // вызов мутации SETCHOSENMINE с передачей объекта выбранной шахты
        commit('SETCHOSENMINE', newValue);
    },
    setActiveModal({commit}, activeModalName) {                                                                         // экшн по установке наименования активного модального окна/выпадающего списка/контекстного меню
        commit('SETACTIVEMODAL', activeModalName);                                                                      // вызов мутации SETACTIVEMODAL с передачей наименования активного модального окна/выпадающего списка/контекстного меню
    },
    setDate({commit}, dateObject) {                                                                                     // экшн по установке выбранной даты
        commit('SETDATE', dateObject);                                                                                  // вызов мутации SETDATE с передачей объекта выбранной даты
    },
    changeDepartment({commit}, departmentObject) {
        commit('CHANGEDEPARTMENT', departmentObject);
    },
    /**
     * Метод отправки запроса на получение депратаментов
     * @param commit
     * @param payload
     * @returns {Promise<void>}
     */
    async ajaxGetCompanyList({commit}) {
        let config = {
            controller: 'handbooks\\HandbookEmployee',
            method: 'GetCompanyList',
            subscribe: '',
            date_time_request: new Date(),
            page_request: window.location.href,
            data: JSON.stringify({})
        };
        let handbookDepartment = await sendAjax(config);
        await commit('SETHANDBOOKDEPARTMENT', handbookDepartment.Items);
    },


    /**
     * Запрос на получение данных по нарядам
     * @param commit
     * @param order
     * @returns {Promise<void>}
     */
    async getOrderInfo({commit}, order) {
        let config = {
            controller: 'ordersystem\\OrderSystem',
            method: 'GetRouteMap',
            subscribe: '',
            date_time_request: new Date(),
            page_request: window.location.href,
            data: JSON.stringify({
                company_department_id: order.company_department_id,
                year: order.year,
                month: order.month,
                mine_id: order.mine_id,
            })
        };

        let orderInfo = await sendAjax(config);
        // проверка на наличие статуса равного 1
        if (orderInfo.status===1) {                                                                                     // если статус 1
            await commit('INIT_ROUTE_MAP', orderInfo.Items);                                                            // то инициализируется мутация с исходными данными
        } else {                                                                                                        // иначе
            await commit('INIT_DEFAULT');                                                                               // инициализируется дефолтная мутация
        }
    },
};
const mutations = {
    SETCHOSENMINE(state, mineObject) {                                                                                  //установка нового значения шахты по умолчанию
        state.chosenMine = mineObject;
        if (hasProperty(localStorage, 'serialWorkerData')) {
            let serialWorkerData = {};
            serialWorkerData = JSON.parse(localStorage.getItem("serialWorkerData"));

            serialWorkerData.userMineId = state.chosenMine.id;
            serialWorkerData.userMineTitle = state.chosenMine.title;

            localStorage.setItem("serialWorkerData", JSON.stringify(serialWorkerData));
        }
    },
    SETACTIVEMODAL(state, activeModalName) {
        state.activeModal = activeModalName;
    },
    SETDATE(state, dateObject) {
        state.chosenDate = dateObject;
    },
    // заполнение департаментов
    SETHANDBOOKDEPARTMENT(state, handbookDepartment) {
        state.handbookDepartment = handbookDepartment;
    },
    CHANGEDEPARTMENT(state, departmentObject) {
        state.chosenDepartment = departmentObject;
    },
    SETALLORDERSINCOMPANY(state, allOrdersInCompany) {
        state.allOrdersInCompany = allOrdersInCompany;
    },
    // SETALLORDERSVTBAB(state, allOrdersVtbAb) {
    //     state.allOrdersVtbAb = allOrdersVtbAb;
    // },

    /**
     * Инициальзация входных данных по наряду
     */
    INIT_ROUTE_MAP(state, orderInfo) {
        state.ordersArray = orderInfo.route_map_array;
        state.ordersObject = orderInfo.route_map_object;
    },

    /**
     * Инициализация занченией по умолчанию на случай ошибки получения данных по наряду
     */
    INIT_DEFAULT(state) {
        state.ordersArray = [];
        state.ordersObject = {};
    },
};
/**
 * Блок по подключению элементов стора на странице Учёт травматизма и происшествий
 */
export default new Vuex.Store({
    namespaced: true,
    state,
    getters,
    actions,
    mutations
});
