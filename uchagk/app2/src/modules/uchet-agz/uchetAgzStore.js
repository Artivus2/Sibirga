import { createStore } from 'vuex'
 
import sendAjax from '../../plugins/api';

export default new createStore({

    state: { 
    activeModal: '',                            // активное модальное окно
    selectedReportId: 0,                      //выбранный отчет
    selectedMineId: 0,                      //выбранная шахта
    selectedSensorId: 0,

    
    },
    
    sensorsdata: {
        id: 0, 
        title: 0,
        zavod_id: 0, 
        madate: 0,
        location: 0,
        mine: 0
    },
    
 

getters: {


    ACTIVEMODAL: state => state.activeModal,                            // геттер по получению названия активного модального окна
    SELECTEDREPORTID: state => state.selectedReportId,                  // геттер по получению id выбранного отчета
    SELECTEDMINEID: state => state.selectedMineId,                  // геттер по получению id выбранной шахты
    SELECTEDSENSORID: state => state.selectedSensorId,                  // геттер по получению id выбранной шахты
    SENSORSDATA: state => state.sensorsdata,
   

},

actions: {
    /**
     * метод по установке активного модального окна
     * @param commit
     * @param activeModalName
     */
    setActiveModal({commit}, activeModalName) {    
                                                                             // экшн по установке наименования активного модального окна/выпадающего списка/контекстного меню
        commit('SETACTIVEMODAL', activeModalName);                                                                      // вызов мутации SETACTIVEMODAL с передачей наименования активного модального окна/выпадающего списка/контекстного меню
    },
     /**
     * метод по установке выбранного отчета как активного для просмотра статистики
     * @param commit - функция вызова мутации
     * @param selectedReportId (string) = id выбранного отчета
     */
    setActiveReportId({ commit }, selectedReportId) {
        console.log('uchetAgzStore.js, setActiveReportId',selectedReportId);
        commit('SETACTIVEREPORTID', selectedReportId);
    },
    setActiveMineId({ commit }, selectedMineId) {
        console.log('uchetAgzStore.js, setActiveMineId',selectedMineId);
        commit('SETACTIVEMINEID', selectedMineId);
    },
    setActiveSensorId({ commit }, selectedSensorId) {
        console.log('uchetAgzStore.js, setSelectedSensorId',selectedSensorId);
        commit('SETACTIVESENSORID', selectedSensorId);
    },
    setSensorsData({ commit }, selectedMineId) {
        //console.log('uchetAgzStore.js, setSelectedSensorId',selectedSensorId);
        commit('ajaxGetAllSensors', selectedMineId);
    },
    async ajaxGetAllSensors({ commit }, SensorConfig) {
        return new Promise((resolve, reject) => {
            let config = {
            // controller: 'admin\\admin',
            // method: 'get-test',
            url: '/sensor-info/get-test',
            //subscribe: '',
            //date_time_request: new Date(),
            //page_request: window.location.href,
            data: JSON.stringify(SensorConfig),
            method: 'get'
        };
            sendAjax(config)
            .then(resp => {
              
                resolve(resp)
                
                commit("SETSENSORSDATA", resp.data);
                console.log(resp)
                // if (SensorsData.status) {
                //     commit("SETSENSORSDATA", SensorsData.Items);
                // } else {
                //     commit("SETSENSORSDATA", {
                //         id: 0, 
                //         title: 0,
                //         zavod_id: 0, 
                //         madate: 0,
                //         location: 0,
                //         mine: 0
                //     });
                // }
            })
            .catch(err => {
                console.log(err)
                reject(err)
            })
          })
        
        
    },
   
},

mutations: {
    /**
     * мутация по установке активного модального окна
     * @param state
     * @param activeModalName
     */
    SETACTIVEMODAL(state, activeModalName) {
        state.activeModal = activeModalName;
    },
    /**
     * мутация по установке выбранного отчета как активного
     * @param state
     * @param selectedReportId
     * @constructor
     */
    SETACTIVEREPORTID(state, selectedReportId) {
        state.selectedReportId = selectedReportId;
    },
    SETACTIVEMINEID(state, selectedMineId) {
        state.selectedMineId = selectedMineId;
    },
    SETACTIVESENSORID(state, selectedSensorId) {
        state.selectedSensorId = selectedSensorId;
    },
    SETSENSORSDATA(state, newData) {
        
        state.sensorsdata = newData;
    },
    
}
})
