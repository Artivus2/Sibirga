<!--
    Подключается: на странице "Заполнение отчета", "Выдача наряда", "Корректировка наряда"
    Компонент отображения списка операций и установки выбранного(справа)
    Входные параметры:
        operationsList - список операций
        parentElement - селектор блока в пределах которого необходимо перемещать модальное окно
        modalElement - селектор блока в котором позицианируется модальное окно
    Разработка Сибирцев М.Б.
 -->
<template>
    <!--  v-on:mousedown="dragnDropModal($event)" -->
        <div class="dropDownOperations" v-on:click.stop>
            <div class="dropDownOperations-header"><p>Выберите задачу</p></div>
            <div class="dropDownOperations-close"
                 v-on:click="closeDropDownOperations">
                <span class="dropDownOperations-close-btn"></span>
            </div>
            <div class="dropDownOperations-search">
                <img src="@/assets/universalModalWindows/universalModalWindowsSearch.png" alt="#" v-if="!searchInput">
                <button v-else v-on:click="searchInput = ''"><span></span></button>
                <input type="text" placeholder="поиск задания..." v-model="searchInput" v-on:mousedown.stop>
            </div>


            <div class="dropDownOperations-list">

                <div class="kind-row"
                     v-for="kindOperation in filterOperationList"
                     :key="kindOperation.kind_operation_id">

                    <div class="dropDownOperations-list-wrapper-kind"
                        v-on:click.stop="openOperationKind(kindOperation.kind_operation_id)"
                    >
                        <p class="dropDownOperations-list-caption">{{kindOperation.kind_operation_title}}</p>
                        <div :class="(operationKindId === kindOperation.kind_operation_id) || fullOpen ? 'minus' : 'plus'"></div>

                        <!-- Список типовых операций -->
                        <div class="kind-type-sublist"
                             v-on:click.stop
                             v-if="(operationKindId === kindOperation.kind_operation_id) || fullOpen"
                        >
                            <template v-for="operationType in kindOperation.operation_type">

                                <div class="kind-type-row"
                                     v-on:click.stop="openOperationType(operationType.operation_type_id)"
                                >
                                    <p class="dropDownOperations-list-caption">{{operationType.operation_type_title}}</p>
                                    <div :class="(operationKindId === kindOperation.kind_operation_id &&
                                            operationTypeId === operationType.operation_type_id) || fullOpen ? 'minus' : 'plus'"></div>


                                    <!-- Раздел списка операций в типовых операциях -->
                                    <div class="kind-type-operation-sublist"
                                         v-on:click.stop
                                         v-if="(operationKindId === kindOperation.kind_operation_id &&
                                            operationTypeId === operationType.operation_type_id) || fullOpen">

                                        <template v-for="operation in operationType.operation">
                                            <div class="kind-type-operation-row"
                                                 v-on:click="addOperation(operation)">
                                                <p>{{operation.operation_title}}</p>
                                            </div>
                                        </template>

                                    </div>
                                </div>

                            </template>
                        </div>

                    </div>

                </div>

            </div>
            

        </div>
</template>

