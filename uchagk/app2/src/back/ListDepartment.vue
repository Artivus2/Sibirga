<!--
  - Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
  -
  -->

<template>
  <div id="listDepartmentsDropdown" class="departmentDropdownList" @click.stop>
    <div class="dropDownList" style="z-index: 500;">
      <div class="dropDownList__header departmentListHeader"><p>Выберите подразделение</p></div>
      <div class="dropDownList__closeBtn" @click.stop="closeDropDownDepartments()">
        <div class="dropDownList__closeBtnLine"></div>
      </div>

      <div class="container-search-input">
        <input class="search-input" type="text" placeholder="Поиск департамента"
               v-model.lazy="searchGroupInput">
        <button class="input-form-btns btn-reset-text" @click="searchGroupInput=''"></button>
        <button class="input-form-btns btn-search"></button>
      </div>

      <!-- блок со всем списком людей -->
      <div id="listDepartmentsDropdownList"
           class="dropDownList__list"
      >
        <!-- блок со списком людей сгруппированных по департаментам -->
        <template>
          <template v-if="companyDepartments.id">
            <div class="companies-list">

              <template
                  v-for="company1 in filteredGroupsDepartments">
                <template
                    v-if="company1.is_chosen===2">
                  <div class="company-row" v-if="company1">
                    <div :key="company1.id" @click.stop="toggleDepartmentsVisibility($event)"
                         v-on:dblclick="addDepartmentsInParent(company1)"
                         class="company-title">
                      <span>{{ company1.title }}</span>
                    </div>
                    <div class="collapsed-items"
                         v-if="company1.children">

                      <template v-if="company1.children">
                        <div class="companies-list">
                          <template v-for="company2 in company1.children">
                            <template v-if="company2.is_chosen===2">
                              <div class="company-row" v-if="company2">
                                <div :key="company2.id"
                                     v-on:dblclick="addDepartmentsInParent(company2, company1.title)"
                                     @click.stop="toggleDepartmentsVisibility($event)"
                                     class="company-title">
                                  <span>{{ company2.title }}</span>
                                </div>

                                <div class="collapsed-items"
                                     v-if="company2.children">

                                  <template v-if="company2.children">
                                    <div class="companies-list">
                                      <template
                                          v-for="company3 in company2.children">
                                        <template
                                            v-if="company3.is_chosen===2">
                                          <div class="company-row"
                                               v-if="company3">
                                            <div :key="company3.id"
                                                 v-on:dblclick="addDepartmentsInParent(company3, company1.title + ' / ' + company2.title)"
                                                 @click.stop="toggleDepartmentsVisibility($event)"
                                                 class="company-title">
                                              <span>{{ company3.title }}</span>
                                            </div>
                                            <div class="collapsed-items"
                                                 v-if="company3.children">

                                              <template
                                                  v-if="company3.children">
                                                <div class="companies-list">
                                                  <template
                                                      v-for="company4 in company3.children">
                                                    <template
                                                        v-if="company4.is_chosen===2">
                                                      <div class="company-row"
                                                           v-if="company4">
                                                        <div :key="company4.id"
                                                             v-on:dblclick="addDepartmentsInParent(company4, company1.title + ' / ' + company2.title + ' / ' + company3.title)"
                                                             @click.stop="toggleDepartmentsVisibility($event)"
                                                             class="company-title">
                                                          <span>{{ company4.title }}</span>
                                                        </div>
                                                        <div class="collapsed-items"
                                                             v-if="company4.children">

                                                          <template
                                                              v-if="company4.children">
                                                            <div class="companies-list">
                                                              <template
                                                                  v-for="company5 in company4.children">
                                                                <template
                                                                    v-if="company5.is_chosen===2">
                                                                  <div class="company-row"
                                                                       v-if="company5">
                                                                    <div :key="company5.id"
                                                                         v-on:dblclick="addDepartmentsInParent(company5, company1.title + ' / ' + company2.title + ' / ' + company3.title + ' / ' + company4.title)"
                                                                         @click.stop="toggleDepartmentsVisibility($event)"
                                                                         class="company-title">
                                                                      <span>{{ company5.title }}</span>
                                                                    </div>
                                                                    <div class="collapsed-items"
                                                                         v-if="company5.children">


                                                                      <template
                                                                          v-if="company5.children">
                                                                        <div class="companies-list">

                                                                          <template
                                                                              v-for="company6 in company5.children">
                                                                            <div class="company-row"
                                                                                 v-if="company6">
                                                                              <div :key="company6.id"
                                                                                   v-on:dblclick="addDepartmentsInParent(company6, company1.title + ' / ' + company2.title + ' / ' + company3.title + ' / ' + company4.title + ' / ' + company5.title)"
                                                                                   @click.stop="toggleDepartmentsVisibility($event)"
                                                                                   class="company-title">
                                                                                <span>{{ company6.title }}</span>
                                                                              </div>
                                                                              <div class="collapsed-items"
                                                                                   v-if="company6.children">


                                                                                <template
                                                                                    v-if="company6.children">
                                                                                  <div class="companies-list">

                                                                                    <template
                                                                                        v-for="company7 in company6.children">
                                                                                      <template
                                                                                          v-if="company7.is_chosen===2">
                                                                                        <div class="company-row"
                                                                                             v-if="company7">
                                                                                          <div :key="company7.id"
                                                                                               v-on:dblclick="addDepartmentsInParent(company7, company1.title + ' / ' + company2.title + ' / ' + company3.title + ' / ' + company4.title + ' / ' + company5.title + ' / ' + company6.title)"
                                                                                               @click.stop="toggleDepartmentsVisibility($event)"
                                                                                               class="company-title">
                                                                                            <span>{{
                                                                                                company7.title
                                                                                              }}</span>
                                                                                          </div>
                                                                                          <div class="collapsed-items"
                                                                                               v-if="company7.children">

                                                                                            <template
                                                                                                v-if="company7.children">
                                                                                              <div
                                                                                                  class="companies-list">

                                                                                                <template
                                                                                                    v-for="company8 in company7.children">
                                                                                                  <template
                                                                                                      v-if="company8.is_chosen===2">
                                                                                                    <div
                                                                                                        class="company-row"
                                                                                                        v-if="company8">
                                                                                                      <div
                                                                                                          :key="company8.id"
                                                                                                          v-on:dblclick="addDepartmentsInParent(company8, company1.title + ' / ' + company2.title + ' / ' + company3.title + ' / ' + company4.title + ' / ' + company5.title + ' / ' + company6.title + ' / ' + company7.title)"
                                                                                                          @click.stop="toggleDepartmentsVisibility($event)"
                                                                                                          class="company-title">
                                                                                                        <span>{{
                                                                                                            company8.title
                                                                                                          }}</span>
                                                                                                      </div>
                                                                                                    </div>
                                                                                                  </template>
                                                                                                </template>

                                                                                              </div>
                                                                                            </template>

                                                                                          </div>
                                                                                        </div>
                                                                                      </template>
                                                                                    </template>

                                                                                  </div>
                                                                                </template>

                                                                              </div>
                                                                            </div>
                                                                          </template>

                                                                        </div>
                                                                      </template>

                                                                    </div>
                                                                  </div>
                                                                </template>
                                                              </template>
                                                            </div>
                                                          </template>

                                                        </div>
                                                      </div>
                                                    </template>
                                                  </template>

                                                </div>
                                              </template>

                                            </div>
                                          </div>
                                        </template>
                                      </template>

                                    </div>
                                  </template>

                                </div>

                              </div>
                            </template>
                          </template>

                        </div>
                      </template>

                    </div>
                  </div>
                </template>
              </template>

            </div>
          </template>

        </template>
      </div>

    </div>
  </div>
