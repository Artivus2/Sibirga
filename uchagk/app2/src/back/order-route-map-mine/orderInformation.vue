<!--
    Описание компонента:
        Компонент "Информация о наряде", отображает информацию о наряде в хронологическом порядке.
    Родительский компонент:
        order-route-map.vue
    Входные данные:
        ordersObject - сформированный объект(в родителе) для отображения данных
        selectedDate - выбранная дата
        selectedOrderId - выбранный наряд
        selectedShift - выбранная смена
        chosenDepartment - выбранный участок
        closeAllModalAndDropdownWindows - закрытие всех окон
    Разработал:
        Ругаль П.Н
 -->

<template>

    <div class="modal" @click.stop="closeAllModalAndDropdown">

        <div class="modal-orderInformation" @click.stop>

            <div class="header">

                <div class="header-title">
                    <span>Информация о наряде</span>
                </div>

                <div class="header-dateAndChange">
                    <div class="date">
                        <img src="@/modules/book-directive/assets/icon/calendar.png" alt="">
                        <div>{{selectedDate}}</div>
                    </div>
                    <div class="change">
                        <span>Смена:</span>
                        <div>{{selectedShift}}</div>
                    </div>
                </div>

            </div>

            <div class="body">

                <div class="body-title">
                    {{chosenDepartment.title}}
                </div>

                <div class="body-table">
                    
                    <div class="body-table-title">
                        <div>Время</div>
                        <div>Действие по наряду</div>
                        <div>ФИО</div>
                        <div>Комментарий</div>
                    </div>

                    <div class="body-table-content"
                         v-for="(status, key , index) in ordersObject.status_history">



                        <div class="time">
                            {{status.date_time}}
                        </div>
                        <!-- Если наряд создан первый раз -->
                        <div class="status">
                            {{status.status_title}}
                        </div>
<!--                        &lt;!&ndash; Если наряд создан последующие разы &ndash;&gt;-->
<!--                        <div class="status"-->
<!--                             v-else-if="index > 0">-->
<!--                            Наряд сохранен-->
<!--                        </div>-->
                        <div class="full-name">
                            {{status.full_name}}
                        </div>
                        
                        <div class="description">
                            {{ status.description }}
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

</template>

<script>


    export default {

        name: "orderInformation",

        props: {
            ordersObject: Object,                                                                                       // сформированный объект(в родителе) для отображения данных
            selectedDate: String,                                                                                       // выбранная дата
            selectedOrderId: Number,                                                                                    // выбранный наряд
            selectedShift: Number,                                                                                      // выбранная смена
            chosenDepartment: Object,                                                                                   // выбранный участок

            closeAllModalAndDropdownWindows: Function,                                                                  // закрытие всех окон
        },

        date() {
            return {
               orderCreated: 'Наряд создан',
            }
        },
        methods: {
            closeAllModalAndDropdown (){
              this.$emit('closeAllModalAndDropdownWindows');
            },
        },
    }

</script>

<style scoped lang="less">

    .modal {
        display: flex;
        background-color: rgba(0, 0, 0, 0.5);

        &-orderInformation {
            width: 800px;
            min-height: 500px;
            display: flex;
            flex-direction: column;
            margin: auto;
            overflow: auto;
            background-color: #fff;
            border: 2px solid #4d897c;

            .header {
                width: 100%;
                height: 70px;
                margin-bottom: 3px;
                background: #4d897c;

                &-title {
                    margin: 5px auto 10px;
                    color: #fff;
                }

                &-dateAndChange {
                    display: flex;
                    justify-content: center;

                    .date {
                        display: flex;
                        align-items: center;
                        margin-right: 25px;

                        img {
                            width: 20px;
                            height: 20px;
                            margin-right: 10px;
                        }

                        div {
                            padding: 3px 10px;
                            background-color: #ebecec;
                            border-radius: 1px;
                        }
                    }

                    .change {
                        display: flex;
                        align-items: center;

                        span {
                            margin-right: 10px;
                            color: #fff;
                        }

                        div {
                            padding: 3px 10px;
                            background-color: #ebecec;
                            border-radius: 1px;
                        }
                    }

                }
            }

            .body {

                &-title {
                    padding: 3px;
                    background-color: #ebecec;
                }

                &-table {
                    width: 100%;
                    max-height: 500px;
                    overflow: auto;

                    &-title {
                        width: 100%;
                        display: flex;
                        margin: 5px 0 0;
                        border: 1px solid #4d897c;
                        border-left: none;
                        border-right: none;

                        div:not(:last-of-type) {
                            border-right: 1px solid #4d897c;
                        }

                        div:first-of-type {
                            width: 17%;
                            margin: 5px 0;
                        }
                        div:nth-of-type(2) {
                            width: 23%;
                            margin: 5px 0;
                        }
                        div:nth-of-type(3) {
                            width: 30%;
                            margin: 5px 0;
                        }
                        div:last-of-type {
                            width: 30%;
                            margin: 5px 0;
                            /*border: none;*/
                        }
                    }

                    &-content {
                        width: 100%;
                        display: flex;
                        margin: 5px 0;
                        border-bottom: 1px solid #dedede;

                        div {
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            margin: 5px 0;
                        }

                        div:not(:last-of-type) {
                            border-right: 1px solid #dedede;
                        }

                        div:first-of-type {
                            width: 17%;
                        }
                        div:nth-of-type(2) {
                            width: 23%;
                        }
                        div:nth-of-type(3) {
                            width: 30%;
                        }
                        div:last-of-type {
                            width: 30%;
                            /*border: none;*/
                        }
                    }
                }
            }
        }
    }

</style>
