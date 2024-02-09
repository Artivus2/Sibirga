<!-- модалка создания шаблона наряда
   разработал: Якимов М.Н.
   дата: 05.09.2019
   отредактировал Курбанов И. С.
   дата: 31.10.2019
-->
<template>
    <div id="OrderTemplateCreationWindow" @click.stop>
        <div class="createModal">
            <div class="createModal__header"><span>Создать шаблон наряда на производство работ</span></div>
            <div class="createModal__closeBtn" @click.stop="closeDropDownTemplateOrders()">
                <div class="createModal__closeBtnLine"></div>
            </div>

            <!-- блок со всем списком инструктажей -->
            <div class="createModal__list">
                <div class="createModal_title">Введите название шаблона:</div>

                <div class="createModal_input">
                    <input type="text"
                           class="createModal_input-field"
                           placeholder="Название шаблона"
                           v-model.lazy="templateTitle">
                </div>
            </div>
            <div class="createModal__groupButton">
                <div class="createModal_buttonCancel">
                    <div class="createModal_buttonCancel-rotate" @click.stop="closeDropDownTemplateOrders()"><p>
                        Отмена</p></div>
                </div>
                <div class="createModal_buttonSave">
                    <div class="createModal_buttonSave-rotate" @click.stop="createOrderVtbAbTemplate()"><p>Сохранить</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>


    export default {
        name: "OrderTemplateCreationWindow",
        props: {
            orderTemplateList: Object,
        },

        data() {
            return {
                templateTitle: '',
            }
        },
        computed: {},
        methods: {

            createOrderVtbAbTemplate() {
                let warnings = [];
                try {
                    warnings.push("createOrderVtbAbTemplate. Начал выполнять метод");
                    // проверяем на совпадение имен внутри уже существующих шаблонов
                    if (Object.keys(this.orderTemplateList).length) {
                        let templateOrderTitle = "";
                        for (let templateOrderId in this.orderTemplateList) {                                                            //пробегаемся по списку сотрудников (объект)
                            templateOrderTitle = this.orderTemplateList[templateOrderId].title;                                                  //объявляем переменную для хранения работника
                            if (templateOrderTitle.toLowerCase().indexOf(this.templateTitle.toLowerCase()) > -1) { //ищем подстроку в строке, переводя всё в нижний регистр (-1 означает, что подстрока не была найдена в строке)
                                showNotify("Название введенного шаблона существует! Введите другое название!", "danger");
                                throw new Error("Название введенного шаблона существует! Введите другое название!");
                            }
                        }
                    }
                    this.$emit('createOrderVtbAbTemplate', this.templateTitle);

                  warnings.push("createOrderVtbAbTemplate. Закончил выполнять метод");
                } catch (err) {
                    console.log("createOrderVtbAbTemplate. Исключение");
                    console.log(err);
                }
                console.log(warnings);
            },

            /**Метод в момент вызова отправляет событие closeDropDownOrders
             * событие сообщает о том что выпадающий список необходимо закрыть*/
            closeDropDownTemplateOrders() {
                this.$emit('closeDropdownList');
            },

        },

        mounted() {
            $("#OrderTemplateCreationWindow").draggable({
                containment: '.max-content',
                handle: '.createModal__header',
                scroll: false
            });
        },
        beforeDestroy() {
            this.templateTitle = '';
        }
    }

</script>

<style lang="less" scoped>

    .createModal {
        position: relative;
        width: 500px;
        font-size: 14px;
        text-align: left;
        background-color: #808080;
        padding: 3px;
        box-shadow: 5px 6px 10px 0 rgba(0, 0, 0, 0.35);

        &__groupButton {
            display: flex;
            justify-content: space-between;
            padding: 0;
            background: white;
            margin: 0 2px 2px;
            color: white;

            .createModal_buttonCancel {
                left: 0;
                overflow: hidden;
                width: 120px;
                height: 30px;

                .createModal_buttonCancel-rotate {
                    background: #b3b3b3;
                    transform: skew(30deg);
                    width: 110px;
                    height: 30px;
                    margin-left: -10px;
                    padding-left: 10px;
                    
                    p {
                        transform: skew(-30deg);
                        margin-left: 20px;
                        padding-top: 5px;
                    }
                }

                .createModal_buttonCancel-rotate:hover {
                    cursor: pointer;
                    background-color: #6a7080;
                }
            }

            .createModal_buttonSave {
                right: 0;
                overflow: hidden;
                width: 120px;
                height: 30px;

                .createModal_buttonSave-rotate {
                    background: #41746c;
                    transform: skew(-30deg);
                    text-align: right;
                    width: 110px;
                    height: 30px;
                    margin-left: 20px;

                    p {
                        transform: skew(30deg);
                        margin-right: 20px;
                        padding-top: 5px;
                    }
                }

                .createModal_buttonSave-rotate:hover {
                    cursor: pointer;
                    background-color: #6a7080;
                }
            }
        }

        &__list {
            height: 100px;
            margin-left: 2px;
            margin-right: 2px;
            margin-top: 2px;
            margin-bottom: 0;
            padding: 15px;
            background-color: #fff;

            .createModal_title {
                padding-bottom: 5px;
                width: 200px;
            }

            .createModal_input {
                /*border: 1px solid black;*/
            }

            .createModal_input-field {
                border: 1px solid gray;
                width: 100%;
                font-size: 14px;
                padding: 5px;
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
            width: calc(100%);
            height: 32px;
            margin: 1px;
            border: 2px solid #ababab;
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