</template>

<script>
import SSStore from '@/modules/order-system/components/shift-schedule/shift-schedule.js';

export default {
  name: "ListDepartment",
  props: {},
  data() {
    return {
      searchGroupInput: '',
      /**
       * тестовые переменные Исмата
       * */
      // mySearchInput: '',
      // filteredDepartments: []
    }
  },
  computed: {
    /**
     * вычисляемое свойство - получение списка департаметов рекурсивным образом из БД - повыщение скорости обработки и получения данных
     */
    companyDepartments() {
      return this.$store.getters.GETALLCOMPDEPAR;
    },
    // Установить/получить текущую активную вкладку
    activeModal2: {
      get() {
        return SSStore.getters.ACTIVEMODAL2;
      },
      set(value) {
        SSStore.dispatch("setActiveModal2", value);
      }
    },
    activeModal: {
      get() {
        return SSStore.getters.ACTIVEMODAL;
      },
      set(value) {
        SSStore.dispatch("setActiveModal", value);
      }
    },
    /**
     * вычисляемое свойство группы списка сотрудников, которое возвращает список с учетом поискового запроса
     **/
    filteredGroupsDepartments() {
      let warnings = [];
      let response = {};
      let responseTemp = {};
      try {
        warnings.push("filteredGroupsDepartments. Начал выполнять метод");
        if (Object.keys(this.companyDepartments.children).length) {
          if (this.searchGroupInput.length > 2) {                                                                    // проверяем строку поиска на наличие запроса
            for (let companyIDX in this.companyDepartments.children) {
              let company = this.companyDepartments.children[companyIDX];
              //warnings.push("filteredGroupsDepartments. Первый уровень компаний ", company);
              responseTemp = this.searchDepartments(company, 0);
              if (Object.keys(responseTemp).length) {
                //warnings.push("filteredGroupsDepartments. есть вложения, поиск удался ", responseTemp);
                this.$set(response, companyIDX, responseTemp);
              }
            }
          } else {
            response = this.companyDepartments.children;                                                                                // иначе возвращаем исходный список
          }
        }
        warnings.push("filteredGroupsDepartments. Закончил выполнять метод");
      } catch (err) {
        console.log("filteredGroupsDepartments. Исключение");
        console.log(err);
      }
      // console.log(warnings);
      return response;
    },

    //выбранный департамент
    currentDepartment: {
      get() {
        return this.$store.getters.getCurrentDepartment;
      },
      set(value) {
        this.$store.dispatch("setCurrentDepartment", value);
      }
    },

    /* myFilteredDepartments: {
         get() {
             return this.filteredDepartments;
         },
         set(newData) {
             this.filteredDepartments = newData;
         }
     }*/
  },
  methods: {
    /** метод поиска работниках во вложенных департаментах
     * структуру собираем обратным ходом - идем до самого низа, и потом обратным ходом собираем департаменты
     **/
    searchDepartments(companys, i) {
      i++;
      let warnings = [];
      let response = {};
      let responseTemp = {};
      let filteredWorkersAndDepartments = {};                                                                               //объявляем пустой массив для формирования списка сотрудников с учетом поискового запроса
      try {
        warnings.push("searchDepartments. Начал выполнять метод для компании", companys);
        if (i < 15) {
          if (companys.children) {
            warnings.push("searchDepartments. Есть вложения внутри компании");
            for (let companyIDX in companys.children) {
              let company = companys.children[companyIDX];
              responseTemp = this.searchDepartments(company, i);
              if (Object.keys(responseTemp).length) {
                this.$set(filteredWorkersAndDepartments, companyIDX, responseTemp);
              }
            }
            if (Object.keys(filteredWorkersAndDepartments).length) {
              //warnings.push("searchDepartments. Найдены люди по совпадению кладу в ответ");
              response = {
                id: companys.id,
                title: companys.title,
                is_chosen: companys.is_chosen,
                children: filteredWorkersAndDepartments,
              };
            }
          }

          if (!Object.keys(filteredWorkersAndDepartments).length && companys.title.toLowerCase().indexOf(this.searchGroupInput.toLowerCase()) > -1) { //ищем подстроку в строке, переводя всё в нижний регистр (-1 означает, что подстрока не была найдена в строке)
            warnings.push("searchDepartments. Найдено совпадение внутри самой компании, готово к передаче на верх");
            response = {
              id: companys.id,
              title: companys.title,
              is_chosen: companys.is_chosen,
            };
          }

        } else {
          warnings.push("searchDepartments. Лимит вложений превышен");
        }

        //warnings.push("searchDepartments. Закончил выполнять метод");
      } catch (err) {
        console.log("searchDepartments. Исключение");
        console.log(err);
      }
      //console.log(warnings);
      return response;
    },
    /**
     * методы Исмата для поиска по участкам
     **/
    /*recursiveDepartmentSearch(companyObject) {
        // console.log('ListDepartment.vue, recursiveDepartmentSearch. companyObject. title',companyObject.title);
        let flag = false;
        if (companyObject.title.toLowerCase().indexOf(this.mySearchInput.toLowerCase()) > -1) {
            flag = true;
        }
        if (companyObject.children) {
            companyObject.children = companyObject.children.filter(companyItem => this.recursiveDepartmentSearch(companyItem));
            // console.log('ListDepartment.vue, recursiveDepartmentSearch. companyObject.children ',companyObject.children);
        }
        return !!(flag || (companyObject.children && companyObject.children.length));


    },

    searchDepartment(searchValue) {
        console.time('searchDepartment');
        let tempList = [], localCopyList = JSON.parse(JSON.stringify(this.companyDepartments));
        if (searchValue.length > 2) {
            this.mySearchInput = searchValue;
            if (localCopyList.children) {
                for (let i = 0; i < localCopyList.children.length; i++) {
                    // console.log('ListDepartment.vue, searchDepartment. localCopyList.children[i].title ',localCopyList.children[i].title);
                    if (localCopyList.children[i].children) {
                        // console.log('ListDepartment.vue, searchDepartment есть дети',localCopyList.children[i].children);
                        let tempCompany = localCopyList.children[i].children.filter(companyItem => this.recursiveDepartmentSearch(companyItem));
                        // console.log('ListDepartment.vue, searchDepartment. tempCompany',tempCompany);
                        if (tempCompany.length) {
                            tempList.push({
                                id: localCopyList.children[i].id,
                                title: localCopyList.children[i].title,
                                is_chosen: localCopyList.children[i].is_chosen,
                                children: tempCompany
                            });
                        }
                    }
                    else {
                        // console.log('ListDepartment.vue, searchDepartment. нет детей', localCopyList.children[i].children);
                        if (localCopyList.children[i].title.toLowerCase().indexOf(searchValue.toLowerCase()) > -1) {
                            // console.log('ListDepartment.vue, searchDepartment. соответствует поисковому запросу',);
                            tempList.push(localCopyList.children[i]);
                        }
                        else {
                            // console.log('ListDepartment.vue, searchDepartment. не соответствует поисковому запросу',);
                        }
                    }


                }
                // console.log('ListDepartment.vue, searchDepartment. tempList', tempList);
                this.filteredDepartments = tempList;
                //     {
                //     id: localCopyList.id,
                //     title: localCopyList.title,
                //     is_chosen: localCopyList.is_chosen,
                //     state: localCopyList.state,
                //     children: tempList
                // }
                // {
                //     if (companyItem.company_title.toLowerCase().indexOf(searchValue.toLowerCase()) > -1) {
                //         return true;
                //     }
                //     if (companyItem.children) {
                //
                //     }
                // }
                // );
            }
        } else {
            this.filteredDepartments = this.companyDepartments.children;
        }
        console.timeEnd('searchDepartment');
    },
    clearSearch(inputNode) {
        inputNode.value = '';
        this.mySearchInput = '';
        this.filteredDepartments = this.companyDepartments.children;
    },*/
    /**
     * Функция скрытия/раскрытия списка подразделений
     * @param event (object) - объект события клика левой кнопкой мыши
     */
    toggleDepartmentsVisibility(event) {
      if (event.target.classList.contains('company-title')) {
        $(event.target.nextElementSibling).slideToggle('fast');
      } else {
        $(event.target.parentNode.nextElementSibling).slideToggle('fast');
      }
    },

    addDepartmentsInParent(companyDepartment, parentCompaniesTitle) {
      this.closeDropDownDepartments();
      let department = {
        id: companyDepartment.id,
        title: companyDepartment.title,
        brigade_list: {},
        full_path: parentCompaniesTitle ? parentCompaniesTitle + ' / ' + companyDepartment.title : companyDepartment.title
      }
      //this.currentDepartment=department;
      this.$emit('setDepartmentInFilter', department);
      // console.log('addDepartmentsInParent. Выбранный дапартамент ', department);
    },

    /**Метод в момент вызова отправляет событие closeDropDownOrders
     * событие сообщает о том что выпадающий список необходимо закрыть*/
    closeDropDownDepartments() {
      this.searchGroupInput = '';
      this.activeModal2 = '';
      this.activeModal = '';
      // console.log('closeDropDownDepartments. Закрыл список людей модалка 2 уровня');
      this.$emit('closeListDepartment');
      this.$emit('closeModals');
    },

  },
  // watch:  {
  //     companyDepartments(newValue) {
  //         this.filteredDepartments = newValue.children ? newValue.children : [];
  //     }
  //
  // },
  mounted() {
    $(".departmentDropdownList").draggable({
      containment: 'window',
      handle: '.departmentListHeader',
      scroll: false
    });
    $(".departmentDropdownList").resizable({
      minWidth: 300,
      maxWidth: 500,
      minHeight: 250,
      maxHeight: 450,
      alsoResize: "#listDepartmentsDropdownList"
    });
  }
}

</script>

<style lang="less" scoped>

.groupButtonCheckGroupBy {
  text-align: left;
  padding: 0;
  margin: 0;
}

.buttonCheckGroupBy {
  font-size: 10px;
  text-align: center;
  width: 305px;
  background-color: #6A7080;
  padding: 2px;
  vertical-align: middle;

  &__wihout {
    display: inline-block;
    width: 150px;
    height: 35px;
    background-color: #605c5d;
    color: #fff;
    border: 1px solid #0c5460;
    cursor: pointer;
    vertical-align: middle;
  }

  &__wihdepartment {
    display: inline-block;
    width: 150px;
    height: 35px;
    background-color: #605c5d;
    color: #fff;
    border: 1px solid #0c5460;
    cursor: pointer;
    vertical-align: middle;
  }
}



.company-title {
  background-color: #0003;
}

.company-title, .department-title {
  padding: 5px;
  margin: 0;
  border-bottom: 1px solid #fff;
  cursor: pointer;
  width: 100%;
}

.collapsed-items {
  padding-left: 20px;
  display: none;
}

.department-title:hover {
  background-color: #eee;
}

.company-title:hover {
  background-color: #00000015;
}
</style>
