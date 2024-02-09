<!-- выпадающий список выбора шаблона наряда
   разработал: Якимов М.Н.
   дата: 05.09.2019
   отредактировал Курбанов И. С.
   дата: 31.10.2019
-->
<template>
    <div id="orderTemplateList" @click.stop="changeActiveModal2">
        <div class="dropDownList">
            <div class="dropDownList__header"><span>Шаблон наряда на производство работ</span></div>
            <div class="dropDownList__closeBtn" @click.stop="closeDropDownTemplateOrders()">
                <div class="dropDownList__closeBtnLine"></div>
            </div>
            <div class="container-search-input">
                <input class="search-input" type="text" placeholder="Поиск шаблона" v-model="searchInput">
                <button class="input-form-btns btn-reset-text" @click="searchInput=''"></button>
                <button class="input-form-btns btn-search"></button>
            </div>
            <!-- блок со всем списком инструктажей -->
            <div class="dropDownList__list">
                <template v-for="templateOrder in filteredTemplateOrders">
                    <div class="templateOrder-row"
                         @click="setOrderVtbAbTemplate(templateOrder)"
                         @contextmenu.prevent.stop="showContextMenu($event, templateOrder.template_order_vtb_ab_id)"
                         :key="templateOrder.template_order_vtb_ab_id">
                        {{templateOrder.title}}
                    </div>
                </template>
            </div>
        </div>
        <div class="order-template-context-menu" v-show="activeModal2 === 'orderTemplateContextMenu'">
            <order-template-context-menu @deleteOrderTemplate="deleteOrderTemplate"/>
        </div>
    </div>
</template>

<script>

import orderVtbStore from "../../orderVtbStore";
import orderTemplateContextMenu from "../dropdowns/orderTemplateContextMenu";

export default {
  name: "OrderTemplateList",
  props: {
    orderTemplateList: Object,
  },
  components: {
    orderTemplateContextMenu
  },
  data() {
    return {
      searchInput: '',
                activeOrderTemplateId: null,                                                                                   // выбранный шаблон, устанавливается при клике правой кнопкой мыши
            }
        },
        computed: {
            /**
             * вычисляемое свойство списка инструктажей, которое возвращает список с учетом поискового запроса
             **/
            filteredTemplateOrders() {
                let warnings = [];
                let filteredTemplateOrders = [];
                try {
                  warnings.push("filteredTemplateOrders. Начал выполнять метод");
                  warnings.push("filteredTemplateOrders. начал построение списка инструктажей", this.orderTemplateList);

                    if (this.searchInput.length > 2) {                                                                    // проверяем строку поиска на наличие запроса
                        warnings.push("filteredTemplateOrders. с поиском");
                        //объявляем пустой массив для формирования списка сотрудников с учетом поискового запроса
                        let templateOrderObject = null;
                        for (let templateOrderId in this.orderTemplateList) {                                                            //пробегаемся по списку инструктажей (объект)
                            templateOrderObject = this.orderTemplateList[templateOrderId];                                                  //объявляем переменную для хранения инструктажей
                            if (templateOrderObject.title.toLowerCase().indexOf(this.searchInput.toLowerCase()) > -1) { //ищем подстроку в строке, переводя всё в нижний регистр (-1 означает, что подстрока не была найдена в строке)
                                filteredTemplateOrders.push(templateOrderObject);                                                         //добавляем в конец массива объект работника
                            }
                        }
                        //возвращаем список инструктажей с учетом поиска
                    } else {
                        warnings.push("filteredTemplateOrders. без поиска");
                        filteredTemplateOrders = this.orderTemplateList;
                    }
                  warnings.push("filteredTemplateOrders. закончил построение списка инструктажей", filteredTemplateOrders);
                  warnings.push("filteredTemplateOrders. Закончил выполнять метод");
                } catch (err) {
                    console.log("filteredTemplateOrders. Исключение");
                    console.log(err);
                }
                // console.log(warnings);
                return filteredTemplateOrders;                                                                                // иначе возвращаем исходный список
            },
            activeModal2: {
                get() {
                    return orderVtbStore.getters.ACTIVEMODAL2;
                },
                set(newActiveModal) {
                    orderVtbStore.dispatch('setActiveModal2', newActiveModal);
                }
            }
        },
        methods: {

            setOrderVtbAbTemplate(templateOrderObj) {
                this.$emit('setOrderVtbAbTemplate', templateOrderObj);
                console.log('addTemplateOrderInParent. Передача данных в setSelectedTemplateOrderId');
            },

            /**Метод в момент вызова отправляет событие closeDropDownOrders
             * событие сообщает о том что выпадающий список необходимо закрыть*/
            closeDropDownTemplateOrders() {
                this.searchInput = '';
                this.$emit('closeDropdownList');
            },
            
            changeActiveModal2(modalName = '') {
                console.log('OrderTemplateList.vue, changeActiveModal2. modalName',modalName);
                console.count();
                if (this.activeModal2 === modalName) {
                    console.log('OrderTemplateList.vue, changeActiveModal2 if case',);
                    this.activeModal2 = '';
                } else {
                    console.log('OrderTemplateList.vue, changeActiveModal2 else case',);
                    this.activeModal2 = modalName;
                }
            },
            
            showContextMenu(event, orderTemplateId) {
                console.log('OrderTemplateList.vue, showContextMenu. event',event, orderTemplateId);
                this.changeActiveModal2();
                let modal = document.querySelector('.order-template-context-menu');
                console.log('OrderTemplateList.vue, showContextMenu. modal ', modal);
                let position = calculateModalPosition(event.clientX + 10, event.clientY + 10, 170, 30, "#orderTemplateList");
                console.log('OrderTemplateList.vue, showContextMenu position', position);
                if (position.status === 1) {
                    modal.style.left = position.left;
                    modal.style.top = position.top;
                }
                this.activeOrderTemplateId = orderTemplateId;
                this.changeActiveModal2('orderTemplateContextMenu');
                console.log('OrderTemplateList.vue, showContextMenu. после установки активной модалки второго уровня', this.activeModal2);
            },
            
            deleteOrderTemplate() {
                console.log('OrderTemplateList.vue, deleteOrderTemplate. activeOrderTemplateId ', this.activeOrderTemplateId);
                orderVtbStore.dispatch('ajaxDeleteOrderAbVtbTemplate', this.activeOrderTemplateId);
                this.activeOrderTemplateId = null;
                this.changeActiveModal2();
            }
        },

        mounted() {
            $("#orderTemplateList").draggable({
                containment: '.max-content',
                handle: '.dropDownList__header',
                scroll: false
            });
            $("#orderTemplateList").resizable({
                minWidth: 300,
                maxWidth: 500,
                minHeight: 250,
                maxHeight: 500,
            });
        },
        updated() {
            // this.activeOrderTemplateId = null;
        },
        beforeDestroy() {
            this.activeOrderTemplateId = null;
            this.searchInput = '';
        }
    }