<script>
    export default {
        name: "operationsDropdownList",
        props: {
            operationsList: {
                type: Object || Array,
            },
            parentElement: {
                required: false,
            },
            modalElement: {
                required: false,
            },
        },
        data() {
            return {
                operationTypeId: '',
                operationKindId: '',
                searchInput: '',
                showListWrapperLeftLine: true,
                fullOpen: false,
                operationElement: {}
            }
        },
        computed: {
            /**
             * Вычисляемое свойство которое возвращает отфильтрованный по поисковому запросу массив
             * */
            filterOperationList() {
                let deleteKind = true,
                    deleteType = true,
                    deleteTypical = true,
                    deleteMine = true,
                    newOperationsList = {};

                if (this.searchInput.length > 2) {
                    this.fullOpen = true;                                                                               // установка флага отобажения всех вложенных списков
                    for (let kindId in this.operationsList) {
                        let kindElement = this.operationsList[kindId];
                        newOperationsList[kindId] = {
                            kind_operation_id: kindElement.kind_operation_id,
                            kind_operation_title: kindElement.kind_operation_title,
                            operation_type: {}
                        };
                        for (let typeOperationId in kindElement.operation_type) {
                            let typeOperationElement = kindElement.operation_type[typeOperationId];
                            newOperationsList[kindId].operation_type[typeOperationId] = {
                                operation_type_id: typeOperationElement.operation_type_id,
                                operation_type_title: typeOperationElement.operation_type_title,
                                operation: {}
                            };
                            for (let operationId in typeOperationElement.operation) {
                                let operationElement = typeOperationElement.operation[operationId]
                                if (String(operationElement.operation_title	).toLowerCase().indexOf(this.searchInput.toLowerCase() ) > -1 ) {
                                    newOperationsList[kindId].operation_type[typeOperationId].operation[operationId] = operationElement;
                                    deleteType = false;
                                    deleteKind = false
                                }
                            }
                            if (deleteType) {
                                delete newOperationsList[kindId].operation_type[typeOperationId]
                            } else {
                                deleteType = true
                            }
                        }
                        if (deleteKind) {
                            delete newOperationsList[kindId]
                        } else {
                            deleteKind = true
                        }
                    }
                    return newOperationsList
                } else {
                    this.fullOpen = false;
                    return this.operationsList
                }
            }
        },
        methods: {
            /**
             * Отображение списка операций
             * */
            openOperationType(number = '') {
                if (this.operationTypeId === number) {
                    this.operationTypeId = ''
                } else {
                    this.operationTypeId = number
                }
            },
            /**
             * Отображение списка типовых операций
             * */
            openOperationKind(number = '') {
                this.operationTypeId ='';
                if (this.operationKindId === number) {
                    this.operationKindId = ''
                } else {
                    this.operationKindId = number
                }
            },
            /**
             * Метод закрытия модального окна
             * */
            closeDropDownOperations() {
                this.searchInput ='';
                this.operationTypeId ='';
                this.operationKindId = '';
                this.$emit('closeDropDownList');
            },
            /**
             * Метод добавление выбранной операции
             * */
            addOperation(operation) {
                this.$emit('addOperation', operation);
               // console.log('передал объект в родительский компонент из os-dropdown-OperationList ', operation);
                //this.closeDropDownOperations();
            },

            /**
             * Метод возвращает отфильтрованный по тексту поискового запроса
             * массив пришедший в параметрах массив
             * @param operationsList массив который необходимо отфильтровать
             * @param searchInput - критерий поиск
             * */
            // searchOperation(operationsList, searchInput) {
            //     let deleteKind = true,
            //         deleteType = true,
            //         deleteTypical = true,
            //         deleteMine = true,
            //         newOperationsList = {};
            //
            //     if (searchInput.length > 2) {
            //         this.fullOpen = true;                                                                               // установка флага отобажения всех вложенных списков
            //         for (let kindId in operationsList) {
            //             let kindElement = operationsList[kindId];
            //             newOperationsList[kindId] = {
            //                 kind_operation_id: kindElement.kind_operation_id,
            //                 kind_operation_title: kindElement.kind_operation_title,
            //                 operation_type: {}
            //             };
            //             for (let typeOperationId in kindElement.operation_type) {
            //                 let typeOperationElement = kindElement.operation_type[typeOperationId];
            //                 newOperationsList[kindId].operation_type[typeOperationId] = {
            //                     operation_type_id: typeOperationElement.operation_type_id,
            //                     operation_type_title: typeOperationElement.operation_type_title,
            //                     operation: {}
            //                 };
            //                 for (let operationId in typeOperationElement.operation) {
            //                     let operationElement = typeOperationElement.operation[operationId]
            //                     if (String(operationElement.operation_title	).toLowerCase().indexOf(searchInput.toLowerCase() ) > -1 ) {
            //                         newOperationsList[kindId].operation_type[typeOperationId].operation[operationId] = operationElement;
            //                         deleteType = false;
            //                         deleteKind = false
            //                     }
            //                 }
            //                 if (deleteType) {
            //                     delete newOperationsList[kindId].operation_type[typeOperationId]
            //                 } else {
            //                     deleteType = true
            //                 }
            //             }
            //             if (deleteKind) {
            //                 delete newOperationsList[kindId]
            //             } else {
            //                 deleteKind = true
            //             }
            //         }
            //         return newOperationsList
            //     } else {
            //         this.fullOpen = false;
            //         return operationsList
            //     }
            //
            // },
            /**
             * Самописная функция перемещения модалки (не работает, доработать и избавиться от плагина джейквери)
             * */
            dragnDropModal() {
               // dragnDropModalModules(this)
            },

        },
        mounted() {
            let localParentElement = this.parentElement;
            let localModalElement = this.modalElement;
            $(localModalElement).draggable({
                containment: localParentElement,
                handle: '.dropDownOperations-header',
                scroll: false
            });
            $(localModalElement).resizable({
                minWidth: 300,
                maxWidth: 500,
                minHeight: 250,
                maxHeight: 500,
            });
        },
        
        beforeDestroy() {
            this.operationTypeId = '';
            this.operationKindId = '';
            this.searchInput = '';
            this.fullOpen = false;
        }
    }
