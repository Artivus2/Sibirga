<!-- Страница интерактивной формы
     используется для отображения схемы шахты и работы с её элементами
-->
<template>
    <div class="pageWrapper max-content">
        <div class="interactiveTableFormWrapper" ref="windowTableForm" :class="maxWindowInteractive">
            <!--      Шапка      -->
            <div class="header">
                <interactive-header-menu
                        @maxWindow = "setMaxWindow"
                        @closeWindow = "closeWindow"
                        @processingData = "processingData"
                        @changeActiveModal = "changeActiveModal"


                        :maxWindow = 'maxWindow'
                        :activeModal = "activeModal"
                        :titleHeaderMenu="''"
                ></interactive-header-menu>
            </div>

            <div class="topbar" :style="[{width: '100%'}, {height: '30px'}, {border: '1px solid #e3e3e3'}]" >
<!--                <button class="btn btn-info" @click="sendToWebSocket()">SW</button>-->
<!--                <button class="btn btn-info" @click="showInjunctionTable()">Show injunction</button>-->
                <button class="btn btn-info" @click="showIndividualOrder()">Show individual order</button>
<!--                <button class="btn btn-info" @click="saveDataForUnity()">Update</button>-->
<!--                <button class="btn btn-warning" @click="callMethodInUnity()">Show</button>-->
                <button class="btn btn-warning" @click="showOrderInConsole()">Show order</button>
<!--                <button class="btn btn-warning" @click="sendOrderToState()">Send order</button>-->
<!--                <input id="orderJson" type="text" style="height: 100%; width: 200px" placeholder="ORDER JSON">-->
<!--                <input id="orderChainId" type="text" style="height: 100%; width: 70px" placeholder="CHAIN_ID">-->
<!--                <button class="btn btn-warning" @click="callMoveChain()">SendMoveChain</button>-->
                <p style="font-size: 8px;">Версия от 22.10.2019 16:55</p>
            </div>

            <!-- Блок со схемой Unity -->
            <div class="webgl-content" id="webglContent">
                <div id="gameContainer"
                     @dragover.prevent="dragover_prevent()"
                     @drop.prevent="drop_prevent()"
                     @drag.end="testprevent()"
                     @drop.stop
                >
                </div>
            </div>
        </div>

        <!-- Список по профессиям работников в шахте -->
        <div class="worker-list-grouped">
            <interactive-worker-list-grouped-by-role
                    :orderWorkers="orderWorkers"
                    :orderPlaces="orderPlaceAndOperations"
                    :outgoingInfo="orderBrigade.outgoing"
                    :handbookRoles="handbookRoles"/>
        </div>

        <!--                    :workerRescuersList="workerRescuersList"-->
        <!-- Модальное окно выбора персонала -->
        <interactive-employee-selection
                v-show="activeModal === 'employeeSelection' || secondActiveModal === 'employeeSelection'"

                :orderPlaceDecrementId="orderPlaceDecrementId"
                :orderOperationDecrementId="orderOperationDecrementId"
                :operationWorkerDecrementId="operationWorkerDecrementId"
                @decrementOrderPlaceId="decrementOrderPlaceId"
                @decrementOrderOperationId="decrementOrderOperationId"
                @decrementOperationWorkerId="decrementOperationWorkerId"

                :dataObjectFromOrder="dataObjectFromOrder"
                :workersInOrder="orderWorkers"
                :operationsInOrder="allOperationsInOrder"
                :chosenBrigade="chosenBrigade"
                :chosenChain="chosenChain"
                :places="places"
                :onlyOperationsList="onlyOperationsList"
                :handbookRoles="handbookRoles"
                :roles="roles"
                :allWorkers="workers"

                @addWorkerInOrder="addWorker"
                @delWorkerFromOrder="delWorkerFromOrder"
                @selectOperationForWorker="selectOperationForWorker"
                @unselectOperationForWorker="unselectOperationForWorker"
                @changeActiveModal="changeActiveModal"
                @changeRoleWorker="changeRoleWorker"
                @sendWorkerToUnity="sendWorkerToUnity"
                @openCardInfoWorker="openCardInfoWorker"
        />

        <!--        @addOperation="addOperation"-->
        <!--        @addNewPlace="addNewPlace"-->
        <interactive-operations-selection
                v-show="activeModal === 'operationsSelection' || secondActiveModal === 'operationsSelection'"

                :orderPlaceDecrementId="orderPlaceDecrementId"
                :orderOperationDecrementId="orderOperationDecrementId"
                @decrementOrderPlaceId="decrementOrderPlaceId"
                @decrementOrderOperationId="decrementOrderOperationId"

                :dataObjectFromOrder="dataObjectFromOrder"
                :orders="orders"
                :places="places"
                :workers="workers"
                :onlyOperationsList="onlyOperationsList"
                :chosenDate="chosenDate"
                :chosenShift="chosenShift"
                :chosenDepartment="chosenDepartment"
                :chosenBrigade="chosenBrigade"
                :chosenChainId="chosenChain.chane_id"
                @changeActiveModal="changeActiveModal"
        />

        <!--        :placeOperations="orderPlaceAndOperations"-->
        <!--        @change="orders"-->

        <!--      Боковое левое меню интерактивной формы      -->
<!--        v-if="orders.department_order"-->
        <div class="leftSideMenu">
            <!--      Кнопка открытия окна выбора персонала      -->
            <div class="selectEmployeeButton" >
                <a
                        @click="openEmployeeSelectionModal()"
                >
                    <img class="selectEmployeeButton-background"
                         src="../../assets/interactiveForm/select_employee-w.png" alt="Выбор персонала">
                    <div class="buttonTitle">
                        <p>выбор персонала</p>
                    </div>
                </a>
            </div>

            <!--      Кнопка открытия окна выбора задач      -->
<!--            v-if="orders.department_order"-->
            <div class="selectOperationsButton" >
                <a
                        @click="changeActiveModal('operationsSelection')"
                >
                    <img class="selectOperationsButton-background"
                         src="../../assets/interactiveForm/Choice_of_tasks_w.png" alt="Выбор задач">
                    <div class="buttonTitle">
                        <p>выбор задач</p>
                    </div>
                </a>
            </div>

        </div>

        <!--      Боковое правое меню интерактивной формы      -->
        <div class="rightSideMenu">
            <!--      Кнопка открытия модального окна просмотра и корректировки наряда      -->
<!--            v-if="orders.department_order"-->
            <div class="viewOrderButton" >
                <a
                        @click="setDataForInitializationCorrectionOrder()"
                >
                    <img class="viewOrderButton-background"
                         src="../../assets/interactiveForm/view_order.png" alt="Просмотреть наряд">
                    <div class="buttonTitle">
                        <p>просмотреть наряд</p>
                    </div>
                </a>
            </div>
            <div class="viewInstructionsButton" >
                <a
                        @click="toggleInstructionsModal()"
                >
                    <img class="viewInstructionsButton-background"
                         src="../../assets/interactiveForm/instructions.png" alt="Просмотреть наряд">
<!--                    <div class="buttonTitle">-->
<!--                        <p>просмотреть наряд</p>-->
<!--                    </div>-->
                </a>
            </div>

        </div>

        <!--      Компонент модального окна корректировки наряда      -->
<!--        :chainAttendance="chainAttendance"-->
        <group-order
                v-if="orders.department_order"

                :orderPlaceDecrementId="orderPlaceDecrementId"
                :orderOperationDecrementId="orderOperationDecrementId"
                @decrementOrderPlaceId="decrementOrderPlaceId"
                @decrementOrderOperationId="decrementOrderOperationId"

                :dataObjectFromOrder="dataObjectFromOrder"
                :chosenDate="chosenDate"
                :chosenShift="chosenShift"
                :chosenDepartment="chosenDepartment"
                :chosenBrigade="chosenBrigade"
                :chosenChain="chosenChain"
                :correctOrder="correctOrder"
                :orderStatus="orderStatus"
                :orders="orders"
                :places="places"
                :workers="workers"
                :roles="roles"

                :allInstructions="allInstructions"
                :onlyOperationsList="onlyOperationsList"

                :handbookRoles="handbookRoles"
                :orderWorkers="orderWorkers"
                :orderPlaces="orderPlaceAndOperations"
                :outgoingInfo="orderBrigade.outgoing"

                @getOrder="getOrder"
        />
        <employee-card
                v-if="thirdActiveModal==='cardEmployee'"
                :idWorker="selectedWorkerId"
                @changeActiveModal="thirdActiveModal = ''"
        />
        <injunction-table
                v-if="thirdActiveModal==='injunctionTable'"
                :injunction-id="injunctionId"
                @close-view-injunction-modal="thirdActiveModal = ''"
        />
<!--        :selectedWorkerId="selectedWorkerId"-->
        <individual-order
                v-if="orders.department_order"

                :orderPlaceDecrementId="orderPlaceDecrementId"
                :orderOperationDecrementId="orderOperationDecrementId"
                :operationWorkerDecrementId="operationWorkerDecrementId"
                @decrementOrderPlaceId="decrementOrderPlaceId"
                @decrementOrderOperationId="decrementOrderOperationId"
                @decrementOperationWorkerId="decrementOperationWorkerId"

                :dataObjectFromOrder="dataObjectFromOrder"
                :workersInOrder="orderWorkers"
                :allWorkers="workers"
                :correctOrder="correctOrder"
                :chosenDate="chosenDate"
                :chosenShift="chosenShift"
                :chosenDepartment="chosenDepartment"
                :chosenBrigade="chosenBrigade"
                :chosenChain="chosenChain"
                :orderStatus="orderStatus"
                :orders="orders"
                :places="places"
                :allInstructions="allInstructions"
                :onlyOperationsList="onlyOperationsList"
                :selectedWorkerId="'1801'"
                @getOrder="getOrder"
        />
        <!--      Компонент инструктажей по наряду      -->
            <instructions-modal
                    v-if="thirdActiveModal === 'instructionsModal'"
                    :editMode="true"
                    :orderStatus="orderStatus"
                    :orders="orders"
                    :allInstructions="allInstructions"
                    :chosenChainId="chosenChain.chane_id"
                    @toggleInstructionsModal="toggleInstructionsModal"
            />

    </div>
</template>