</script>

<style lang="less" scoped>
    .order-template-context-menu {
        position: absolute;
        top: 0;
        left: 0;
        width: 170px;
        height: 30px;
        box-shadow: 2px 2px 5px rgba(0,0,0,0.35);
    }
    .dropDownList {
        position: relative;
        width: 400px;
        font-size: 14px;
        text-align: left;
        background-color: #808080;
        padding: 3px;
        box-shadow: 5px 6px 10px 0 rgba(0, 0, 0, 0.35);

        &__list {
            overflow-y: scroll;
            overflow-x: hidden;
            height: 250px;
            margin: 3px 1px 0;
            background-color: #fff;

            .templateOrder-row {
                padding: 5px 5px 5px 15px;
                position: relative;
                display: flex;
                height: 30px;

                &:hover {
                    background-color: #ebecec;
                    cursor: pointer;
                }
            }

            .templateOrder-row > .tabel-number {
                position: absolute;
                right: 5px;
                width: 100px;
                text-align: right;
            }

            .templateOrder-row > .fullname {
                position: absolute;
                left: 10px;
            }
        }

        &__header {
            display: flex;
            align-items: center;
            justify-content: center;
            padding-left: 5px;
            color: #fff;
            height: 30px;
            
            &:hover {
                cursor: move;
            }
        }

        &__search {
            input {
                width: 90%;
                margin: 3px;
                border-radius: 5px;
                border: unset;
                padding-left: 5px;
            }
        }

        .search-input {
            width: 100%;
            height: 100%;
            padding-right: 47px;
            padding-left: 10px;
            color: gray;
            outline: none;
            border: none;
        }

        .container-search-input {
            position: relative;
            width: 100%;
            height: 30px;
            padding: 0 1px;
        }

        .btn-search {
            width: 23px;
            height: 26px;
            background: url('../../assets/search.png') no-repeat center;
            background-size: 100%, 100%;
        }

        .input-form-btns {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translate(0, -50%);
            padding: 0;
            border: none;
            cursor: pointer;
            outline: none;
        }

        .btn-reset-text {
            width: 23px;
            height: 26px;
            margin-right: 23px;
            background: url('../../assets/resetText.png') no-repeat center;
            background-size: 100%, 100%;
        }

        &__closeBtn {
            position: absolute;
            z-index: 1000;
            top: 10px;
            right: 5px;
            cursor: pointer;
        }

        &__closeBtnLine {
            display: block;
            width: 15px;
            height: 2px;
            background-color: #fff;

            transform: rotate(45deg);

            &::before {
                content: '';
                display: block;
                width: 15px;
                height: 2px;
                background-color: inherit;
                transform: rotate(90deg);
            }
        }
    }

</style>
