/**
 * Модуль для страницы "Отчет за предыдущий период"
 */

const state = {
    previousPeriodReport: {},                                                                                           // объект хронящий информации по отчету за предыдущий период
    dataReportMonth: {},                                                                                                // объект хранящий информацию по отчету за месяц
};

const actions = {
    /**
     *  actions получение данных по отчету за предыдущий период
     *  @payload - полезная нагрухка несет в себе данные по:
     *  dateTime - дата
     *  brigadeId - id бригады
     *  companyDepartmentId - id департамента
     * */
    async getPreviousPeriodReport({commit}, payload) {
        let config = {
            controller: 'ordersystem\\ReportForPreviousPeriod',
            method: 'GetBrigadeStatistic',
            subscribe: 'worker_list',
            date_time_request: new Date(),
            page_request: window.location.href,
            data: JSON.stringify({
                shift_id: null,
                company_department_id: payload.companyDepartmentId,
                brigade_id: payload.brigadeId,
                mine_id: payload.mineId,
                date: payload.dateTime.toLocaleDateString('ru')
            })
        };
        let data = await sendAjax(config);
        if(data.status===1) {
            await commit('setPreviousPeriodReport', data.Items);
        } else {
            await commit('setPreviousPeriodReport', {});
            showNotify("Нет нарядов на данную дату и участок или не заполнены ни одна операция", 'danger');
        }
    },

    /**
     * actions получение данных работы бригады за месяц
     * **/
    async getDataReportMonth({commit}, payload) {
        let config = {
            controller: 'ordersystem\\ReportForPreviousPeriod',
            method: 'GetCoalMiningReportByMonth',
            subscribe: 'worker_list',
            date_time_request: new Date(),
            page_request: window.location.href,
            data: JSON.stringify({
                company_department_id: payload.companyDepartmentId,
                brigade_id: payload.brigadeId,
                mine_id: payload.mineId,
                report_year: payload.dateTime.getFullYear(),
                report_month: payload.dateTime.getMonth() + 1,
                place_id: 6187 // TODO необходимо передвать placeId
            })
        };
        let data = await sendAjax(config);
        //console.log(`получение данных работы бригады за смену getDataReportMonth - ${data.Items}`);
        await commit('setDataReportMonth', data.Items);
    },
};