<script>
    const interactiveHeaderMenu = () => import("@/components/orderSystem/interactiveOrderSystem/interactiveHeaderMenu.vue");
    const groupOrder = () => import("@/components/orderSystem/interactiveOrderSystem/groupOrder.vue");                  // Подключение компонента модального окна редактирования наряда
    const interactiveEmployeeSelection = () => import("@/components/orderSystem/interactiveOrderSystem/interactiveEmployeeSelection.vue"); // Подключение компонента окна выбора сотрудников
    const interactiveOperationsSelection = () => import("@/components/orderSystem/interactiveOrderSystem/interactiveOperationsSelection.vue"); // Подключение компонента окна выбора задач
    const interactiveWorkerListGroupedByRole = () => import("@/components/orderSystem/interactiveOrderSystem/interactiveWorkerListGroupedByRole.vue");  // Подключение компонента отображения выхождаемости по ролям
    const employeeCard = () => import('@/components/orderSystem/employeeCard/employeeCard.vue');                        // Подключение компонента карточка сотрудника
    const injunctionTable = () => import('@/components/bookDirectiveModule/viewInjunctionModal.vue');                   // Подключение компонента отображения предписания
    const instructionsModal = () => import("@/components/orderSystem/interactiveOrderSystem/interactiveShiftBriefingModal.vue");                  // Подключение компонента модального окна списка инструктажей

    //** Блок подключения модального окна с индивидуальным нарядом **//
    const individualOrder = () => import("@/components/orderSystem/interactiveOrderSystem/individualOrderModal/individualOrder.vue");   // Подключение компонента отображения индивидуального наряда

    import webSocket from '@/service/webSocket.js'

    export default {
        components: {                                                                                                   // Подключенные компоненты
            interactiveHeaderMenu,
            groupOrder,                                                                                                 // Модальное окно корректировки наряда
            interactiveEmployeeSelection,                                                                               // Модальное окно выбора персонала
            interactiveOperationsSelection,                                                                             // Модальное окно выбора задач
            interactiveWorkerListGroupedByRole,                                                                         // Окно выхождаемости звена
            employeeCard,                                                                                               // Модальное окно карточки сотрудника
            injunctionTable,                                                                                            // Модальное окно отображения предписания
            individualOrder,                                                                                            // Модальное окно отображения индивидуального наряда
            instructionsModal,                                                                                          // Модальное окно инструктажей
        },
        data() {
            return {
                orderPlaceDecrementId: -10000,                                                                          // Глобавльная переменная для создания нового места (используется во всех дочерних компонентах, где есть добавление мест)
                orderOperationDecrementId: -10000,                                                                      // Глобавльная переменная для создания новой операции (используется во всех дочерних компонентах, где есть добавление операций)
                operationWorkerDecrementId: -10000,                                                                      // Глобавльная переменная для создания новой операции (используется во всех дочерних компонентах, где есть добавление операций)

                loaded: false,
                debug_mode: false,
                maxWindow: false,                                                                                       // Переменная для задания максимальной ширины окна
                maxWindowInteractive: '',                                                                               // переменная для максимального разрешения
                activeModal: '',                                                                                        // Активное модальное окно
                secondActiveModal: '',                                                                                  // Активное модальное окно
                thirdActiveModal: '',                                                                                   // Активное модальное окно
                selectedWorkerId: -1,                                                                                   // id выбранного сотрудника в окне "выбор сотрудника"
                injunctionId: 88475,                                                                                       // id выбранного предписания //TODO 10.10.2019 selivanov: перенести в computed, для обновления из Unity через store

                chosenDate: new Date(),                                                                                 // объект хронящий выбранную дату
                chosenShift: {
                    id: null,
                    title: 'Выберите смену'
                },                                                                                                      // объект хронящий выбранную смену
                chosenDepartment: {
                    id: null,
                    title: 'Выберите участок'
                },                                                                                                      // объект хронящий выбранный департамент
                chosenBrigade: {
                    brigade_id: '',
                    brigade_description: 'Выберите бригаду',
                    brigader_id: ''
                },                                                                                                      // объект хронящий выбранную бригаду

                chosenChain: {
                    brigade_id:	null,
                    chane_id:	null,
                    chaner_id:	null,
                    chane_type:	null,
                    chane_title: "Выберите звено"
                },
                AllBrigadesInCompanyDepartment: {},
                // workerRescuersList: {},                                                                                  //переменная для хранения списка членов ВГК и ГМ (объек)
            }
        },
        computed: {                                                                                                     // Вычисляемые значения
            dataObjectFromOrder: {
                get() {
                    console.log('os-interactive-form.vue, get', this.orders);
                    console.count();
                    return this.getDataObjectFromOrder(this.orders);
                }
            },
            correctOrder: {                                                                                             // Получение текущего состояния объекта корректировки наряда
                get() {
                    return this.$store.getters.getCorrectOrder;                                                         // Возвращаем объект корректировки наряда
                }
            },
            /** Вычисляемое свойство для определения возможного действия с нарядом в окне "Просмотреть наряд"
             * Если статус наряда 50 - отображаем кнопку "Сохранить"
             * При иных статусах - отображаем кнопку корректировать наряд
             */
            orderStatus: {
                get() {
                    let warnings = [],
                        errors = [];
                    try {
                        warnings.push(['Метод computed orderStatus, начали выполнение метода', arguments]);
                        if (this.orders && Object.keys(this.orders).length !== 0 && this.orders.hasOwnProperty('department_order')) {
                            warnings.push(['Метод get, зашли в условие существования наряда']);
                            if (this.chosenChain.chane_id !== '') {
                                warnings.push(['Метод get, зашли в условие что звено выбрано']);
                                if (this.orders.department_order[this.chosenChain.chane_id].hasOwnProperty('order')) {
                                    warnings.push(['Метод get, зашли в условие что в наряде есть это звено и в нем есть св-во order']);
                                    if (this.orders.department_order[this.chosenChain.chane_id].order.hasOwnProperty('order_status_id')) {
                                        warnings.push(['Метод get, зашли в условие, что в нарядем есть статус']);

                                        if (this.orders.department_order[this.chosenChain.chane_id].order.order_status_id === 50) {
                                            warnings.push(['Метод get, зашли в условие, что статус 50, возвращаем save']);
                                            return 'save';
                                        }
                                        if (this.orders.department_order[this.chosenChain.chane_id].order.order_status_id !== 50) {
                                            warnings.push(['Метод get, зашли в условие, что статус отличный от 50, возвращаем correct']);
                                            return 'correct';
                                        }

                                    }
                                }
                            }
                        }
                        warnings.push(['Метод orderStatus, завершили выполнение метода']);
                    } catch (error) {
                        errors.push({'os-interactive-form.vue. get. Блок catch. Тип ошибки ': error.name});
                        errors.push({'os-interactive-form.vue. get. Блок catch. Текст ошибки ': error.message});
                        errors.push({'os-interactive-form.vue. get. Блок catch. Номер строки ': error.lineNumber});
                        console.warn(warnings);
                        if (errors.length) console.error(errors);
                    }
                }
            },
            equipment: {
              get() {
                  return this.$store.getters.getEquipmentListGroup;
              }
            },
            places: {                                                                                                   // Получение списка мест
                get() {
                    return this.$store.getters.getPlaces;                                                               // Возвращаем список мест
                }
            },
            workers: {                                                                                                  // Получение списка работников
                get() {
                    return this.$store.getters.getHandbookWorkers;                                                      // Возвращаем список работников
                }
            },
            orders: {                                                                                                   // Получение наряда
                get() {
                    return this.$store.getters.getChainOrder;                                                           // Возаращаем наряд
                }
            },
            onlyOperationsList: {                                                                                       // Получение списка всех операций без вложений
                get() {
                    return this.$store.getters.getOnlyOperationsList;                                                   // Возвращаем список всех операций без вложений
                }
            },
            // chainAttendance: {                                                                                                   // Получение выхождаемости
            //     get() {
            //         return this.$store.getters.getChainAttendance;                                                           // Возаращаем выхождаемость
            //     }
            // },
            allInstructions: {
                get() {
                    return this.$store.getters.getInstructionPB;                                                        // Получаем данные из store
                }
            },
            orderWorkers: {
                get() {
                    let warnings = [],
                        errors = [],
                        workers = {};
                    try {
                        warnings.push(['Computed orderWorkers. Объект наряда из store = ', this.orders]);
                        if (this.orders && this.orders.department_order && Object.keys(this.orders.department_order).length) {
                            warnings.push('Computed orderWorkers. есть информация о наряде');
                            if (this.chosenChain.chane_id && this.orders.department_order.hasOwnProperty(this.chosenChain.chane_id)) {
                                // if (!this.orders.department_order[this.chosenChain.chane_id].order.order_workers.hasOwnProperty('undefined')) {
                                    workers = this.orders.department_order[this.chosenChain.chane_id].order.order_workers;
                                    warnings.push(['Computed orderWorkers. список работников из наряда = ', workers]);
                                // } else {
                                //     warnings.push(['Метод get, order_workers содержит свойство undefined']);
                                //     workers = {};
                                // }
                            } else {
                                warnings.push('Computed orderWorkers. Не совпадает id бригады из списка и из наряда');
                                workers = {};
                            }
                        } else {
                            warnings.push('Computed orderWorkers. Нет информации о наряде');
                            // if (this.chosenDepartment.id) {
                            //     showNotify('В БД наряда нет', 'warning');
                            // }
                            if (this.orders.ListWorkersByGrafic && Object.keys(this.orders.ListWorkersByGrafic).length) {
                                warnings.push(['this.orderData.ListWorkersByGrafic   ', this.orders.ListWorkersByGrafic]);
                                warnings.push(['Object.keys(this.orderData.ListWorkersByGrafic).length   ', Object.keys(this.orders.ListWorkersByGrafic).length]);
                                warnings.push('Computed orderWorkers. Есть информация по графику выходов');
                                if (this.chosenBrigade.brigade_id && this.orders.ListWorkersByGrafic.hasOwnProperty(this.chosenBrigadeLast.brigade_id)) {
                                    warnings.push('Computed orderWorkers. Берем работников по ключу бригады из orderData');
                                    for (let chainId in this.orders.ListWorkersByGrafic[this.chosenBrigade.brigade_id].chanes) {
                                        if (this.orders.ListWorkersByGrafic[this.chosenBrigade.brigade_id].chanes[chainId].hasOwnProperty('workers')) {
                                            workers = Object.assign({}, workers, this.orders.ListWorkersByGrafic[this.chosenBrigade.brigade_id].chanes[chainId].workers);
                                        }
                                    }
                                    warnings.push(['Computed orderWorkers. список работников из графика выходов = ', workers]);
                                } else {
                                    warnings.push('Computed orderWorkers. Не совпадает id бригады из списка и из графика выходов');
                                    if (this.chosenDepartment.id) {
                                        showNotify('В БД нет графика выходов', 'warning');
                                    }
                                    workers = {};
                                }
                            } else {
                                warnings.push('Computed orderWorkers. нет информации ни по графику выходов, ни по наряду');
                                workers = {};
                            }
                        }

                    } catch (error) {
                        // showNotify('Возникла ошибка при обработке объекта наряда с целью получения списка работников в наряде');
                        errors.push({'Тип ошибки': error.name});
                        errors.push({'Текст ошибки': error.message});
                        errors.push({'Номер строки': error.lineNumber});
                    }
                    this.brigadeWorkersInOrder = workers;
                    warnings.push(['Computed orderWorkers. список работников а наряде ', this.brigadeWorkersInOrder]);
                    console.warn(warnings);
                    errors.length ? console.error(errors) : '';
                    return this.brigadeWorkersInOrder;
                    // if (this.chosenChain.chane_id) {
                    //     return this.$store.state.orderWorkersGroupedByChains[this.chosenChain.chane_id];
                    // }
                    // return {};
                }
            },
            allOperationsInOrder: {
                get() {
                    let placeOperationsObject = {};
                    let resultList = {};

                    let warnings = [],
                        errors = [];
                    try {
                        if (Object.keys(this.dataObjectFromOrder).length !== 0) {
                            for (let orderPlaceId in this.dataObjectFromOrder) {
                                // placeOperationObject = {
                                //     [orderPlaceId]: {
                                //         operations: {
                                //             // [operationId]: {
                                //             //     operation_id: '',
                                //             //     order_operation_id: '',
                                //             // }
                                //         },
                                //         orderPlaceId: orderPlaceId,
                                //         place_id: this.dataObjectFromOrder[orderPlaceId].place_id
                                //     }
                                // };
                                for (let operation in this.dataObjectFromOrder[orderPlaceId].operations) {
                                    let placeOperationObject = {
                                        [orderPlaceId]: {
                                            operations: {
                                                [this.dataObjectFromOrder[orderPlaceId].operations[operation].operation_id]: {
                                                    operation_id: this.dataObjectFromOrder[orderPlaceId].operations[operation].operation_id,
                                                    order_operation_id: this.dataObjectFromOrder[orderPlaceId].operations[operation].order_operation_id,
                                                }
                                            },
                                            orderPlaceId: orderPlaceId,
                                            place_id: this.dataObjectFromOrder[orderPlaceId].place_id
                                        }
                                    };
                                    placeOperationsObject = $.extend(true, placeOperationsObject, placeOperationObject);
                                }
                                console.log('Метод computed allOperationsInOrder, начало добавление объекта в результат, orderPlaceId = ',orderPlaceId,  placeOperationsObject);
                                resultList = $.extend(true, resultList, placeOperationsObject);
                            }
                        }

                    } catch (error) {
                        errors.push({'table-form.vue. allOperationsInOrder. Блок catch. Тип ошибки ': error.name});
                        errors.push({'table-form.vue. allOperationsInOrder. Блок catch. Текст ошибки ': error.message});
                        errors.push({'table-form.vue. allOperationsInOrder. Блок catch. Номер строки ': error.lineNumber});
                    }
                    console.warn(warnings);
                    errors.length ? console.error(errors) : false;

                    return resultList;
                }
            },
            orderPlaceAndOperations: {
                get() {

                    if (this.orders && this.orders.department_order && this.orders.department_order.hasOwnProperty(this.chosenChain.chane_id) && this.orders.department_order[this.chosenChain.chane_id].order && this.orders.department_order[this.chosenChain.chane_id].order.order_places) {
                        return this.orders.department_order[this.chosenChain.chane_id].order.order_places;
                    }

                    return {};
                },
                set(placeObject) {
                    this.orders.department_order[this.chosenChain.chane_id].order.order_places = Object.assign({}, this.orders.department_order[this.chosenChain.chane_id].order.order_places, placeObject);
                    this.$store.commit('updateOrderForTableFormInfo', this.orders);
                }
            },
            actualWorkersRescuersList: {
                get() {
                    return this.workerRescuersList;
                },
                set(updatedList) {
                    this.workerRescuersList = updatedList;
                }
            },
            handbookRoles: {
                get() {
                    return this.$store.getters.getHandbookRoles;
                }
            },
            // получение списка ролей для модального окна добавления роли - массив
            roles: {
                get() {
                    return this.$store.getters.roles;
                }
            },
            orderBrigade() {
                let warnings = [],
                    errors = [],
                    result = {};
                try {
                    let orderInfo = this.orders;


                    if (this.chosenChain.chane_id) {                                                                // проверка на выбор бригады
                        warnings.push('выбрано звено ' + this.chosenChain.chane_id);
                        // process устанавливается перед выполнения запроса
                        if (Object.keys(orderInfo).length && Object.keys(orderInfo.department_order).length && orderInfo.department_order.hasOwnProperty(this.chosenChain.chane_id) && orderInfo.department_order[this.chosenChain.chane_id].order) {                          // проверка на наличие наряда
                            warnings.push('Проверили существование объекта наряда order у выбранной бригады');
                            this.disabled = false;
                            //showNotify('Данные успешно полученны', 'success');
                            result = orderInfo.department_order[this.chosenChain.chane_id].order;                                                                               // возвращает наряд выбранной бригады
                        } else {
                            // this.disabled = true;
                            warnings.push('Отсутствует наряд');
                        }
                    } else {
                        warnings.push('Не выбрано звено либо нет звеньев в бригаде ');
                        // this.disabled = true;
                        //showNotify('Отсутствует бриигада');
                    }
                } catch (error) {
                    this.disabled = true;
                    result = {};
                    errors.push({'orderBrigade. Блок catch. Тип ошибки ': error.name});
                    errors.push({'orderBrigade. Блок catch. Текст ошибки ': error.message});
                    errors.push({'orderBrigade. Блок catch. Номер строки ': error.lineNumber});
                }
                //console.warn(warnings);
                errors.length ? console.error(errors) : '';
                return result;
            },
        },
        methods: {                                                                                                      // Блок с методами компонента

            // callUnityWS() {
            //     WS.subscribeWebSocketFromUnity("test")
            // },
            /**
             * <pre>Метод для сборки объекта вида:
             * order_place_id: {
             *     operation_id: {
             *         operation_id: operation_id
             *         worker_list: {
             *             [worker_id]: {
             *                 worker_id: worker_id,
             *                 operation_worker_id: operation_worker_id
             *             }
             *         }
             *     }
             * }
             *
             * используемого для построения таблицы с местами, операциями и работниками,
             * для удаления операций и мест.
             *
             * Для его работы необходимо, чтобы в компонент приходил chosenBrigade (выбранная бригада)
             * </pre>
             * //TODO 12.09.2019 selivanov: выполнить рефакторинг для построения линейного объекта или массива (необходима сортировка)
             * [place_id, operation_id, worker_list] - только для построения отсоритрованной таблицы
             */

            getDataObjectFromOrder(orderInfo) {
                console.log('Метод getDataObjectFromOrder, вызвали метод');
                let warnings = [],
                    errors = [];
                try {
                    warnings.push(['Метод getDataObjectFromOrder, начали выполнение метода']);
                    let resultObject = {};                                                                                  // Объект, содержащий всю информацию по месту

                    if (orderInfo && orderInfo.hasOwnProperty('department_order') && Object.keys(orderInfo.department_order).length !== 0) {                                    // Проверка наличия объекта orders в props и наличия свойства department_order в нём
                        warnings.push(['Метод getDataObjectFromOrder, зашли в условие, что наряд существует и не пуст']);

                        if (orderInfo.department_order.hasOwnProperty(this.chosenChain.chane_id)) {
                            warnings.push(['Метод getDataObjectFromOrder, зашли в условие, что в наряде есть такое звено']);

                            if (orderInfo.department_order[this.chosenChain.chane_id].hasOwnProperty('order')) {                          // Проверяем, есть ли св-во order в выбранной бригаде
                                let orderId = orderInfo.department_order[this.chosenChain.chane_id].order.order_id;                       // Записываем в переменную id наряда
                                warnings.push(['Метод getDataObjectFromOrder, нашли свойство order_id = ', orderId]);

                                for (let orderPlaceId in orderInfo.department_order[this.chosenChain.chane_id].order.order_places) {      // Перебираем места в наряде
                                    warnings.push(['Метод getDataObjectFromOrder, зашли в цикл по order_places, текущий элемент перебора orderPlaceId = ', orderPlaceId]);
                                    let placeId = orderInfo.department_order[this.chosenChain.chane_id].order.order_places[orderPlaceId].place_id;    // Записываем в переменную id места

                                    if (orderInfo.department_order[this.chosenChain.chane_id].order.order_places[orderPlaceId].hasOwnProperty('operation_production') && Object.keys(orderInfo.department_order[this.chosenChain.chane_id].order.order_places[orderPlaceId].operation_production).length !== 0) {  // Если в объекте места есть св-во operation_production и оно содержит хотя бы одно св-во
                                        if (this.debug_mode) console.log('Метод getDataObjectFromOrder, зашли в условие, что operation_production есть и он не пуст');
                                        warnings.push(['Метод getDataObjectFromOrder, зашли в условие, что operation_production есть и он не пуст']);
                                        warnings.push(['Метод getDataObjectFromOrder, operation_production', orderInfo.department_order[this.chosenChain.chane_id].order.order_places[orderPlaceId].operation_production]);
                                        let operationProduction = orderInfo.department_order[this.chosenChain.chane_id].order.order_places[orderPlaceId].operation_production;
                                        for (let orderOperationId in operationProduction) {   // Перебираем операции по месту
                                            warnings.push(['Метод getDataObjectFromOrder, зашли в цикл по operation_production, текущий элемент перебора orderOperationId = ', orderOperationId]);

                                            let operationId = orderInfo.department_order[this.chosenChain.chane_id].order.order_places[orderPlaceId].operation_production[orderOperationId].operation_id;  // Записываем id операции в переменную
                                            let operationValuePlan = orderInfo.department_order[this.chosenChain.chane_id].order.order_places[orderPlaceId].operation_production[orderOperationId].operation_value_plan;   // Записываем плановое значение операции
                                            let operationStatus = orderInfo.department_order[this.chosenChain.chane_id].order.order_places[orderPlaceId].operation_production[orderOperationId].status;   // Записываем статус операции
                                            warnings.push(['Метод getDataObjectFromOrder, id операции = ' + operationId + ' плановое значение = ' + operationValuePlan + ' статус = ' + operationStatus]);

                                            let operationGroups = {};                                                   // Создаем переменную для хранения значений групп, которым пренадлежит эта операция
                                            for (let groupId in orderInfo.department_order[this.chosenChain.chane_id].order.order_places[orderPlaceId].operation_production[orderOperationId].operation_groups) {   // Цикл по группам, в которых состоит операция
                                                let operationGroupId = {                                                // Объект для сбора значений id групп
                                                    [groupId]: groupId
                                                };
                                                operationGroups = Object.assign({}, operationGroups, operationGroupId); // Добавляем объект для сбора в переменную для хранения значений
                                            }

                                            let workerList = {};                                                        // Создаем переменную для хранения списка сотрудников по операции
                                            if (orderInfo.department_order[this.chosenChain.chane_id].order.hasOwnProperty('order_workers')) {
                                                // console.log('Метод getDataObjectFromOrder, есть order_workers');
                                                if (Object.keys(orderInfo.department_order[this.chosenChain.chane_id].order.order_workers).length !== 0) {
                                                    // console.log('Метод getDataObjectFromOrder, order_workers не пуст', orderInfo.department_order[this.chosenChain.chane_id].order.order_workers);
                                                    for (let workerId in orderInfo.department_order[this.chosenChain.chane_id].order.order_workers) {    // Перебор сотрудников в наряде по worker_id
                                                        if (orderInfo.department_order[this.chosenChain.chane_id].order.order_workers.hasOwnProperty(workerId)) {
                                                            // console.log('Метод getDataObjectFromOrder, в order_workers есть работник', workerId);
                                                            if (typeof orderInfo.department_order[this.chosenChain.chane_id].order.order_workers[workerId] !== 'undefined') {
                                                                if (orderInfo.department_order[this.chosenChain.chane_id].order.order_workers[workerId].hasOwnProperty('operation_production')) {
                                                                    if (orderInfo.department_order[this.chosenChain.chane_id].order.order_workers[workerId].operation_production.hasOwnProperty(orderOperationId)) {   // Если у воркера в переборе есть свойство operation_worker_id
                                                                        let workerStatus = orderInfo.department_order[this.chosenChain.chane_id].order.order_workers[workerId].operation_production[orderOperationId].status ?
                                                                            orderInfo.department_order[this.chosenChain.chane_id].order.order_workers[workerId].operation_production[orderOperationId].status : 51;   // Записываем статус сотрудника в переменную
                                                                        let operationWorkerId = orderInfo.department_order[this.chosenChain.chane_id].order.order_workers[workerId].operation_production[orderOperationId].operation_worker_id;   // Записываем статус сотрудника в переменную
                                                                        let workerIdObject = {                                                      // Собираем объект с работником, для добавления в worker_list
                                                                            [workerId]: {
                                                                                worker_id: workerId,
                                                                                order_operation_id: orderOperationId,
                                                                                operation_worker_id: operationWorkerId,
                                                                                status: workerStatus,
                                                                                // worker_role_id: this.workers[workerId] ? this.workers[workerId].worker_role_id : null    // Раскомментировать, если потребуется подгрузка ролей для всех сотрудников
                                                                            }
                                                                        };
                                                                        workerList = Object.assign({}, workerList, workerIdObject);                      // Добавляем объект с работником в worker_list
                                                                    }
                                                                } else {
                                                                    warnings.push(['Метод getDataObjectFromOrder, в наряде, у воркера ', workerId, 'нет св-ва operation_production']);
                                                                }
                                                            } else {
                                                                warnings.push(['Метод getDataObjectFromOrder, workerId is undefined']);
                                                                warnings.push(['Метод getDataObjectFromOrder, очищаем order_workers']);
                                                                this.$store.dispatch('clearOrderWorkers', this.chosenChain.chane_id);
                                                            }
                                                        } else {
                                                            warnings.push(['Метод getDataObjectFromOrder, в наряде нет такого воркера ', workerId]);
                                                        }
                                                    }
                                                } else {
                                                    warnings.push(['Метод getDataObjectFromOrder, order_workers пуст']);
                                                }
                                            } else {
                                                warnings.push(['Метод getDataObjectFromOrder, в наряде нет order_workers']);
                                            }
                                            warnings.push(['Метод getDataObjectFromOrder, полученный объект workerList по операции', workerList]);

                                            let orderPlaceData = {                                                              // Собираем информацию по месту
                                                [orderPlaceId]: {
                                                    order_place_id: orderPlaceId,
                                                    place_id: placeId,
                                                    operations: {
                                                        [operationId]: {
                                                            operation_id: operationId,
                                                            operation_value_plan: operationValuePlan,
                                                            order_operation_id: orderOperationId,
                                                            status: operationStatus,
                                                            operation_groups: operationGroups,
                                                            worker_list: workerList
                                                        }
                                                    }
                                                }
                                            };

                                            let extendObj = $.extend(true, resultObject, orderPlaceData);                       // Глубокое (!!!) слияние объектов. Без него происходит перезапись и остается только последний записанный элемент
                                            resultObject = Object.assign({}, resultObject, extendObj);                          // Собираем результирующий объект
                                        }
                                    } else {                                                                                // Если не найдено operation_production в объекте места
                                        warnings.push(['Метод getDataObjectFromOrder, зашли в условие, что operation_production нет или он пуст']);
                                        let orderPlaceData = {                                                              // Собираем информацию по месту
                                            [orderPlaceId]: {
                                                order_place_id: orderPlaceId,
                                                place_id: placeId,
                                                operations: {}
                                            }
                                        };

                                        let extendObj = $.extend(true, resultObject, orderPlaceData);                       // Глубокое (!!!) слияние объектов. Без него происходит перезапись и остается только последний записанный элемент
                                        resultObject = Object.assign({}, resultObject, extendObj);                          // Собираем результирующий объект
                                    }
                                }
                            }
                        }
                    }
                    warnings.push(['Метод getDataObjectFromOrder, результирующий объект: ', resultObject]);
                    console.log("Результирующий объект: ", resultObject);
                    return resultObject;
                } catch (error) {
                    errors.push({'os-interactive-form.vue. getDataObjectFromOrder. Блок catch. Тип ошибки ': error.name});
                    errors.push({'os-interactive-form.vue. getDataObjectFromOrder. Блок catch. Текст ошибки ': error.message});
                    errors.push({'os-interactive-form.vue. getDataObjectFromOrder. Блок catch. Номер строки ': error.lineNumber});
                }
                console.warn(warnings);
                if (errors.length) console.error(errors);                                                                               // Возвращаем итоговый объект
            },

            decrementOrderPlaceId() {
                this.orderPlaceDecrementId--;
            },

            decrementOrderOperationId() {
                this.orderOperationDecrementId--;
            },

            decrementOperationWorkerId() {
                this.operationWorkerDecrementId--;
            },

            testprevent() {
                console.log("testprevent. Сработало")
            },
            dragover_prevent() {
                console.log("dragover_prevent. Сработало")
            },
            drop_prevent() {
                console.log("drop_prevent. Сработало")
            },

            sendWorkerToUnity(workerId) {
                let warnings = [],
                    errors = [];
                try {
                    warnings.push(['Метод sendWorkerToUnity, начали выполнение метода', arguments]);
                    let workerObj = [];
                    workerObj.push(this.workersInOrder[workerId]);

                    let data = {
                        items: workerObj,
                        chaneId: ''
                    };
                    if (this.chosenChain && Object.keys(this.chosenChain).length !== 0) {
                        console.log('Метод sendWorkerToUnity, зашли в условие, что есть выбранное звено = ', this.chosenChain);
                        warnings.push(['Метод sendWorkerToUnity, зашли в условие, что есть выбранное звено = ', this.chosenChain]);
                        data.chaneId = this.chosenChain.chane_id;
                        console.log('Метод sendWorkerToUnity, записали в объект data.chaneId = ', data.chaneId);
                        warnings.push(['Метод sendWorkerToUnity, записали в объект data.chaneId = ', data.chaneId]);
                    }
                    let jsonData = JSON.stringify(data);

                    console.log('Метод sendWorkerToUnity, заJSONили', jsonData);
                    warnings.push(['Метод sendWorkerToUnity, итоговый JSON = ', jsonData]);

                    gameInstance.SendMessage('UserControlOrderSystem', 'NotifyDragBeginFromWeb', jsonData);
                    console.log('Метод sendWorkerToUnity, отправили в Unity');
                    this.closeWindow();
                    warnings.push(['Метод sendWorkerToUnity, завершили выполнение метода']);
                } catch (error) {
                    errors.push({'interactiveEmployeeSelection.vue. sendWorkerToUnity. Блок catch. Тип ошибки ': error.name});
                    errors.push({'interactiveEmployeeSelection.vue. sendWorkerToUnity. Блок catch. Текст ошибки ': error.message});
                    errors.push({'interactiveEmployeeSelection.vue. sendWorkerToUnity. Блок catch. Номер строки ': error.lineNumber});
                }
                console.warn(warnings);
                if (errors.length) console.error(errors);
            },

            toggleInstructionsModal() {
                this.thirdActiveModal === 'instructionsModal' ? this.thirdActiveModal = '' : this.thirdActiveModal = 'instructionsModal';   // Если модальное окно уже открыто - закрываем, иначе - открываем
                console.log('Метод toggleInstructionsModal, this.thirdActiveModal = ', this.thirdActiveModal);
            },

            //открывает информацио о работнике из модального окна
            openCardInfoWorker(workerId) {
                //Метод меняет булево значение в переменной openWorkerInfo на противоположное тем самым
                // показывая / скрывая модальное окно карточки сотрудника
                this.selectedWorkerId = workerId;
                this.thirdActiveModal = 'cardEmployee';
            },
            /**
             * Метод раскрытия окна на весь экран
             */
            setMaxWindow() {
                this.maxWindowReport === 'interactiveTableFormWrapper-maxWindow' ? this.maxWindowReport = '' : this.maxWindowReport = 'interactiveTableFormWrapper-maxWindow'; // присваивание в переменную наименования классов
                this.maxWindow = !this.maxWindow;                                                                       // присваиваем противоположенное логическое значение
            },
            /**
             * Метод закрытия страницы
             * */
            closeWindow() {
                console.log('Закрытие окна')
            },
            /**
             * Метод обработки результата работы меню
             * @param dataMenu(Object) - полученный объект данных
             * */
            processingData(dataMenu) {
                let warnings = [],
                    errors = [];
                try {
                    warnings.push(['Метод processingData, начали выполнение метода', arguments]);
                    let cloneDataMenu = {...dataMenu};
                    warnings.push("processingData. Начал выполнять метод");
                  warnings.push(['processingData. Входные данные', dataMenu]);

                    this.chosenDate = new Date(cloneDataMenu.chosenDate);
                    this.chosenDepartment = cloneDataMenu.chosenDepartment;
                    this.chosenBrigade = cloneDataMenu.chosenBrigade;
                    this.chosenShift = cloneDataMenu.chosenShift;
                    this.chosenChain = cloneDataMenu.chosenChane;

                    warnings.push(['Метод processingData, записали данные в data interactive form', [this.chosenDate, this.chosenDepartment, this.chosenBrigade, this.chosenShift, this.chosenChain]]);

                    // if (this.chosenDate && this.chosenDepartment.id && this.chosenBrigade.brigade_id) {                     // если показатели корректны, то вызывается запрос на получение данных по отчету за выбранные период
                    if (this.chosenDate && this.chosenDepartment.id && this.chosenShift.id) {                     // если показатели корректны, то вызывается запрос на получение данных по отчету за выбранные период
                        console.log('Метод processingData, __________ВЫЗЫВАЕМ МЕТОД getOrder____________');
                        this.getOrder();                                                                                // вызов метода на получение данных отчета за выбранные период
                    } else {
                        warnings.push(['processingData. одно из полей не задано']);
                    }
                    warnings.push(['Метод processingData, вызываем метод добавления полей ФИО и названия роли addFullNameAndRoleTitleToOrderWorkers']);
                    // this.addFullNameAndRoleTitleToOrderWorkers();
                    warnings.push('processingData. вызываем saveDataForUnity');
                    this.saveDataForUnity();
                  warnings.push('processingData. вызвали saveDataForUnity');
                  warnings.push("processingData. Закончил выполнять метод");
                  warnings.push(['Метод processingData, завершили выполнение метода']);
                } catch (error) {
                    errors.push({'os-interactive-form.vue. processingData. Блок catch. Тип ошибки ': error.name});
                    errors.push({'os-interactive-form.vue. processingData. Блок catch. Текст ошибки ': error.message});
                    errors.push({'os-interactive-form.vue. processingData. Блок catch. Номер строки ': error.lineNumber});
                }
                console.warn(warnings);
                if (errors.length) console.error(errors);
            },

            callMethodInUnity() {
                console.log("Вызов метода показа даты в unity");
                showSavedData();
            },

            sendToWebSocket() {
                console.log('Метод sendToWebSocket, вызвали метод', );
                let jsonUnity = {"order_places":{"order_place_id":-702,"place_id":6664,"passport_id":null,"reason":null,"operation_production":{"-7002":{"operation_id":-1,"order_operation_id":-7002,"operation_value_plan":null,"operation_value_fact":null,"operation_load_value":null,"operation_groups":[]},"-7003":{"operation_id":-1,"order_operation_id":-7003,"operation_value_plan":null,"operation_value_fact":null,"operation_load_value":null,"operation_groups":[]}}},"order_workers":{"1000066":{"worker_id":1000066,"worker_role_id":-1,"role_title":null,"full_name":null,"operation_production":{"-7002":{"order_operation_id":-7002,"operation_worker_id":-2,"coordinate":"15471.78,-827.1768,-13545.87","group_workers_unity":0,"status":null}},"status":null},"1000241":{"worker_id":1000241,"worker_role_id":-1,"role_title":null,"full_name":null,"operation_production":{"-7003":{"order_operation_id":-7003,"operation_worker_id":-3,"coordinate":"15471.78,-827.1768,-13545.87","group_workers_unity":0,"status":null}},"status":null}},"chane_id":738};
                let trueJSON = JSON.stringify(jsonUnity);
                // let objectForSendMessage = {
                //     ClientType: 'order-system',
                //     // ClientId: String(workerId),
                //     ActionType: 'publish',
                //     SubPubList: [new Date().toString()],
                //     MessageToSend: jsonUnity,
                // };
                //
                // console.log('Метод sendToWebSocket, вызываем отправку в вебсокет', objectForSendMessage);
                // webSocket.send(JSON.stringify(objectForSendMessage));

                console.log('Метод sendToWebSocket, вызываем метод из store');
                this.$store.dispatch("updateChainOrderFromUnity", trueJSON);
              },

            callMoveChain() {
              let sendObject = JSON.stringify('{"trunsuctionId":239749723984,"crudId":1,"executorName":"OrderController","methodName":"moveChain","methodParameters":"123231|2131|2342342|{json:json}","senderId":"webFront@client_441234","reciverId":"unity@client_123213","data":"{\\"order_places\\":{\\"order_place_id\\":367,\\"place_id\\":132146,\\"passport_id\\":null,\\"reason\\":null,\\"operation_production\\":{\\"1133\\":{\\"operation_id\\":24,\\"order_operation_id\\":1133,\\"operation_value_plan\\":0,\\"operation_value_fact\\":null,\\"operation_load_value\\":0.0,\\"operation_groups\\":{\\"8\\":{\\"operation_group_id\\":8}},\\"debug_workerId\\":0,\\"debug_operation_groups\\":[{\\"operation_group_id\\":8}]},\\"1134\\":{\\"operation_id\\":25,\\"order_operation_id\\":1134,\\"operation_value_plan\\":0,\\"operation_value_fact\\":null,\\"operation_load_value\\":0.0,\\"operation_groups\\":{},\\"debug_workerId\\":0,\\"debug_operation_groups\\":[]},\\"1135\\":{\\"operation_id\\":48,\\"order_operation_id\\":1135,\\"operation_value_plan\\":500,\\"operation_value_fact\\":null,\\"operation_load_value\\":360.0,\\"operation_groups\\":{},\\"debug_workerId\\":0,\\"debug_operation_groups\\":[]},\\"1136\\":{\\"operation_id\\":49,\\"order_operation_id\\":1136,\\"operation_value_plan\\":500,\\"operation_value_fact\\":null,\\"operation_load_value\\":0.0,\\"operation_groups\\":{},\\"debug_workerId\\":0,\\"debug_operation_groups\\":[]}},\\"debug_operation_production\\":[]},\\"order_workers\\":{\\"2909348\\":{\\"worker_id\\":2909348,\\"worker_role_id\\":1,\\"role_title\\":null,\\"full_name\\":null,\\"operation_production\\":{\\"1135\\":{\\"order_operation_id\\":1135,\\"operation_worker_id\\":674,\\"coordinate\\":\\"16338.63,-728.1321,-13887.27\\",\\"group_workers_unity\\":0,\\"status\\":{\\"status_id_last\\":1,\\"status_id_all\\":{\\"650\\":{\\"operation_status_id\\":650,\\"status_id\\":1,\\"status_date_time\\":\\"2019-09-27 14:12:42\\",\\"worker_id\\":1801}}}}}},\\"10109045\\":{\\"worker_id\\":10109045,\\"worker_role_id\\":1,\\"role_title\\":null,\\"full_name\\":null,\\"operation_production\\":{\\"1135\\":{\\"order_operation_id\\":1135,\\"operation_worker_id\\":675,\\"coordinate\\":\\"16338.63,-728.1321,-13887.27\\",\\"group_workers_unity\\":0,\\"status\\":{\\"status_id_last\\":1,\\"status_id_all\\":{\\"651\\":{\\"operation_status_id\\":651,\\"status_id\\":1,\\"status_date_time\\":\\"2019-09-27 14:12:42\\",\\"worker_id\\":1801}}}}}},\\"70003553\\":{\\"worker_id\\":70003553,\\"worker_role_id\\":1,\\"role_title\\":null,\\"full_name\\":null,\\"operation_production\\":{\\"1136\\":{\\"order_operation_id\\":1136,\\"operation_worker_id\\":676,\\"coordinate\\":\\"16338.63,-728.1321,-13887.27\\",\\"group_workers_unity\\":0,\\"status\\":{\\"status_id_last\\":1,\\"status_id_all\\":{\\"652\\":{\\"operation_status_id\\":652,\\"status_id\\":1,\\"status_date_time\\":\\"2019-09-27 14:12:42\\",\\"worker_id\\":1801}}}}}},\\"-13786\\":{\\"worker_id\\":-13786,\\"worker_role_id\\":-1,\\"role_title\\":null,\\"full_name\\":\\"[Не найден -13786]\\",\\"operation_production\\":{}},\\"-13787\\":{\\"worker_id\\":-13787,\\"worker_role_id\\":-1,\\"role_title\\":null,\\"full_name\\":\\"[Не найден -13787]\\",\\"operation_production\\":{}}},\\"chane_id\\":738}","errors":["some Error 1","some Error 2","some Error 3"],"warnings":["warning1","warning2"]}');
                  webSocket.sendMessageFromWS('TestUnityWebFront', sendObject);
            },

            showOrderInConsole() {
                console.info('Из store: ', this.$store.state.chainOrder);
                console.info('Из computed: ', this.orders);
                // console.log('Метод showOrderInConsole, проверка ', this.$store.state.chainOrder.department_order['738'].order.order_places['-701']);
                // console.log('Метод showOrderInConsole, проверка 2', this.$store.state.chainOrder.department_order['738'].order.order_places);
                // console.log(hasProperty(this.$store.state.chainOrder.department_order['738'].order.order_places, '-701'));
                let jsonOrder = JSON.stringify(this.$store.state.chainOrder);
                console.log('Метод showOrderInConsole, отправляем в Unity JSON с нарядом', jsonOrder);
                gameInstance.SendMessage('CompareOrderJson', 'MineOrderExchange', jsonOrder);
            },

            sendOrderToState() {
                let orderJSON = {
                    data: document.getElementById('orderJson').value,
                    methodName: 'updateObject'
            };
                console.log('Метод sendOrderToState, записали введенное', orderJSON);

                this.chosenChain.chane_id = document.getElementById('orderChainId').value;

                this.$store.dispatch('updateChainOrderFromUnity', orderJSON);
            },

            getWorkerRole(roleId, workerId) {
                if (roleId && this.handbookRoles.hasOwnProperty(roleId)) {
                    return this.handbookRoles[roleId].title;
                }
                // return this.allWorkers[workerId].role_main;
                return '';
            },

            sendInitializeWorkerListToUnity() {
                console.log('Метод sendInitializeWorkerListToUnity, начали выполнение метода');

                let workersObj = this.orderWorkers;

                console.log('Метод sendInitializeWorkerListToUnity, workersObj = ', workersObj);

                for (let workerId in workersObj) {
                    // console.log('Метод sendChainToUnity, зашли в цикл', workerId);
                    workersObj[workerId].full_name = this.workers[workerId].worker_full_name;
                    workersObj[workerId].role_title = this.getWorkerRole(workersObj[workerId].worker_role_id, workerId);

                    console.log('Метод sendInitializeWorkerListToUnity, зашли в цикл workerId = ', workerId);
                    console.log('Метод sendInitializeWorkerListToUnity, зашли в цикл workersObj[workerId].full_name = ', workersObj[workerId].full_name);
                    console.log('Метод sendInitializeWorkerListToUnity, зашли в цикл workersObj[workerId].role_title = ', workersObj[workerId].role_title);
                }
                console.log('Метод sendInitializeWorkerListToUnity, пересобрали объект ', workersObj);
                let jsonData = JSON.stringify(workersObj);
                console.log('Метод sendInitializeWorkerListToUnity, заJSONили', jsonData);

                gameInstance.SendMessage('DataSyncOrderWorkersWithWebFront', 'MineOrderExchange', jsonData);
                console.log('Метод sendInitializeWorkerListToUnity, отправили в Unity');
            },

            openEmployeeSelectionModal() {
                this.changeActiveModal('employeeSelection');
                // this.sendInitializeWorkerListToUnity();
            },

            // метод вызова окна предписания
            showInjunctionTable() {
                this.thirdActiveModal = 'injunctionTable';
            },

            showIndividualOrder() {
                $("#individualOrderModal").modal('show');
            },

            // метод вызова функций юнити
            saveDataForUnity() {
                console.log('Метод saveDataForUnity, начали выполнение метода');
                let data = {
                    selectDate: this.chosenDate,                                                                        // текущая выбранная дата
                    shiftId: this.chosenShift.id,                                                                       // текущая смена - ключ
                    departmentId: this.chosenDepartment.id,                                                             // текущий департамент - ключ
                    brigadeId: this.chosenBrigade.brigade_id,                                                           // текущая бригада - ключ
                    chaneId: this.chosenChain.chane_id,                                                                 // текущее звено - ключ
                };

                console.log('Метод saveDataForUnity, вызываем метод из order-system-unity data', data);
                saveDataForUnity(data);
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
                let warnings = [],
                    errors = [];
                try {
                    warnings.push(['Метод changeActiveModal, начали выполнение метода смены активного окна']);
                    if (modalName === this.activeModal) {
                        warnings.push(['Метод changeActiveModal, зашли в условие, что вызываем то же окно, какое сейчас активно']);
                        this.activeModal = '';
                        if (clickedDiv && clickedDiv.querySelector('i')) {
                            clickedDiv.querySelector('i').style.transform = "rotate(0deg)";
                        }
                    } else {
                        if (modalName === 'brigadesList') {
                            warnings.push(['Метод changeActiveModal, зашли в условие, что вызываем окно со списком бригад']);
                            if (Object.keys(this.AllBrigadesInCompanyDepartment).length) {
                                this.activeModal = modalName;
                                if (clickedDiv && clickedDiv.querySelector('i')) {
                                    clickedDiv.querySelector('i').style.transform = "rotate(180deg)";
                                }
                            }
                        } else {
                            warnings.push(['Метод changeActiveModal, зашли в условие, что вызываем другое окно (не список бригад)']);

                            switch (modalName) {
                                case 'employeeSelection':
                                    if (this.activeModal === '' && this.secondActiveModal !== modalName) {
                                        this.activeModal = modalName;
                                    } else if (this.activeModal === modalName) {
                                        this.activeModal = '';
                                    } else if (this.secondActiveModal === modalName) {
                                        this.secondActiveModal = '';
                                    } else {
                                        this.secondActiveModal = modalName;
                                    }
                                    break;
                                case 'operationsSelection':
                                    if (this.activeModal === '' && this.secondActiveModal !== modalName) {
                                        this.activeModal = modalName;
                                    } else if (this.activeModal === modalName) {
                                        this.activeModal = '';
                                    } else if (this.secondActiveModal === modalName) {
                                        this.secondActiveModal = '';
                                    } else {
                                        this.secondActiveModal = modalName;
                                    }
                                    break;
                                default:
                                    this.activeModal = modalName;
                                    if (clickedDiv && clickedDiv.querySelector('i')) {
                                        clickedDiv.querySelector('i').style.transform = "rotate(180deg)";
                                    }
                                    break;
                            }

                            // if (modalName === 'employeeSelection') {
                            //
                            //     operationsSelection
                            //     secondActiveModal
                            // } else {
                            //     this.activeModal = modalName;
                            //     if (clickedDiv && clickedDiv.querySelector('i')) {
                            //         clickedDiv.querySelector('i').style.transform = "rotate(180deg)";
                            //     }
                            // }
                        }
                    }
                } catch (error) {
                    errors.push({'os-interactive-form.vue. changeActiveModal. Блок catch. Тип ошибки ': error.name});
                    errors.push({'os-interactive-form.vue. changeActiveModal. Блок catch. Текст ошибки ': error.message});
                    errors.push({'os-interactive-form.vue. changeActiveModal. Блок catch. Номер строки ': error.lineNumber});
                }
                console.warn(warnings);
                if (errors.length) console.error(errors);
            },
            /**
             * Метод получения наряда
             * Входные параметры: отсутствуют
             * Выходные параметры: отсутствуют
             */
            getOrder() {
                let warnings = [],
                    errors = [];
                try {
                    warnings.push(['Метод getOrder, начали выполнение метода получения наряда']);
                    // if (this.chosenDate !== '' &&                                                                           // Если выбранная дата не пуста и
                    //     this.chosenDate !== null &&
                    //     typeof this.chosenDate !== 'undefined' &&
                    //     this.chosenShift.id !== '' &&                                                                       // Если выбранная смена не пуста и
                    //     this.chosenShift.id !== null &&
                    //     typeof this.chosenShift.id !== 'undefined' &&
                    //     this.chosenDepartment.id !== '' &&                                                                  // Если выбранное подразделение не пустое
                    //     this.chosenDepartment.id !== null &&
                    //     typeof this.chosenDepartment.id !== 'undefined') {

                    warnings.push(['Метод getOrder, прошли условие на заполненность полей, необходимых для выполнения запроса']);

                    let jsonData = JSON.stringify({                                                                     // Собираем JSON из входных данных (дата, смена, департамент)
                        "company_department_id": this.chosenDepartment.id,
                        // "date_time": this.chosenDate.toLocaleString("ru").split(',')[0].replace(/\./g, '-').split('-').reverse().join('-'),
                        "date_time": getFormattedDate(this.chosenDate, 'hyphen'),
                        "shift_id": this.chosenShift.id,
                        "brigade_id": this.chosenBrigade.brigade_id ? this.chosenBrigade.brigade_id : '',
                        "chane_id": this.chosenChain.chane_id ? this.chosenChain.chane_id : ''
                    });
                    this.$store.dispatch('getChainOrder', jsonData);                                                    // Вызываем action getChainOrder из store и передаем в него json
                    // this.$store.dispatch('getChainAttendance', jsonData);                                               // Вызываем action getChainAttendance из store и передаем в него json
                    // ajaxGetRescuersList(this.chosenDepartment.id, getFormattedDate(this.chosenDate, 'hyphen'), this.chosenShift.id);
                    // this.ajaxGetRescuersList(this.chosenDepartment.id, this.chosenDate, this.chosenShift.id);
                    warnings.push(['Метод getOrder, запрос отправлен']);
                    // } else {                                                                                                // Если хотя бы одно из необходимых полей не выбрано
                    //     warnings.push(['Метод getOrder, запрос не отправлен, так как не заполнены все поля']);
                    // }
                } catch (error) {
                    errors.push({'os-interactive-form.vue. getOrder. Блок catch. Тип ошибки ': error.name});
                    errors.push({'os-interactive-form.vue. getOrder. Блок catch. Текст ошибки ': error.message});
                    errors.push({'os-interactive-form.vue. getOrder. Блок catch. Номер строки ': error.lineNumber});
                }
                console.warn(warnings);
                if (errors.length) console.error(errors);
            },

            /**
             * Метод записи основых данных (ИД подразделения, ИД бригады, ИД наряда) о редактируемом наряде
             * в объект корректировки наряда в store
             * Входные параметры: отсутствуют
             * Выходные параметры: отсутствуют
             */
            setDataForInitializationCorrectionOrder() {
                let warnings = [],
                    errors = [];
                try {
                    warnings.push(['Метод setDataForInitializationCorrectionOrder, начали выполнение метода установки основых данных в объект корректировки']);
                    warnings.push(['Метод setDataForInitializationCorrectionOrder, this.chosenDepartment.id = ', this.chosenDepartment.id]);
                    warnings.push(['Метод setDataForInitializationCorrectionOrder, this.chosenBrigade.brigade_id = ', this.chosenBrigade.brigade_id]);
                    warnings.push(['Метод setDataForInitializationCorrectionOrder, this.orders.department_order = ', this.orders.department_order]);
                    // if (this.chosenDepartment.id !== '' &&                                                                  // Если подразделение выбрано и
                    //     this.chosenDepartment.id !== null &&
                    //     typeof this.chosenDepartment.id !== 'undefined' &&
                    //     this.chosenBrigade.brigade_id !== '' &&                                                             // Если бригада выбрана и
                    //     this.chosenBrigade.brigade_id !== null &&
                    //     typeof this.chosenBrigade.brigade_id !== 'undefined' &&
                    //     this.orders.department_order !== '' &&                                                              // Если наряд не пуст
                    //     this.orders.department_order !== null &&
                    //     typeof this.orders.department_order !== 'undefined') {

                        warnings.push(['Метод setDataForInitializationCorrectionOrder, прошли условие, что выбраны все вкладки']);
                        if (Object.keys(this.orders.department_order).length !== 0) {
                            warnings.push(['Метод setDataForInitializationCorrectionOrder, прошли условие, что наряд не пуст']);
                            let order_id = '',
                                departmentOrderObject = {};                                                             // Создаем переменую для order id

                            if (this.orders && this.orders.department_order && Object.keys(this.orders.department_order).length !== 0) { // Если есть orders и в нём есть department_order и в нём есть свойство
                                departmentOrderObject = this.orders.department_order[Object.keys(this.orders.department_order)[0]];  // Записываем значение этого свойства в переменную
                                warnings.push(['Метод setDataForInitializationCorrectionOrder, вынесли orders.department_order в отдельную переменную', departmentOrderObject]);

                                if (departmentOrderObject.order && Object.keys(departmentOrderObject.order).length !== 0) {     // Если объект не пуст и в этом объекте есть свойства
                                    order_id = departmentOrderObject.order.order_id;                                            // Записываем order_id из объекта
                                    warnings.push(['Метод setDataForInitializationCorrectionOrder, записали order_id в отдельную переменную', order_id]);
                                }
                            }

                            // if (Object.keys(departmentOrderObject.order.order_places).length !== 0) {                // * Проверка на содержание мест в наряде отключена, т.к. появился функционал создания нового наряда

                                let dataAndType = {                                                                                 // Создаем новый объект для передачи данных
                                    data: {                                                                                         // Записываем полученные данные
                                        company_department_id: this.chosenDepartment.id,
                                        brigade_id: this.chosenBrigade.brigade_id,
                                        order_id: order_id
                                    },
                                    type: 'initialization'                                                                          // Тип данных используется в action setCorrectOrder в store, чтобы определить, куда записать данные
                                };

                                let jsonData = JSON.stringify({
                                    "company_department_id": this.chosenDepartment.id,
                                    "date_time": getFormattedDate(this.chosenDate, 'hyphen'),
                                    "shift_id": this.chosenShift.id
                                });

                                this.$store.dispatch("updateCorrectOrderObject", dataAndType);                                            // Вызываем action updateCorrectOrder с передачей в него полученных данных
                                // this.$store.dispatch('getChainAttendance', jsonData);
                                warnings.push(['Метод setDataForInitializationCorrectionOrder, обновили объект корректировки']);
                                $("#groupModal").modal('show');
                            // } else {                                                                                 // * Проверка на содержание мест в наряде отключена, т.к. появился функционал создания нового наряда
                            //     showNotify('Наряд пуст');
                            //     warnings.push(['Метод setDataForInitializationCorrectionOrder, наряд пуст']);
                            // }
                        } else {
                            showNotify('Объект наряда пуст');
                            warnings.push(['Метод setDataForInitializationCorrectionOrder, объект наряда пуст']);
                        }
                    // } else {
                    //     showNotify('Одно из полей не задано или нет наряда');
                    //     warnings.push(['Метод setDataForInitializationCorrectionOrder, одно из полей не задано']);
                    // }
                } catch (error) {
                    errors.push({'os-interactive-form.vue. setDataForInitializationCorrectionOrder. Блок catch. Тип ошибки ': error.name});
                    errors.push({'os-interactive-form.vue. setDataForInitializationCorrectionOrder. Блок catch. Текст ошибки ': error.message});
                    errors.push({'os-interactive-form.vue. setDataForInitializationCorrectionOrder. Блок catch. Номер строки ': error.lineNumber});
                }
                console.warn(warnings);
                if (errors.length) console.error(errors);
            },

            //**************************************************************************************************
            //*                                                                                                *
            //*                       Блок методов для компонента "Выбор персонала"                            *
            //*                                                                                                *
            //**************************************************************************************************
            addWorker(worker) {
                console.log("os-intractive-form. methods. addWorker. Начало метода",);
                console.time("addWorker");
                // if (this.chosenChain.chane_id) {
                    let workerObject = {
                        worker_id: worker.worker_id,
                        worker_role_id: worker.worker_role_id > 0 ? worker.worker_role_id : 9,
                        operation_list: {},
                        operation_production: {}
                    };
                    let extendedWorkerObject = {
                        brigadeId: this.chosenBrigade.brigade_id,
                        chaneId: this.chosenChain.chane_id,
                        workerInfo: workerObject,
                        department: this.chosenDepartment,
                        shiftId: Number(this.chosenShift.id),
                        orderDateTime: getFormattedDate(this.chosenDate, 'hyphen')
                    };
                    this.$store.dispatch('addOneWorkerInInteractiveOrder', extendedWorkerObject);
                // } else {
                //     console.log('os-intractive-form, addWorker. нету звена', this.chosenChain.chane_id);
                // }
                console.timeEnd("addWorker");
                console.log("os-intractive-form. methods. addWorker. Конец метода");
            },
            // удаление работника из наряда
            delWorkerFromOrder(workerId) {
                console.log('Метод delWorkerFromOrder, вызвали метод удаления работника, пришло workerId = ', workerId);
                let workerObject = {
                    brigadeId: this.chosenBrigade.brigade_id,
                    chaneId: this.chosenChain.chane_id,
                    workerId: workerId,
                    department: this.chosenDepartment,
                    shiftId: Number(this.chosenShift.id),
                    orderDateTime: getFormattedDate(this.chosenDate, 'hyphen'),
                    workerObjectForUnity: this.orderWorkers[workerId]
                };
                console.log('Метод delWorkerFromOrder, собрали объект для отправки на удаление', workerObject);
                this.$store.dispatch('deleteWorkerFromInteractiveOrder', workerObject);
            },
            // Привязка операции к работнику
            selectOperationForWorker(workerOperationObject) {
                console.log('Метод selectOperationForWorker, пришло:', workerOperationObject);
                let operationForWorker = {
                    operationId: workerOperationObject.operationId,
                    orderOperationId: workerOperationObject.orderOperationId,
                    operationWorkerDecrementId: this.operationWorkerDecrementId,
                    workerId: workerOperationObject.workerId,
                    brigadeId: this.chosenBrigade.brigade_id,
                    chaneId: this.chosenChain.chane_id,
                    department: this.chosenDepartment,
                    shiftId: Number(this.chosenShift.id),
                    orderDateTime: getFormattedDate(this.chosenDate, 'hyphen'),
                };
                console.log('Метод selectOperationForWorker, собрали объект для отправки на привязку операции к работнику', operationForWorker);
                this.$store.dispatch('selectOperationForWorkerInInteractiveOrder', operationForWorker);
            },
            // Отвязка операции от работника
            unselectOperationForWorker(workerOperationObject) {
                console.log('Метод unselectOperationForWorker, пришло:', workerOperationObject);
                let operationForWorker = {
                    operationId: workerOperationObject.operationId,
                    orderOperationId: workerOperationObject.orderOperationId,
                    operationWorkerDecrementId: this.operationWorkerDecrementId,
                    workerId: workerOperationObject.workerId,
                    brigadeId: this.chosenBrigade.brigade_id,
                    chaneId: this.chosenChain.chane_id,
                    department: this.chosenDepartment,
                    shiftId: Number(this.chosenShift.id),
                    orderDateTime: getFormattedDate(this.chosenDate, 'hyphen'),
                };
                console.log('Метод unselectOperationForWorker, собрали объект для отправки на привязку операции к работнику', operationForWorker);
                this.$store.dispatch('unselectOperationForWorkerInInteractiveOrder', operationForWorker);
            },
            // смена роли работника в наряде
            changeRoleWorker(workerRole) {
                //console.log('table-form.vue, addOperationToWorker. dataForAddingOperationToWorker', dataForAddingOperationToWorker);
                let workerObject = {
                    brigadeId: this.chosenBrigade.brigade_id,
                    chaneId: this.chosenChain.chane_id,
                    workerId: workerRole.workerId,
                    roleId: workerRole.roleId,
                    department: this.chosenDepartment,
                    shiftId: Number(this.chosenShift.id),
                    orderDateTime: getFormattedDate(this.chosenDate, 'hyphen'),
                    workerObjectForUnity: this.orderWorkers[workerRole.workerId]
                };
                //console.log('table-form.vue, addOperationToWorker. fullInfoForAddingOperationToWorker', fullInfoForAddingOperationToWorker);
                this.$store.dispatch('changeRoleWorkerInInteractiveOrder', workerObject);
            },
            addOperationToWorker(dataForAddingOperationToWorker) {
                console.log('os-intractive-form, addOperationToWorker. dataForAddingOperationToWorker', dataForAddingOperationToWorker);
                const fullInfoForAddingOperationToWorker = {
                    brigadeId: this.chosenBrigade.brigade_id,
                    workerId: dataForAddingOperationToWorker.workerId,
                    operationId: dataForAddingOperationToWorker.operationId,
                    operationIndex: dataForAddingOperationToWorker.operationIndex
                };
                console.log('table-form.vue, addOperationToWorker. fullInfoForAddingOperationToWorker', fullInfoForAddingOperationToWorker);
                this.$store.dispatch('addOrderOperationToWorker', fullInfoForAddingOperationToWorker);
            },
            // async ajaxGetRescuersList(companyDepartmentId, dateTime, shiftId) {
            //     // if (this.debug_mode) {
            //     console.log('функция ajaxGetRescuersList');
            //     console.log('выводим аргументы функции');
            //     console.log(`company_department_id = ${companyDepartmentId}, dateTime = ${dateTime}, shiftId = ${shiftId}`);
            //     // }
            //     let self = this,
            //         dataForMethod = {
            //             controller: 'ordersystem\\OrderSystem',
            //             method: 'GetWorkersVgk',
            //             subscribe: 'GetWorkersVgk',
            //             data: JSON.stringify({
            //                 company_department_id: companyDepartmentId,
            //                 date: dateTime,
            //                 shift_id: shiftId
            //             })
            //         };
            //     let rescuers = await sendAjax(dataForMethod);
            //
            //     if (rescuers.status === 1) {
            //         self.actualWorkersRescuersList = rescuers.Items;
            //     } else {
            //         rescuers.errors.forEach(error => showNotify(error));
            //     }
            // },

            /**
             * Метод добавления скриптов Unity в head документа
             */
            appendUnityScripts() {
                let warnings = [],
                    errors = [];
                try {
                    warnings.push(['Метод appendUnityScripts, начали выполнение метода']);
                    let unityProgress = document.createElement('script'),                                               // Создаем пустые элементы скриптов
                        unityLoader = document.createElement('script'),
                        orderSystemUnity = document.createElement('script');
                    let self = this;                                                                                    // Делаем копию текущего объекта

                    warnings.push(['Метод appendUnityScripts, создали элементы скриптов']);

                    unityProgress.setAttribute('src', '/order_system/TemplateData/UnityProgress.js');                   // Задаем путь до файла скрипта
                    unityProgress.setAttribute('id', 'unityProgressScriptFile');                                        // Устаналиваем элементу скрипта id (для последующего удаления)
                    document.head.appendChild(unityProgress);                                                           // Добавляем скрипт в head документа
                    warnings.push(['Метод appendUnityScripts, установили скрипту UnityProgress путь и id', unityProgress]);

                    unityLoader.setAttribute('src', '/order_system/Build/UnityLoader.js');                              // Задаем путь до файла скрипта
                    unityLoader.setAttribute('id', 'unityLoaderScriptFile');                                            // Устаналиваем элементу скрипта id (для последующего удаления)
                    document.head.appendChild(unityLoader);                                                             // Добавляем скрипт в head документа
                    warnings.push(['Метод appendUnityScripts, установили скрипту UnityLoader путь и id', unityLoader]);

                    unityLoader.onload = function () {                                                                  // Как только UnityLoader загружен
                        // setTimeout(function appendUnityScript() {
                        orderSystemUnity.setAttribute('src', '/js/order-system-unity.js');                                  // Задаем путь до файла скрипта
                        orderSystemUnity.setAttribute('id', 'orderSystemUnityScriptFile');                                  // Устаналиваем элементу скрипта id (для последующего удаления)
                        document.head.appendChild(orderSystemUnity);                                                        // Подключаем скрипт
                        // }, 2000);                                                                                        // Добавляем скрипт в head документа после задержки (необходимо, чтобы этот скрипт был загружен последним)
                        warnings.push(['Метод appendUnityScripts, установили скрипту orderSystemUnity путь и id', orderSystemUnity]);

                        orderSystemUnity.onload = function () {                                                         // Как только order-system-unity.js загружен
                            self.saveDataForUnity();                                                                    // Вызываем метод отправки данных из меню в Unity
                            warnings.push(['Метод appendUnityScripts unityLoader.onload, вызван saveDataForUnity']);

                            // JSON.parse(localStorage.getItem('serialWorkerData')).userCompany
                            // localStorage.setItem('unity_tab', 'closed');

                            // let socketName = '04/10/2019';
                            // gameInstance.SendMessage('GlobalConfigsOrderSystem', 'SetIsolatedWebScoketChannel', socketName);
                            // console.log('Метод onload, вызвали метод Unity и передали ', socketName);
                            // let socketNamesArray = [];
                            // socketNamesArray.push(socketName);
                            // console.log('Метод onload, вызвали метод вебсокета на добавление канала', socketNamesArray);
                            // webSocket.subscribeWebSocket(socketNamesArray);
                        }
                    }
                } catch (error) {
                    errors.push({'os-interactive-form.vue. appendUnityScripts. Блок catch. Тип ошибки ': error.name});
                    errors.push({'os-interactive-form.vue. appendUnityScripts. Блок catch. Текст ошибки ': error.message});
                    errors.push({'os-interactive-form.vue. appendUnityScripts. Блок catch. Номер строки ': error.lineNumber});
                }
                console.warn(warnings);
                if (errors.length) console.error(errors);
            },

            /**
             * Метод удаления скриптов Unity из документа
             */
            removeUnityScripts() {
                let warnings = [],
                    errors = [];
                try {
                    warnings.push(['Метод removeUnityScripts, начали выполнение метода']);

                    let progressScriptElem = document.getElementById('unityProgressScriptFile');                        // Находим элемент скрипта
                    warnings.push(['Метод removeUnityScripts, нашли элемент с подключением скрипта UnityProgress', progressScriptElem]);

                    let loaderScriptElem = document.getElementById('unityLoaderScriptFile');                            // Находим элемент скрипта
                    warnings.push(['Метод removeUnityScripts, нашли элемент с подключением скрипта UnityLoader', loaderScriptElem]);

                    let unityScriptElem = document.getElementById('orderSystemUnityScriptFile');                        // Находим элемент скрипта
                    warnings.push(['Метод removeUnityScripts, нашли элемент с подключением скрипта order-system-unity', unityScriptElem]);

                    document.head.removeChild(progressScriptElem);                                                      // Удаляем элемент скрипта со страницы
                    warnings.push(['Метод removeUnityScripts, удалили скрипт UnityProgress']);
                    document.head.removeChild(loaderScriptElem);                                                        // Удаляем элемент скрипта со страницы
                    warnings.push(['Метод removeUnityScripts, удалили скрипт UnityLoader']);
                    document.head.removeChild(unityScriptElem);                                                         // Удаляем элемент скрипта со страницы
                    warnings.push(['Метод removeUnityScripts, удалили скрипт order-system-unity']);

                    warnings.push(['Метод removeUnityScripts, завершили выполнение метода']);
                } catch (error) {
                    errors.push({'os-interactive-form.vue. removeUnityScripts. Блок catch. Тип ошибки ': error.name});
                    errors.push({'os-interactive-form.vue. removeUnityScripts. Блок catch. Текст ошибки ': error.message});
                    errors.push({'os-interactive-form.vue. removeUnityScripts. Блок catch. Номер строки ': error.lineNumber});
                }
                console.warn(warnings);
                if (errors.length) console.error(errors);
            },

        },
        created() {                                                                                                     // Хук - при создании и активации системы реактивности
            this.$store.dispatch('setDefaultData');                                                                     // Вызываем action setDefaultData из store для получения основных справочников и данных
            this.$store.dispatch('GetEquipmentListGroup');
            this.$on("callByUnity", () => {
                showNotify("Получен запрос:: unity", "info");
            })



        },

        beforeUpdate() {
            // if(this.loaded) {
            //     this.$nextTick(() => {
            //         console.log("Обновление данных");
            //         this.saveDataForUnity();
            //     });
            // }
            // this.saveDataForUnity();
        },
        async mounted() {
            await this.appendUnityScripts();                                                                                  // Добавляем скрипты для Unity в документ

            let newDateTime = new Date().toString();
            // let msWebSocketTitle = Date.parse(newDateTime).toString();

            localStorage.setItem('unity_websocket_title', newDateTime);
            // localStorage.setItem('unity_websocket_title', msWebSocketTitle);

            // let subscribeArray = ["TestUnityWebFront"];                                                                 // "InteractiveFormUnity", "InteractiveFormWebPage"
            let subscribeArray = ["TestUnityWebFront",
                newDateTime
            ];                                                                             // "InteractiveFormUnity", "InteractiveFormWebPage"
            // let subscribeArray = [newDateTime];                                                                         // "InteractiveFormUnity", "InteractiveFormWebPage"
            // let subscribeArray = ['2019-10-04'];
            // webSocket.onopen = function() {
            //     console.log('Метод onopen, дождались!');

            setTimeout(() => {
                webSocket.subscribeWebSocket(subscribeArray);
            }, 2000);

            // setTimeout(() => {
            //     subscribeWebSocketFromUnity(webSocket);
            // }, 5000);


            // };
        },

        beforeDestroy() {
            this.removeUnityScripts();                                                                                  // Удаляем скрипты для Unity из документа
        },
    }

    // Блок для изменения размера схемы при изменения размера окна браузера
    let webglContentParameters = document.getElementById('webglContent') ? document.getElementById('webglContent').getBoundingClientRect() : null;
    let canvasFrame = document.getElementById('#canvas') ? document.getElementById('#canvas') : null;
    if (canvasFrame !== null) {
        canvasFrame.setAttribute('width', String(webglContentParameters.width));
        canvasFrame.setAttribute('height', String(webglContentParameters.height));
    }

    $(window).resize(function () {                                                                                            // Событие изменения размера окна
        if (canvasFrame !== null) {
            canvasFrame.setAttribute('width', String(webglContentParameters.width));
            canvasFrame.setAttribute('height', String(webglContentParameters.height));
        }
    });

