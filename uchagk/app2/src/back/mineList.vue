<!-- выпадающий список шахт
   разработал: Курбанов И. С.
   дата: 25.09.2019
-->
<template>
    <div class="listMineDropdown" @click.stop>
        <div class="dropDownList">
            <div class="dropDownList__header"><p>Выберите шахту</p></div>
            <div class="dropDownList__closeBtn" @click.stop="closeDropDownMines()">
                <div class="dropDownList__closeBtnLine"></div>
            </div>
            <div class="container-search-input">
                <input class="search-input" type="text" placeholder="Поиск шахты" v-model="searchInput">
                <button class="input-form-btns btn-reset-text" @click="searchInput=''"></button>
                <button class="input-form-btns btn-search"></button>
            </div>
            <!-- блок со всем списком людей -->
            <div class="dropDownList__list">
                <template v-for="mine in filteredMines">
                    <div class="mine-row"
                         @click="addMineInParent(mine)"
                         :key="mine.id">
                        {{mine.title}}
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "ListMine",
        props: {
            mineList: Object,
        },
        data() {
            return {
                searchInput: '',                 // список работников сгруппированных по департаментам
            }
        },
        computed: {
            /**
             * вычисляемое свойство списка должностей, которое возвращает список с учетом поискового запроса
             **/
            filteredMines() {
                let warnings = [];
                let filteredMines = [];
                try {
                    warnings.push("filteredMines. Начал выполнять метод");
                  warnings.push("filteredMines. начал построение списка ролей", this.mineList);

                    if (this.searchInput.length > 2) {                                                                    // проверяем строку поиска на наличие запроса
                        warnings.push("filteredMines. с поиском");
                        //объявляем пустой массив для формирования списка сотрудников с учетом поискового запроса
                        let mineObject = null;
                        for (let mineId in this.mineList) {                                                            //пробегаемся по списку сотрудников (объект)
                            mineObject = this.mineList[mineId];                                                  //объявляем переменную для хранения работника
                            if (mineObject.title.toLowerCase().indexOf(this.searchInput.toLowerCase()) > -1) { //ищем подстроку в строке, переводя всё в нижний регистр (-1 означает, что подстрока не была найдена в строке)
                                filteredMines.push(mineObject);                                                         //добавляем в конец массива объект работника
                            }
                        }
                        //возвращаем список сотрудников с учетом поиска
                    } else {
                        warnings.push("filteredMines. без поиска");
                        filteredMines = this.mineList;
                    }
                  warnings.push("filteredMines. закончил построение списка ролей", filteredMines);
                  warnings.push("filteredMines. Закончил выполнять метод");
                } catch (err) {
                    console.log("filteredMines. Исключение");
                    console.log(err);
                }
                //console.log(warnings);
                return filteredMines;                                                                                // иначе возвращаем исходный список
            },


        },
        methods: {

            addMineInParent(mineObj) {
                this.closeDropDownMines();
                this.$emit('setSelectedMineId', mineObj);
                // console.log('addMineInParent. Передача данных в setSelectedMineId');
            },

            /**Метод в момент вызова отправляет событие closeDropDownOrders
             * событие сообщает о том что выпадающий список необходимо закрыть*/
            closeDropDownMines() {
                this.searchInput = '';
                this.$emit('closeMineList');
                // console.log('closeDropDownMines. Закрыл список шахт');
            },

        },

        mounted() {
            
            $(".listMineDropdown").draggable({
                containment: '.max-content',
                handle: '.dropDownList__header',
                scroll: false
            });
            $(".listMineDropdown").resizable({
                minWidth: 300,
                maxWidth: 500,
                minHeight: 250,
                maxHeight: 500,
            });

        }
    }

</script>

<style lang="less" scoped>
    .listMineDropdown {
        position: absolute;
        z-index: 999;
        width: 400px;
    }
    .dropDownList {
        position: relative;
        font-size: 14px;
        text-align: left;
        background-color: #6A7080;
        padding: 3px;
        box-shadow: 5px 6px 4px 0px rgba(0, 0, 0, 0.35);

        &__list {
            overflow-y: scroll;
            overflow-x: hidden;
            height: 250px;
            margin: 2px;
            background-color: #fff;

            .mine-row {
                padding: 5px 5px 5px 15px;
                position: relative;
                display: flex;

                &:hover {
                    background-color: #ebecec;
                    cursor: pointer;
                }
            }
        }

        &__header {
            display: flex;
            align-items: center;
            padding-left: 5px;
            color: #fff;
            height: 30px;
            &:hover {
                cursor: move !important;
            }
            p {
                margin: 0;
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
            background: url('../../../prof-security/assets/searchIconWithGreyBorder.png') no-repeat center;
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
            background: url('../../../prof-security/assets/resetText.png') no-repeat center;
            background-size: 100%, 100%;
        }

        &__closeBtn {
            position: absolute;
            z-index: 1000;
            top: 15px;
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