const mutations = {

    /**
     * Сохранение данных в стор данных по отчету за предыдущий период
     * @param state - состояние данных store
     * @param payload - полезная нагрузка, данные отчета за выбранный день
     */
    setPreviousPeriodReport(state, payload) {
        let error = false;
        try {
            if (Object.keys(payload).length) {                                                                      // если есть данные
                if (Object.keys(payload.cyclogram).length) {

                    let amountStop = 0,                                                                                 // сумма простоев
                        amountWorks = 0,                                                                                // сумма работ
                        cyclogramShifts = payload.cyclogram[2].shifts,                                                     // присваиваем операции по сменам
                        cyclogramOperation = payload.cyclogram[2].cyclograms_operations;                                   // присваиваем список операций
                    /*
                    * цикл для формирования суммы времены работ и простоев за смену, а так же форматирование дат
                    * */
                    for (let idShift in cyclogramShifts) {
                        let elementShift = cyclogramShifts[idShift];
                        elementShift.cyclogram_option.date_time_start = new Date(elementShift.cyclogram_option.date_time_start);// преобразование строкового значения в объект даты начала работ
                        elementShift.cyclogram_option.date_time_end = new Date(elementShift.cyclogram_option.date_time_end);    // преобразование строкового значения в объект даты окончания работ

                        for (let idChain in elementShift.chanes_cyclogram) {
                            let elementChain = elementShift.chanes_cyclogram[idChain];
                            for (let idOperation in elementChain.cyclograms_operations) {
                                let elementOperation = cyclogramOperation[idOperation];
                                elementOperation.date_time_start = new Date(elementOperation.date_time_start);          // преобразование строкового значения в объект даты начала операции
                                elementOperation.date_time_end = new Date(elementOperation.date_time_end);              // преобразование строкового значения в объект даты окончания операции
                                if (elementOperation.type_operation_id == 8) {
                                    amountStop += (elementOperation.date_time_end.valueOf() - elementOperation.date_time_start.valueOf()) / 1000 / 60;
                                } else if (elementOperation.type_operation_id == 1 ||
                                    elementOperation.type_operation_id == 2 ||
                                    elementOperation.type_operation_id == 4 ||
                                    elementOperation.type_operation_id == 9 ||
                                    elementOperation.type_operation_id == 10) {
                                    amountWorks += (elementOperation.date_time_end.valueOf() - elementOperation.date_time_start.valueOf()) / 1000 / 60;
                                }
                            }


                            elementShift.cyclogram_option.amountStop = amountStop;                                      // присваивание получившейся суммы простоев за смену
                            elementShift.cyclogram_option.amountWorks = amountWorks;                                    // присваивание получившейся суммы работ за смену
                        }

                        amountStop = 0;                                                                                     // обнуление результатов простоев за смену
                        amountWorks = 0;                                                                                    // обнуление результатов работы за смену
                    }
                } else if (Object.keys(payload.planogramm).length) {
                    let amountStop = 0,                                                                                 // сумма простоев
                        amountWorks = 0,                                                                                // сумма работ
                        planogrammShifts = payload.planogramm[4].shifts,                                                // присваиваем операции по сменам
                        planogrammEquipments = payload.planogramm[4].equipments;                                        // присваиваем список операций
                    for (let idShift in planogrammShifts) {
                        let itemShift = planogrammShifts[idShift];

                        itemShift.planogramm_option.date_time_start = new Date(itemShift.planogramm_option.date_time_start);// преобразование строкового значения в объект даты начала работ
                        itemShift.planogramm_option.date_time_end = new Date(itemShift.planogramm_option.date_time_end);    // преобразование строкового значения в объект даты окончания работ

                        for (let idEquipment in itemShift.equipments) { // цикл по оборудованию
                            let itemEquipment = itemShift.equipments[idEquipment]
                            for (let idOperation in itemEquipment.planogramms_operations) { // цикл по операциями
                                // let itemOperation = itemEquipment.planograms_operations[idOperation]
                                let itemOperation = planogrammEquipments[idEquipment].planogramm_operation[idOperation]; // нахождение объекта операциии
                                itemOperation.date_time_start = new Date(itemOperation.date_time_start);                  // конвертация в объект даты
                                itemOperation.date_time_end = new Date(itemOperation.date_time_end);                      // конвертация в объект даты

                                if (itemOperation.type_operation_id == 8) {
                                    amountStop += (itemOperation.date_time_end.valueOf() - itemOperation.date_time_start.valueOf()) / 1000 / 60;
                                } else  {                                                                                   // TODO  необходимо возможно будет определить какие операции будут считаться рабочими
                                    amountWorks += (itemOperation.date_time_end.valueOf() - itemOperation.date_time_start.valueOf()) / 1000 / 60;
                                }
                            }
                        }
                        itemShift.planogramm_option.amountStop = amountStop / Object.keys(itemShift.equipments).length;   // присваивание получившейся суммы простоев за смену
                        itemShift.planogramm_option.amountWorks = amountWorks / Object.keys(itemShift.equipments).length; // присваивание получившейся суммы работ за смену

                        amountStop = 0;                                                                                     // обнуление результатов простоев за смену
                        amountWorks = 0;                                                                                    // обнуление результатов работы за смену
                    }
                    console.log('---------------------------------------')
                    console.log(planogrammShifts)
                    console.log(planogrammEquipments)
                }
            }

        } catch (e) {
            if (error) console.log('Ошибка подсчета суммы простоев и работ', e)
        }
        try {
            if (Object.keys(payload).length) { // установка флага для отображения мест с операциями в списке
                let operationShift = payload.statistic.operation_shift;
                for (let placeId in operationShift) {
                    let placeItem = operationShift[placeId]
                    placeItem.place_show = true;
                }
            }

        } catch (e) {
            if (error) console.log('Ошибка установки флага какие смены необходимо отображать', e)
        }

        state.previousPeriodReport = payload;                                                                           // запись в стор полученных данных по отчету
    },
    /**
     * мутация на смену статуса отображения мест в операция по сменам settShowPlace
     * payload  - id операции
     * */
    setShowPlace(state, payload) {
        //console.log('сработала мутация на смену статуса отображения мест в операция по сменам setShowPlace', payload)
        try {
            let place = state.previousPeriodReport.statistic.operation_shift[payload];
            if (place) {
                place.place_show = !place.place_show
            }
            //console.log('итог работы мутации на смену статуса отображения мест в операция по сменам tShowPlace', place)
        } catch (e) {
            //console.log('ошибка в мутации на смену статуса отображения мест в операция по сменам tShowPlace', e)
        }
    },

    /**
    * Сохранение показаттеели работы бригады за месяц в стор
     * @param state - состояние данных store
     * @param payload - полезная нагрузка, данные отчета за месяц
    * */
    setDataReportMonth(state, payload) {
        state.dataReportMonth = payload;
    },
};

const getters = {

};

const modules = {
};

export default {
    namespaced: true,
    state,
    actions,
    mutations,
    getters,
    modules
}