</script>

<style scoped lang="less">
    * {
        padding: 0;
        margin: 0;
    }
    .dropDownOperations {
        height: 100%;
        width: 100%;
        position: relative;
        font-size: 14px;
        text-align: left;
        box-shadow: 5px 6px 4px 0px rgba(0, 0, 0, 0.35);
        background-color: #fff;
        border: 2px solid #808080;
        &-header {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 35px;
            color: #fff;
            background-color: #808080;
    
            &:hover {
                cursor: move;
            }
        }
        &-search {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 30px;
            border-bottom: 2px solid #808080;
            img {
                position: absolute;
                top: 2px;
                right: 5px;
                height: 25px;
            }
            button {
                position: absolute;
                top: 2px;
                right: 0;
                height: 25px;
                width: 25px;
                background-color: transparent;
                border: none;
                span {
                    position: relative;
                    display: block;
                    height: 100%;
                    width: 100%;
                    &:after {
                        content: '';
                        position: absolute;
                        top: 11px;
                        left: 2px;
                        display: block;
                        width: 16px;
                        height: 2px;
                        background-color: #C5C5C5;
                        transform: rotate(45deg);

                    }
                    &:before {
                        content: '';
                        position: absolute;
                        top: 11px;
                        left: 2px;
                        display: block;
                        width: 16px;
                        height: 2px;
                        background-color: #C5C5C5;
                        transform: rotate(-45deg);
                    }
                }

            }
            input {
                padding: 5px 20px 5px 5px;
                height: 100%;
                width: 100%;
                border: 1px solid #dedede;
            }
        }
        &-close {
            position: absolute;
            z-index: 1000;
            top: 4px;
            right: 5px;
            cursor: pointer;
            &-btn {
                position: relative;
                display: block;
                height: 20px;
                width: 20px;
                &:after {
                    content: '';
                    position: absolute;
                    top: 10px;
                    left: 0px;
                    display: block;
                    width: 20px;
                    height: 2px;
                    background-color: #fff;
                    transform: rotate(45deg);

                }
                &:before {
                    content: '';
                    position: absolute;
                    top: 10px;
                    left: 0px;
                    display: block;
                    width: 20px;
                    height: 2px;
                    background-color: #fff;
                    transform: rotate(-45deg);
                }
            }
        }

        &-list {
            overflow-y: scroll;
            overflow-x: hidden;
            height: calc(100% - 65px);
            background-color: #fff;
            &-caption {
                background-color: #CCCCCC;
                padding: 5px 5px 5px 35px;
            }
        }
    }

    div[class$=sublist] {
        position: relative;
        padding-left: 20px;
        margin-top: 2px;

    }
    div[class$=row] {
        position: relative;
        margin-top: 2px;
        p {
            &:hover {
                cursor: pointer;
            }
        }
    }

    .minus {
        position: absolute;
        top: 8px;
        left: 9px;
        height: 16px;
        width: 16px;
        border: 2px solid #5D5D5D;
        cursor: pointer;
        &:after {
            content: '';
            position: absolute;
            top: 5px;
            left: 2px;
            display: block;
            width: 8px;
            height: 2px;
            background-color: #5D5D5D;
        }

    }
    .plus {
        position: absolute;
        top: 8px;
        left: 9px;
        height: 16px;
        width: 16px;
        border: 2px solid #5D5D5D;
        cursor: pointer;
        &:after {
            content: '';
            position: absolute;
            top: 5px;
            left: 1px;
            display: block;
            width: 10px;
            height: 2px;
            background-color: #5D5D5D;
        }
        &:before {
            content: '';
            position: absolute;
            top: 5px;
            left: 1px;
            display: block;
            width: 10px;
            height: 2px;
            background-color: #5D5D5D;
            transform: rotate(90deg);
        }
    }
    .kind-type-operation-row {
        display: flex;
        position: relative;

        p {
            width: 100%;
            position: relative;
            padding: 5px 30px 5px 5px;

            &:hover {
                background-color: #E6E6E6;
            }

            img {
                position: absolute;
                height: 34px;
                top: 50%;
                margin-top: -17px;
                right: 2px;
            }

        }
    }
</style>