</script>

<style scoped lang="less">

    .pageWrapper {
        width: 100%;
        height: 100%;
        background-color: #FFFFFF;
        overflow: hidden;

        .leftSideMenu {
            top: 300px;
            left: 0;
            position: absolute;
            display: flex;
            flex-direction: column;
            user-select: none;
            z-index: 1030;

            .selectEmployeeButton {
                display: flex;
                -webkit-box-align: center;
                -ms-flex-align: center;
                align-items: center;
                -webkit-box-pack: center;
                -ms-flex-pack: center;
                justify-content: center;
                cursor: pointer;

                a {
                    text-decoration: none
                }

                &-background {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 160px;
                    z-index: 1;
                }

                .buttonTitle {
                    width: 80%;
                    margin: 0 auto 0 26px;

                    p {
                        margin-top: 48px;
                        position: relative;
                        color: #ffffff;
                        line-height: 1.2;
                        font-size: 12px;
                        z-index: 2;
                    }
                }
            }
            .selectOperationsButton {
                display: flex;
                margin: auto;
                -webkit-box-align: center;
                -ms-flex-align: center;
                align-items: center;
                -webkit-box-pack: center;
                -ms-flex-pack: center;
                justify-content: center;
                cursor: pointer;

                a {
                    text-decoration: none
                }

                &-background {
                    position: absolute;
                    left: 0;
                    width: 160px;
                    z-index: 1;
                }

                .buttonTitle {
                    /*width: 80%;*/
                    margin: 0 auto 0 18px;

                    p {
                        margin-top: 48px;
                        position: relative;
                        color: #ffffff;
                        line-height: 1.2;
                        font-size: 12px;
                        z-index: 2;
                    }
                }
            }
        }

        .rightSideMenu {
            top: 300px;
            right: 0;
            position: absolute;
            display: flex;
            user-select: none;
            z-index: 1030;

            .viewOrderButton {
                display: flex;
                -webkit-box-align: center;
                -ms-flex-align: center;
                align-items: center;
                -webkit-box-pack: center;
                -ms-flex-pack: center;
                justify-content: center;
                cursor: pointer;

                a {
                    text-decoration: none
                }

                &-background {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    z-index: 1;
                }

                .buttonTitle {
                    width: 80%;
                    margin-left: auto;

                    p {
                        margin-top: 35px;
                        position: relative;
                        color: #ffffff;
                        line-height: 1.2;
                        font-size: 12px;
                        z-index: 2;
                    }
                }
            }

            .viewInstructionsButton {
                display: flex;
                -webkit-box-align: center;
                -ms-flex-align: center;
                align-items: center;
                -webkit-box-pack: center;
                -ms-flex-pack: center;
                justify-content: center;
                cursor: pointer;

                a {
                    text-decoration: none
                }

                &-background {
                    position: absolute;
                    top: 80px;
                    left: 0;
                    width: 100%;
                    z-index: 1;
                }

                .buttonTitle {
                    width: 80%;
                    margin-left: auto;

                    p {
                        margin-top: 35px;
                        position: relative;
                        color: #ffffff;
                        line-height: 1.2;
                        font-size: 12px;
                        z-index: 2;
                    }
                }
            }
        }
    }

    .interactiveTableFormWrapper {
        display: flex;
        flex-wrap: wrap;
        margin: 0 auto;
        min-width: 1024px;
        /*background-color: #fff;*/
        height: 100%;
        /*min-height: 760px;*/
        position: relative;
        z-index: 10;

        &-maxWindow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background-color: #fff;
            z-index: 1160;
        }
    }

    .header {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
        position: relative;

        .caption-logo {
            position: absolute;
            top: 0;
            left: 10px;

            img {
                height: 25px;
            }
        }


        .caption-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 0;
            right: 10px;

            &-maxWindow {
                width: 12px;
                height: 12px;
                border: 2px solid #000;
                border-radius: 1px;
                position: relative;
                top: 2px;
            }

            button {
                background: none;
            }

            .vertical-line {
                margin: 0 5px;
            }

            &-close {
                height: 12px;
                width: 14px;
                border: none;

                span {
                    display: inline-block;
                    position: relative;
                    width: 100%;
                    height: 100%;

                    &::before, &::after {
                        content: "";
                        display: block;
                        height: 2px;
                        width: 100%;
                        position: absolute;
                        top: 4px;
                        left: 0;
                        background-color: #000;
                    }

                    &:before {
                        -webkit-transform: rotate(45deg);
                        -moz-transform: rotate(45deg);
                        -ms-transform: rotate(45deg);
                        -o-transform: rotate(45deg);
                        transform: rotate(45deg);
                    }

                    &:after {
                        -webkit-transform: rotate(-45deg);
                        -moz-transform: rotate(-45deg);
                        -ms-transform: rotate(-45deg);
                        -o-transform: rotate(-45deg);
                        transform: rotate(-45deg);
                    }
                }
            }
        }

        &-signatures {
            width: 100%;
            display: flex;
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

        &-menu {
            width: 80%;
            height: 30px;
            display: flex;
            justify-content: center;
            @media (max-width: 1300px) {
                width: 90%;
            }

            p {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        &__dateBtn {
            width: 15%;
            background-color: #b9df3f;
            transform: skew(30deg);
            margin-right: 4px;
            cursor: pointer;
            display: flex;
            /*align-items: center;*/
            padding: 0 5px;

            &:hover {
                background-color: #C5EE43;
            }

            p {
                transform: skew(-30deg);
            }
        }

        &__dateTriangle {
            display: inline-block;
            width: 0;
            height: 0;
            border-left: 3px solid transparent;
            border-right: 3px solid transparent;
            border-top: 6px solid #000000;
            margin-left: 10px;
        }

        &__shiftWrapper {
            position: relative;
            width: 15%;
        }

        &__shiftBtn {
            background-color: #B2D63C;
            height: 100%;
            transform: skew(30deg);
            margin-right: 4px;
            cursor: pointer;

            &:hover {
                background-color: #C5EE43;
            }

            p {
                transform: skew(-30deg);
            }
        }

        &__shiftDropDownList {
            position: absolute;
            width: 100%;
            top: 33px;
            left: 0;
        }

        &__shiftTriangle {
            display: inline-block;
            width: 0;
            height: 0;
            border-left: 3px solid transparent;
            border-right: 3px solid transparent;
            border-top: 6px solid #000000;
            margin-left: 10px;
        }

        &__title {
            width: 30%;
            display: flex;
            position: relative;
            cursor: pointer;
        }

        &__titleText {
            position: absolute;
        }

        &__titleSkewedLeft {
            position: absolute;
            left: 0;
            width: 70%;
            height: 100%;
            transform: skew(30deg);
            background-color: #B2D63C;

        }

        &__titleSkewedRight {
            position: absolute;
            right: 0;
            width: 70%;
            height: 100%;
            transform: skew(-30deg);
            background-color: #B2D63C;
        }

        &__brigadeWrapper {
            position: relative;
            width: 20%;
        }

        &__brigadeBtn {
            background-color: #B2D63C;
            transform: skew(-30deg);
            margin-left: 4px;
            cursor: pointer;
            height: 100%;

            &:hover {
                background-color: #C5EE43;
            }

            p {
                transform: skew(30deg);
            }
        }

        &__brigadeDropDownList {
            position: absolute;
            width: 100%;
            left: 0;
            top: 33px;
        }

        &__brigadeTriangle {
            display: inline-block;
            width: 0;
            height: 0;
            border-left: 3px solid transparent;
            border-right: 3px solid transparent;
            border-top: 6px solid #000000;
            margin-left: 10px;
        }

        &__departmentWrapper {
            position: relative;
            width: 20%;
        }

        &__departmentBtn {
            background-color: #B2D63C;
            transform: skew(-30deg);
            margin-left: 4px;
            cursor: pointer;
            height: 100%;

            &:hover {
                background-color: #C5EE43;
            }

            p {
                transform: skew(30deg);
            }
        }

        &__departmentDropDownList {
            background-color: #fff;
            position: absolute;
            width: 100%;
            left: 0;
            top: 33px;
        }

        &__departmentTriangle {
            display: inline-block;
            width: 0;
            height: 0;
            border-left: 3px solid transparent;
            border-right: 3px solid transparent;
            border-top: 6px solid #000000;
            margin-left: 10px;
        }

        &__headerTriangleRotated {
            transform: rotate(180deg);
        }

        &__templateBuilderWrapper {
            width: 20%;
        }

        &__templateBuilder {
            height: 100%;
            background-color: #56698F;
            transform: skew(-30deg);
            margin-left: 4px;
            cursor: pointer;
            color: #fff;

            &:hover {
                background-color: #758ec2;
            }

            p {
                transform: skew(30deg);
            }
        }

        &__templateDropDownList {
            position: relative;
            z-index: 9999;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background-color: #fff;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, .5);
            padding: 2px;

            p {
                padding: 5px;
                border: 2px solid #758ec2;
                cursor: pointer;
                margin-bottom: 2px;

                &:last-child {
                    margin-bottom: 0;
                }

                &:hover {
                    background-color: #eee;
                }
            }
        }

    }

    .departmentListSelect {
        position: absolute;
        background-color: #fff;
        width: 350px;
        max-height: 300px;
        z-index: 9999;
        overflow: auto;
        box-shadow: 4px 4px 4px 0 rgb(114, 114, 114);
        text-align: left;
    }

    .os-headerDropDownList {
        position: relative;
        z-index: 9999;
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

    .webgl-content {
        height: 70vh;
        width: calc(100% - 60px);
        position: relative!important;
        top: 0!important;
        left: 0!important;
        transform: none;
        z-index: 1;
        margin: 15px auto 15px;
    }

    #gameContainer {
        height: 100%;
        width: 100%;
    }

    .worker-list-grouped {
        position: absolute;
        left: 0;
        bottom: 0;
        z-index: 1030;
        /*height: 300px;*/
        max-height: 350px;
        width: 200px;
        line-height: 1.3;
    }
</style>
