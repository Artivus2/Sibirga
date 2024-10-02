'use strict';
import { Geometry, Layer, Layers, baseUrl, uri } from './modules/helpers.js';

var htmlReaderEdit = '';
var L_CANVAS_PREFER = true;

//var globalOptions = {};

/**
 * Vue
 * */
var app = new Vue({
    //el: '#vue-leaflet-bar',
    el: '#vue',
    data: {
        /**Меню
         */
        menu: {
            /**Пункт управления картой
             */
            mapManagement: false,

            /**Видимость панели сообщений от оборудования
             */
            hardwareEvents: false,

            /**Конфигурация
             */
            layerSettings: globalOptions.features == 'vectorCreate' ? true : false,

            /**Ламповая
             */
            lampRoom: false,

            /**Статистика
             */
            info: false,

            /**
             * Меняет отображение содержимого пунктов меню
             * @param {string} itemName Название пункта
             */
            changeItem: function (itemName) {
                if (typeof this[itemName] === "boolean") {
                    this[itemName] = !this[itemName];
                }
                //Остальным присвоить false
                for (let i in this) {
                    if (i != itemName && typeof this[i] === "boolean") {
                        this[i] = false;
                    }
                }
            }
        },

        /**Параметры хаба сообщений
         */
        messageHub: {
            bufferSize: 100,
            filter: ''
        },

        /**Текущий список локаторов
         */
        currentLocators: [],
        /**Форма редактирования
         */
        readerEditContent: htmlReaderEdit,
        /**Id считывателя при открытии карты
         */
        startReaderId: globalOptions.readerId,

        /** Слой редактирования
         */
        editableLayer: null,

        /**Инструмент редактирования*/
        editTool: {
            /**Объект для панели редактирования
             */
            toolbar: null,
            /**Ручка для инструмента редактирования панели редактирования
             */
            editHandler: null,
        },

        map: null,
        name: '0',
        zoom: null,
        check: false,
        point: "[0, 0]",
        /**Текущий слой редактирования
         */
        currentLayer: {
            name: 'nothing',
            id: -1,
            state: false
        },
        mapLayers: null,

        /**
         * 
         */
        currentZoom: null,

        /** Режим выделения
         */
        selectedMode: globalOptions.features == 'vectorCreate' ? true : false,

        /**Ошибки при создании вектора
         */
        vectorError: null,
        /** Графические слои Layers
         */
        layers: null,
        layersControl: null,

        /** Новые векторы
         */
        newVectors: [],
        /** Новые считыватели
         */
        newReaders: [],

        /**Атрибуты для нового вектора
         */
        formVector: {
            name: null,

            /**Длина вектора
             */
            len: null,

            /**Создавать вектор в обратном направлении, если true
             */
            reverse: false
        },

        /**Атрибуты для считывателя
         */
        formReader: {
            num: null,
            slot: null
        },

        /**Границы уровней L.Bounds
          */
        levelBounds: null,

        /**
         * Ссылка на хаб
         */
        hubUrl: baseUrl + 'hardware',

        /**Соединение с hub
         */
        hubConnection: null,
        /**Данные для отрисовки сообщения {message: null, type: ['alert', 'alert-success']}
         * typeMess - классы bootstrap
         */
        hubData: [],

        /**Запуск или останов оборудования
         */
        hardwareOnOff: {
            disabled: false,
            on: false,
            /**Задержка после нажатия старт/стоп
             */
            timeDelay: 0
        },

        /**Статистика
         */
        statistics: {
            /**
             * В ламповой/вне
             */
            isLsel: false,

            staffList: [],

            /**Персонал в ламповой
            */
            filter: "",

            /**Список векторов
             */
            vectors: [],

            /**Фильтр по вектору
             */
            currentVectorId: null,

            /**
             *  Весь персонал
             */
            allStaffs: [],

            /**
             * Люди в ламповой
             */
            staffInLsel: [],

            /**
             * Список людей в шахте
             */
            staffInMine: [],
            old: 0,
            all: 0,

            /**
             * Update statistics.allStaffs list
             */
            updateStaffs: function () {
                app.allStaffsUpdate();
            }
        },

        /**
         * История позиционирования
         */
        history: {
            /**
             * История позиционирования StaffHistory
             */
            staffHistories: [],

            /**
             * Строка для фильтрации списка персонал
             */
            staffFilter: "",

            /**
             * Персонал
             */
            staff: [],


            /**
             * Фильтры для запроса staffHistories
             */
            // changes: {
            /**
             * Выбранные люди
             */
            changesStaff: [],
            after: null,
            before: null,

            /**
             * Проверка полей фильтра - если верно true
             * @returns Возвращает список ошибок
             */
            isPassed() {
                let errors = [];
                let a = new Date(this.after);
                let b = new Date(this.before);

                if (b - a <= 0) {
                    errors.push("Неправильно задан интервал");
                }

                if (this.checkedStaff.length < 1) {
                    errors.push("Не выбраны люди");
                }

                return errors;
            },
            // },

            /**
             * Заполнить список staffs из api/staffs
             */
            requestStaff() {
                let url = baseUrl + "api/staffs";

                axios
                    .get(url)
                    .then(response => {
                        this.staff = response.data;
                    })
                    .catch(error => {
                        console.log("Error: request to api/staffs");
                    });
            },

            /**
             * Отправляет запрос к api/staffhistories/filter?staffs=&after=&before=
             */
            requestStaffHistories() {
                let url =
                    baseUrl + "api/staffhistories/filter?" +
                    this.checkedStaff
                        .map(el => `staffs=${el.fullName}`)
                        .join('&');

                url += `&after=${this.after}&before=${this.before}`;
                axios
                    .get(url)
                    .then(response => {
                        this.staffHistories = response.data;
                    })
                    .catch(error => {
                        console.log('Error: get data from api/staffhistories/filter');
                    });
            },

            /**
             * Проигрывает список истории местоположения на карте
             */
            play() {
                //Очистить слой История
                app.layers.history.clearLayers();
                let lsel = app.layers.readers
                    //.getLayers()
                    .filter(l => l.feature.properties.ip != null)[0]
                    .toGeoJSON();

                for (let el of this.staffHistories) {
                    //В ламповой
                    if (!el.vectorId && el.lselIp != null) {
                        lsel.properties = el;
                        app.layers.history
                            .addData(lsel);
                        continue;
                    }
                    //На векторе
                    let vec =
                        app.layers
                            .vectors
                            .filter(l => l.feature.properties.id == el.vectorId)[0];

                    if (vec) {
                        let geoJson =
                            app.interpolate(el.vectorId, el.procentPosition)
                                .toGeoJSON();

                        geoJson.properties = el;

                        app.layers.history
                            .addData(geoJson);
                    }
                }
            },

            /**
             * Персонал для списка выбора
             */
            filteredStaff() {
                if (this.staffFilter.length < 1) {
                    return;
                }
                if (this.staff.length < 1) {
                    this.requestStaff();
                }

                let filter = this.staffFilter.toLowerCase();
                let staff =
                    this.staff
                        .filter(el => {
                            if (filter === '') {
                                return true;
                            } else {
                                let ret = el.fullName.toLowerCase().indexOf(filter) > -1;
                                return ret;
                            }
                        });
                return staff;
            },

            /**
             * Очистить выбор
             */
            clearChange() {
                this.staff
                    .filter(el => el.checked == true)
                    .forEach(el => {
                        el.checked = false;
                    });
            },

            /**
             * Возвращает отмеченных пользователей
             */
            get checkedStaff() {
                let checked =
                    this.staff
                        .filter(el => el.checked == true);
                // this.changesStaff =
                //     checked.map(el => el.fullName);
                return checked;
            },
            /**
             * Удаляет из списка отмеченных по id
             */
            set checkedStaff(id) {
                if (id > 0) {
                    let user =
                        this.staff
                            .filter(el => el.id == id);

                    if (user[0] != undefined) {
                        user[0].checked = false;
                    }
                }
            },

        },

        /**Аварии
         */
        alarm: {
            /**Режим аварии
             */
            mode: false,
            status: -1
        }

    },
    mounted() {
        //Инициализация карты
        //Создание и загрузка слоёв
        this.initMap(this.initLayers);
        //Обработчики событий
        this.initEvents();

        //this.mapLayers = Object.entries(this.layers).filter(el => ['levels', 'vectors', 'readers'].indexOf(el[0]) > -1);
        //this.mapLayers = Object.entries(this.layers);

        //Контрол Слои
        let back = {
            '<span style="">Схема</span>': this.layers.background.lines,
            "Анотации": this.layers.background.anno,
            '<span class="glyphicon glyphicon-th-large"></span> Зоны': this.layers.levels,
            '<span class="glyphicon glyphicon-eye-open"></span> Считыватели': this.layers.readers,
            '<span class="glyphicon glyphicon-resize-small"></span> Векторы': this.layers.vectors,
            '<span class="glyphicon glyphicon-map-marker"></span> Персонал': this.layers.locators,
            '<span class="glyphicon glyphicon-dashboard"></span> История': this.layers.history,
            '<span class="glyphicon glyphicon-fire"></span> Газоанализ': this.layers.chemical,
        };
        this.layersControl = L.control.layers(null, back, {
            collapsed: true,
            position: 'topright',
            autoZIndex: true
        }).addTo(this.map);

        //Создать слой редактирования
        this.editableLayer = new Layer([], { draggable: true });
        this.editableLayer.bindTooltip(function (layer) {
            let content = `<h4>${layer.feature.properties.comment}</h4>`;
            return content;
        }, {
            className: 'horizon-map-tooltip',
            opacity: 0.7
        });

        //Инициализация невизуальной панели редактирования
        this.editTool.toolbar = new L.EditToolbar({
            featureGroup: this.editableLayer,
        });

        //Ручка на инструмент редактирования
        this.editTool.editHandler = this.editTool.toolbar.getModeHandlers()[0].handler;

        //Необходимо для правильного отлавливания событий редактирования на карте
        this.editTool.editHandler._map = this.map;

        //Кластер считывателей
        //TODO:не работает доделать!
        //this.cluster = L.markerClusterGroup();
        //this.cluster.addTo(this.layers.readers);

        this.currentZoom = this.map.getZoom();

        //Иницилизация хаба сообщений от оборудования
        //this.initHub();
    },

    //После создания компонента
    created: function () {

    },
    watch: {
        /** Возникает каждый раз при изменении переменной this.selectedMode
         */
        selectedMode: function (val) {
            //if (val === true) {
            //    //TODO: удалить все popup из readers
            //    this.layers.readers.eachLayer(l => l.unbindPopup());
            //    this.layers.readers.options.onEachFeature = null;
            //} else {
            //    this.layers.readers.eachLayer(l => l.bindPopup('TEST'));
            //    this.layers.readers.options.onEachFeature = onEachFeature_;
            //}
        },
        /** Текущий зум
          */
        currentZoom: function () {
            var z = this.map.getZoom();
            if (this.currentZoom != z) {
                this.map.setZoom(z);
            }
        },
    },
    computed: {
        /**Отфильтрованный список staffInLsel
         * */
        filteredStaffs: function () {
            let f = this.statistics.filter;
            let vid = this.statistics.currentVectorId;
            let ret = this.statistics.staffList.filter(el => {
                if (f === '') {
                    return true;
                } else {
                    let ret = el.staffFullName.indexOf(f) > -1;
                    el.vectorId == vid;
                    return ret;
                }
            });

            return ret;
        },
        readerPopupContent: function () {
            let content = '<p><b>Инфо</b></p>';

            if (typeof feature.properties.isSelected != "undefined") {
                content += `<p>Выделено: ${feature.properties.isSelected}</p>`
            }

            content += '<p><input type="text" /></p>';
            content += '<a href="#">Сохранить</a>'
            return content;
        },

        /**Возвращает отфильтрованные сообщения
         */
        hubDataMessages: {
            get: function () {
                let f = this.messageHub.filter;

                //Фильтр
                let ret = this.hubData.filter(el => {
                    if (f === '') {
                        return true;
                    } else {
                        return el.message.indexOf(f) > -1;
                    }
                });

                return ret;
            },
            set: function (newValue) {
                if (this.hubData.length > this.messageHub.bufferSize) {
                    this.hubData.shift();
                }
                this.hubData.push(newValue);
            }
        },

        /**Запускает и останавливает оборудование
         */
        hardwareStartStop: {
            get: function () {
                return this.hardwareOnOff.on;
            },
            set: function (value) {
                if (this.hubConnection && this.hubConnection.state) {
                    this.hardwareOnOff.on = value;
                    //Сделать кнопку запуску неактивной
                    this.hardwareOnOff.disabled = true;
                    this.disabledDelay = 10;
                    //Вернуть обратно через 30 секунд
                    setTimeout(() => this.hardwareOnOff.disabled = false, 10000);

                    //Послать SignalR запустить оборудование
                    this.hubConnection.invoke('OnOff', this.hardwareOnOff.on);
                }
            }
        },

        /**
         * Возврашает текущий список пользователей
         */
        switchStaffList: {
            get: function () {
                return this.statistics.isLsel;
            },
            set: function (value) {
                if (this.statistics.isLsel) {
                    this.statistics.staffList = this.statistics.staffInMine;
                    this.statistics.isLsel = false;
                } else {
                    this.statistics.staffList = this.statistics.staffInLsel;
                    this.statistics.isLsel = true;
                }
            }
        },

        /**Выводит стиль кнопки в зависимости от параметров
         */
        startStopButtonStyle: {
            get: function () {
                let style = 'label-success';
                if (!this.hardwareOnOff.on) {
                    style = 'label-danger';
                }
                if (this.hardwareOnOff.disabled) {
                    style = 'label-default';
                }
                return style;
            }
        },

        /**Запустить счётчик обратного осчёта на кнопке для разблокировки
         */
        disabledDelay: {
            get: function () {
                return this.hardwareOnOff.timeDelay;
            },
            set: function (value) {

                if (value < 0) return;
                this.hardwareOnOff.timeDelay = value;

                function timer() {
                    app.hardwareOnOff.timeDelay--;
                    if (app.hardwareOnOff.timeDelay == 0) {
                        setTimeout(function () { }, 1000);
                    } else {
                        setTimeout(timer, 1000);
                    }
                }
                setTimeout(timer, 1000);
            }
        }

    },
    /** Методы
     */
    methods: {

        /**Выравнивает зоны по высоте
         * внизу самые низкие Heigth
         * */
        sortLevels() {
            //Выровнять зоны по высоте
            let sortLevels = this.layers.levels
                .getLayers()
                .sort((a, b) => a.feature.properties.heigth - b.feature.properties.heigth);
            //Толкнуть зоны
            for (let l of sortLevels) {
                l.bringToFront();
            }
        },

        /**
         * Для гарантированного выполнения только после заполнения слоёв
         * @param {Set} filledLayers заполненные слои
         */
        afterLoadingLayers(filledLayers) {
            let sa = Array.from(filledLayers);

            //Проверить загрузились ли слои
            let loading = ['vectors', 'readers', 'levels'].every(l => sa.includes(l));

            if (loading) {
                //Если слои заполены, то запустить инициализацию хаба и обработчики событий от хаба
                this.initHub();

                ////Выровнять зоны
                //this.sortLevels();
            }
        },

        /**Создаёт и настраивает карту
         * @param {Function} callback Функция которая выполняется в конце callback()
         * */
        initMap(callback) {
            /** Создание карты
             */
            this.map = L.map('map', {
                editable: true,
                editOptions: {
                    //markerClass: L.CircleMarker
                },
                crs: L.CRS.Simple,
                minZoom: -6,
                maxZoom: 6
            }).setView([6000, 3000], 2);

            //Чтобы можно было прозрачно нажимать на накладывающиеся полигоны нужна панель для уровней
            this.map.createPane('levels');
            this.map.createPane('vectors');
            this.map.createPane('readers');

            let levelsPane = this.map.getPane('levels');
            let vectorsPane = this.map.getPane('vectors');
            let readersPane = this.map.getPane('readers');


            levelsPane.style.zIndex = 0;
            levelsPane.style.pointerEvents = 'none';

            vectorsPane.style.zIndex = 100;
            vectorsPane.style.pointerEvents = 'none';

            readersPane.style.zIndex = 200;
            readersPane.style.pointerEvents = 'none';

            //Вызов функции
            typeof callback === 'function' && callback();

            return this.map;
        },
        /**Создаёт и заполняет слои на карте
         * */
        initLayers() {
            //Объект для всех слоёв Layers,
            //после заполнения слоёв выполняется инициализация хаба
            this.layers = new Layers();

            this.layers.fill(uri, this.afterLoadingLayers);

            //Добавить слои на карту
            if (globalOptions.layers.length > 0) {
                this.layers.addTo(this.map, globalOptions.layers);
            } else {
                this.layers.addTo(this.map);
            }

            //Отключить слой уровни
            //Object.entries(app.layers).filter(l => l[0] === 'levels')[0][1].removeFrom(app.map);

            //Список слоёв которые можно редактировать
            //возращает [{name: 'name', id: '21'},...]
            this.mapLayers = Object.entries(this.layers)
                .filter(f => ['readers', 'vectors', 'levels'].indexOf(f[0]) > -1)
                .map(l => {
                    return {
                        name: l[1].name,
                        id: l[1]._leaflet_id,
                        state: false
                    }
                });
        },

        /**Подписка на события
         * */
        initEvents() {

            ////Перемещает слой levels на задний план
            //this.map.on("overlayadd", function (event) {
            //    app.layers.levels.bringToBack();
            //});

            /** Обработчик события 'click' на считывателе
             */
            this.layers.readers.on('click', function (event) {
                let ll = event.latlng;
                let flag;

                app.layers.readers.getLayers()
                    .filter(l => l.getLatLng().lat == ll.lat && l.getLatLng().lng == ll.lng)
                    .forEach(l => {
                        if (app.selectedMode) {
                            l.feature.properties.isSelected = !l.feature.properties.isSelected;
                            flag = l.feature.properties.isSelected;
                            //Изменить цвет кружка в зависимости от флага
                            l.setStyle({ fillColor: flag ? 'red' : 'green' });

                            //TODO: открыть окно если выделено 2 кружка
                            //if (this.layers.readers.counterSelected() == 2 && flag) {
                            //    var pop = new L.popup()
                            //        .setContent('<input type="text"/><br/><a href="#" v-on:click="this.map.closePopup(pop)">Создать вектор</a>');
                            //    this.map.openPopup(pop, l.getLatLng());
                            //}
                        }
                    });
                console.log({ ll: event.latlng, ev: event, isSelected: flag });
            });

            /** Обработчик события рисования объектов
             */
            this.map.on(L.Draw.Event.CREATED, function (e) {
                let type = e.layerType,
                    layer = e.layer;

                //Если тип объекта маркер
                if (type === 'marker') {
                    geo = layer.toGeoJSON();
                    geo.properties = {
                        isSelected: false,
                        state: 'created',
                        num: null

                    };

                    app.layers.readers.addData(geo);

                }
            });

            this.map.on('zoom', (event) => {
                let z = this.map.getZoom();
                app.currentZoom = z;
            });

            /**Обработчик события this.editTool.editHandler.save
             * Сохраняет изменённые слои в БД
             */
            this.map.on('draw:edited', (e) => {
                //Входные слои
                let editedLayers = e.layers.getLayers();

                let tempLayers = {};//Временные слои для отправки измененией на сервер

                //Для считывателей изменяем также и векторы, которые связаны с этими считывателями
                if (editedLayers.some(l => l.layerName == "readers")) {
                    //Получить id считывателей
                    let readersIds = editedLayers.map(l => l.feature.properties.id);

                    tempLayers["vectors"] = new Layer();

                    let vectors = app.layers.vectors.filter(v => readersIds.some(s => s == v.feature.properties.readerBeginId) ||
                        readersIds.some(s => s == v.feature.properties.readerEndId));

                    vectors.forEach(v => tempLayers["vectors"]
                        .addData(v.toGeoJSON()));
                }

                //Если в слое вектор изменились начальные координаты то исправить и
                for (let l of editedLayers) {
                    if (tempLayers[l.layerName] === undefined) {
                        tempLayers[l.layerName] = new Layer();
                    }
                    tempLayers[l.layerName].addData(l.toGeoJSON());
                }


                //Отправить все отредактированные слои на сервер
                for (let lay in tempLayers) {
                    tempLayers[lay].put(uri + lay, l => {
                        app.layers.refresh(lay);//Обновить слой на карте
                    });
                }

            });

            /**Синхронизировать конечные координаты векторов с координатами изменяемых считывателей
             */
            this.map.on('draw:editmove', (e) => {
                if (e.layer.layerName === "readers") {
                    this.syncVectors(e.layer);
                }
            });

            //this.map.on('contextmenu', (event) => {
            //    console.log(event.containerPoint);
            //});

            //Обработчик события правого клика на считывателе
            //Редактор атрибутов
            this.layers.readers.on('contextmenu', (e) => {
                let marker = e.target;
                let feature = e.layer.feature;
                let form = {};
                console.log(e);

                //Активировать режим редактирования
                //e.layer.options.editing || (layer.options.editing = {});
                //e.layer.editing.enable()

                //Открывать меню редактирования только если считыватель создан в программе
                if (feature.properties.state === 'created') {
                    this.map.openPopup(this.readerEditContent, e.layer.getLatLng());

                    let error = L.DomUtil.get('error-message');

                    //Номер
                    let inputNum = L.DomUtil.get('input-num');
                    inputNum.value = feature.properties.num;

                    //Слот
                    let inputSlot = L.DomUtil.get('input-slot');
                    inputSlot.value = feature.properties.slot;

                    //Имя вектора
                    let inputComment = L.DomUtil.get('input-comment');
                    inputComment.value = feature.properties.comment;

                    //Подписка на событие изменения значения num
                    L.DomEvent.addListener(inputNum, 'change', function (e) {
                        form.num = e.target.value;
                        inputComment.value = form.comment = 'R' + form.num;
                    });

                    //Слот
                    L.DomEvent.addListener(inputSlot, 'change', function (e) {
                        form.slot = e.target.value;
                    });

                    //Comment
                    L.DomEvent.addListener(inputComment, 'change', (e) => {
                        form.comment = e.target.value;
                    });


                    let buttonSubmit = L.DomUtil.get('button-submit');

                    //Сохранить и закрыть Окно
                    L.DomEvent.addListener(buttonSubmit, 'click', function (e) {
                        let num = parseInt(form.num, 10)

                        feature.properties.comment = form.comment;

                        if (!app.verifyReaderNum(num)) {

                            feature.properties.num = num;
                            feature.properties.slot = form.slot;
                            app.map.closePopup();
                        } else {
                            error.innerHTML = "Считыватель с таким номером уже существует";
                        }

                    });
                }
            });

            //Обработчик при добавлении слоя
            //Обновлять свойство границы горизонтов
            this.layers.levels.on('layeradd', (e) => {
                this.levelBounds = this.getLevelBounds();

                //Установить вид на первый горизонт
                this.map.fitBounds(this.levelBounds[0].bound);

                app.sortLevels();
            });

            //Сортировать слой levels при добавлении на карту map.layerAdd
            this.layers.levels.on('add', (e) => {
                app.sortLevels();
            });

            //Событие добавления слоя считывателей на карту
            //Если установлено глобальное свойство layerId, то переместить карту к считывателю
            this.layers.readers.on('layeradd', (e) => {
                if (globalOptions.readerId) {
                    app.flyToReader(globalOptions.readerId);
                }
            });

            //Событие добавления слоя векторов на карту
            //Если в глобальных настройках указан id вектора, то перелететь на вектор
            this.layers.vectors.on('layeradd', (e) => {
                if (globalOptions.vectorId) {
                    app.flyToVector(globalOptions.vectorId);
                }
            });

            this.layers.levels.on('layeradd', (e) => {
                if (globalOptions.levelId) {
                    app.flyToLevel(globalOptions.levelId);
                }
            });

            /**Если редактируются начальные вершины вектора, то отменить редактирование
             * TODO: Возможно вернусь к этому варианту. Пока остановился на грязном хаке this.map.on("draw:editstart"
             */
            this.map.on('draw:editvertex1', (e) => {
                console.log(e);
                if (e.poly.layerName == "vectors" && vector) {
                    let vector = app.editableLayer.getLayers().filter(f => f.feature.properties.id == e.poly.feature.properties.id)[0];
                    let vectorCoordinates = vector.getLatLngs();
                    let nodeBeginCoordinates = vector.feature.properties.nodeBegin.geom.coordinates;
                    nodeBeginCoordinates = [nodeBeginCoordinates[1], nodeBeginCoordinates[0]];
                    let nodeEndCoordinates = vector.feature.properties.nodeEnd.geom.coordinates;
                    nodeEndCoordinates = [nodeEndCoordinates[1], nodeEndCoordinates[0]]
                    //Проверить координату начала вектора
                    if (!vectorCoordinates[0].equals(nodeBeginCoordinates)) {
                        vectorCoordinates[0] = L.latLng(nodeBeginCoordinates);
                        vector.setLatLngs(vectorCoordinates);
                    }
                    //Проверить координату конца вектора
                    if (!vectorCoordinates[vectorCoordinates.length - 1].equals(nodeEndCoordinates)) {
                        vectorCoordinates[vectorCoordinates.length - 1] = L.latLng(nodeEndCoordinates);
                        vector.setLatLngs(vectorCoordinates);
                    }
                }
                //e.poly.feature.properties.state = 'edited';
            });

            this.map.on("draw:editstart", v => {
                let isVector = app.editableLayer.getLayers().
                    some(l => l.layerName == "vectors");

                if (isVector) {
                    setTimeout(v => {
                        app.layers.vectors.getLayers().forEach(p => {
                            let line = p.getLatLngs();
                            app.removeGuide(line[0]);
                            app.removeGuide(line[line.length - 1]);
                        });
                    }, 150);
                }
            });


        },

        /**Проверяет форму создания векторов
         * Если верно, то пустой список, иначе возвращает список ошибок
         */
        verifyVectorForm() {
            let error = [];

            //Количество выделенных считывателей должно быть 2
            if (this.layers.readers.counterSelected() != 2)
                error.push('Выделите 2 считывателя');

            //Проверка полей на заполнение
            let fields = Object.entries(this.formVector).filter(l => l[1] == null).map(l => l[0]);
            if (fields.length)
                error.push(`Не заполнены поля: ${fields}`);

            //Проверка есть ли уже вектор для выбранных точек
            let sel = this.layers.readers.getSelected().map(l => l.feature.properties.num);

            //Проверить num у считывателей
            let und = sel.filter(el => el == undefined)
            if (und.length > 0)
                error.push('Не у всех считывателей задан номер');

            let vec = this.layers.vectors
                .getLayers()
                .map(l => [l.feature.properties.nodeBegin.num, l.feature.properties.nodeEnd.num].toString());

            //Найти вектор, у которого либо начальный считыватель либо конечный равны выбранным
            let selVec = vec.includes(sel.sort().toString());

            if (selVec)
                error.push(`Уже есть вектор для считывателей: ${sel}`);



            return error;
        },

        /**Проверяет все новые векторы на соответствие координат начала и конца вектора со считывателями
         * */
        verifyNewVectors() {
            //TODO: доработать метод
        },

        /** Создаёт вектор
         */
        createVector: function () {
            //Проверка на ошибки форму
            let error = this.verifyVectorForm();

            if (!error.length) {
                let c = this.layers.readers.getSelected(this.formVector.reverse);
                //console.log(`Вектор: [x:${c[0].feature.geometry.coordinates} y:${c[1].feature.geometry.coordinates}]`);

                let coor = this.layers.readers.getSelectedYX();

                //Выделенные считыватели, флаг реверс указывает направление вектора
                let nodes = this.formVector.reverse ?
                    this.layers.readers.getSelected().reverse() :
                    this.layers.readers.getSelected();


                //Создать полилинию по координатам выделенных точек
                let pl = L.polyline(this.formVector.reverse ? coor.reverse() : coor);
                let geo = pl.toGeoJSON();
                geo.properties.isEnabled = true;
                geo.properties.state = 'created';
                geo.properties.comment = this.formVector.name;
                geo.properties.length = this.formVector.len;

                //Заполнить начальный и конечый узел считывателя
                geo.properties.nodeBegin = Geometry.create(nodes[0].feature).model;
                geo.properties.nodeEnd = Geometry.create(nodes[1].feature).model;

                geo.properties.readerBeginId = nodes[0].feature.properties.id;
                geo.properties.readerEndId = nodes[1].feature.properties.id;

                this.layers.vectors.addData(geo);

                this.layers.readers.clearSelection();
                this.vectorError = null;
                this.newVectors = this.getNewVectors();

                //Очистить форму
                //this.formVector = { name: null, len: null, reverse: false };
            }
            else {
                this.vectorError = error;
            }
        },


        /**Удаляет с векторов начальную и конечную ручку
        * @param {L.Point} layerPoint L.Point {x: 0, y: 0}
        */
        removeGuide(latLng) {
            let point = this.map.latLngToLayerPoint(latLng);
            let strPoint = `${point.x}px, ${point.y}px`;
            let guide = document.querySelector(`div[style*='${strPoint}']`);
            //Удалить ручку с карты
            if (guide) {
                guide.remove();
            }
        },


        /**Отправляет созданные векторы в БД
         * */
        saveCreatedVectors() {
            //Создать новый слой Layer
            let dbVec = new Layer();
            let newVectors = this.getNewVectors();
            for (let v of newVectors) {
                delete v.lay.feature.properties.nodeBegin;
                delete v.lay.feature.properties.nodeEnd;
                dbVec.addData(v.lay.toGeoJSON());
            }

            /**
             * Выполняется после успешной загрузки слоя
             * @param {any} res ответ
             */
            function responseFill(res) {
                console.log('Сохранение выполнено успешно, векторы загружены');
                if (res.status == 200)//OK
                    app.newVectors = app.getNewVectors();
            }

            /**
             * Обработка ответа от сервера
             * @param {any} res ответ
             */
            function responsePost(res) {
                console.log(res);
                if (res.status == 201) //Created
                    app.layers.vectors.fill(uri + 'vectors', responseFill);
            }

            //Отправить новые векторы на сервер
            dbVec.post(uri + 'vectors', responsePost);


        },

        /**Сохраняет отредактированные веторы в БД
         * */
        saveEditedVectors() {
            //Создать новый слой Layer
            let dbVec = new Layer();
            let newVectors = this.layers
                .vectors.getLayers()
                .filter(l => l.edited == true);

            for (let v of newVectors) {
                delete v.feature.properties.nodeBegin;
                delete v.feature.properties.nodeEnd;
                dbVec.addData(v.toGeoJSON());
            }

            /**
             * Выполняется после успешной загрузки слоя
             * @param {any} res ответ
             */
            function responseFill(res) {
                console.log('Сохранение отредактированных векторов выполнено успешно, векторы загружены');
                if (res.status == 200)//OK
                    app.newVectors = app.getNewVectors();
            }

            /**
             * Обработка ответа от сервера
             * @param {any} res ответ
             */
            function responsePost(res) {
                console.log(res);
                if (res.status == 201) //Created
                    app.layers.vectors.fill(uri + 'vectors', responseFill);
            }

            //Отправить новые векторы на сервер
            dbVec.put(uri + 'vectors', responsePost);


        },

        /**Сохраняет отредактированные считыватели
         * */
        saveEditedReaders() {
            //Создать новый слой Layer
            let dbReaders = new Layer();
            let newReaders = this.layers
                .readers.getLayers()
                .filter(l => l.edited == true);

            for (let v of newReaders) {
                delete v.feature.properties.nodeBegin;
                delete v.feature.properties.nodeEnd;
                dbReaders.addData(v.toGeoJSON());
            }

            /**
             * Выполняется после успешной загрузки слоя
             * @param {any} res от��ет
             */
            function responseFill(res) {
                console.log('Сохранение отредактированных считывателей выполнено успешно, считыватели загружены');
                if (res.status == 200) {//OK
                    app.newReaders = [];
                }
            }

            /**
             * Обработка ответа от сервера
             * @param {any} res ответ
             */
            function responsePut(res) {
                console.log(res);
                if (res.status == 201) //Created
                    app.layers.readers.fill(uri + 'readers', responseFill);
            }

            //Отправить новые считыватели на сервер
            dbReaders.put(uri + 'readers', responsePut);
        },

        /** Удаляет новый вектор с карты
         */
        removeVector: function (index) {
            this.layers.vectors.removeLayer(this.newVectors[index].lay);
            this.newVectors.splice(index, 1);
            this.newVectors = this.getNewVectors();
        },

        /** Возвращает новые векторы
         * {id:0, lay: object}
         */
        getNewVectors: function () {
            let i = 0;

            let ret = this.layers.vectors.getLayers()
                .filter(el => el.feature.properties.state === 'created')
                .map(l => {
                    return { id: i++, lay: l };
                });
            return ret;
        },

        /**
         * Проверяет существует ли считыватель с num
         * @param {Number} num Проверяемый номер считывателя
         * @returns Если считыватель с num существует, то возвращает true
         */
        verifyReaderNum(num) {
            let readers = this.layers.readers
                .getLayers()
                .map(el => el.feature.properties.num);
            return readers.includes(num);
        },

        /**Границы уровней L.Bounds
          */
        getLevelBounds() {
            //let bounds = null;
            //if (this.layers && this.layers.levels) {
            let bounds = this.layers.levels.getLayers().map(el => {
                return {
                    bound: el.getBounds(),
                    name: `${el.feature.properties.comment} [${el.feature.properties.heigth}]`,
                    color: el.feature.properties.color
                };
            });
            //}
            return bounds;
        },

        async hubRestart() {
            try {
                await this.hubConnection.start();
                console.log("Hub restart!");
            } catch (err) {
                console.error(err);
                setTimeout(() => app.hubRestart(), 5000);
            }
        },

        /** Позиционирование
        */
        positioning(data) {
            //Список текущих локаторов на слое
            let currentLocators =
                app.layers.locators
                    .getLayers()
                    .map(el => el.feature.properties);

            //TODO:проверить!
            //Если есть данные в currentLocators, то очистить только те
            //объекты в слое  locators
            let uniq = [];

            //Получить уникальные данные, которых нет на текущем слое
            uniq = data.filter(el => !currentLocators.some(l => l.hash == el.hash));

            //Удаляемые объекты
            let removedLayres = app.layers.locators.filter(el =>
                uniq.some(l =>
                    l.staffFullName === el.feature.properties.staffFullName));
            let removedChemical =
                app.layers.chemical.filter(el =>
                    uniq.some(l => l.staffFullName === el.feature.properties.staffFullName));

            //Удалить уникальные с карты
            for (let l of removedLayres) {
                app.layers.locators.removeLayer(l);
            }

            for (let l of removedChemical) {
                app.layers.chemical.removeLayer(l);
            }

            let gasRegex = /.*Газ.*/;

            //Выбрыть метки ламповой
            let lsels = uniq.filter(el => el.lselIp && !gasRegex.test(el.comment));
            //Метки от lsr
            let lsrs = uniq.filter(el => !el.lselIp && !gasRegex.test(el.comment));
            //Газоанализаторы
            // let analyzers = uniq.filter(el => el.comment);
            let analyzers = uniq.filter(el => gasRegex.test(el.comment));

            //Обработать локаторы ламповой
            for (let el of lsels) {
                //Выбрать считыватель ламповой
                let lsel = app.layers.readers
                    //.getLayers()
                    .filter(l => l.feature.properties.ip == el.lselIp)[0];
                if (lsel) {
                    //let coor = lsel.feature.geometry.coordinates.reverse();
                    //let lselPoint = L.marker(coor);
                    let lselPoint = lsel.toGeoJSON();

                    //TODO:Перенести в отдельную функцию?
                    //Случайная точка возле ламповой + -100
                    let point = lselPoint.geometry.coordinates;

                    let deltaX = Math.floor(Math.random() * 10);
                    let deltaY = Math.floor(Math.random() * 10);

                    let newPoint = [point[0] - 5 + deltaX, point[1] - 5 + deltaY];
                    lselPoint.geometry.coordinates = newPoint;

                    //Запонить свойства из модели
                    lselPoint.properties = el;
                    app.layers.locators.addData(lselPoint);
                }
            }

            //Локатор зарегистрирован на векторе
            for (let el of lsrs) {
                //Проецировать расстояние на векторе
                let gjsonLocator = app.interpolate(el.vectorId, el.procentPosition).toGeoJSON();

                gjsonLocator.properties = el;

                app.layers.locators.addData(gjsonLocator);
            }

            for (let el of analyzers) {
                try {
                    let geoJson = app.interpolate(el.vectorId, el.procentPosition).toGeoJSON();
                    geoJson.properties = el;
                    app.layers.chemical.addData(geoJson);
                    console.log("Analyzer positioning:");
                    console.log(geoJson);
                } catch (error) {
                    console.log(`GasAnalyzer interpolate error ${error}`);
                }
            }
            //Обновить информцию о персонале в ламповой
            app.statisticsUpdate();
        },
        /**Инициализация подключения к хабу SignalR
         * */
        initHub() {
            this.hubConnection = new signalR.HubConnectionBuilder()
                .withUrl(this.hubUrl)
                .configureLogging(signalR.LogLevel.Information)
                .build();

            //Сообщения от газаанализа
            this.hubConnection.on("Chemical", function (data) {
                console.log(data);
            });

            //Сообщения от хаба
            this.hubConnection.on("Messages", function (data) {
                data.message = app.transformMessage(data.message);
                app.hubDataMessages = data;
            });

            //Принимает состояние устройств и отрисовывает на карте
            this.hubConnection.on("DevicesStatus", function (data) {
                let stat = data;
                if (['LSR', 'LSEL', 'BKRO', 'LSER'].includes(stat.type)) {
                    app.setReaderStatus(stat.name, stat.state);
                }
                if (stat.type == 'MAUP') {
                    console.log(`Type MAUP ${stat.name} Статус: ${stat.state}`)
                }

            });

            //Состояние опроса устройств
            this.hubConnection.on("HardwareStatus", function (data) {
                app.hubDataMessages = {
                    message: `Запуск оборудования: ${data}`,
                    type: 'alert alert-info'
                };
                //Состояние оборудования
                app.hardwareOnOff.on = data;
            });

            //При закрытии хаба пробовать перезапустить
            this.hubConnection.onclose(async () => {
                await app.hubRestart();
            });

            /**Позиционирует локаторы на векторах и на считывателе ламповой
             * приходит StaffHistory [{vectorId:1, position: 14},...]
             * Добавляет позиции в слой app.layers.locators
             */
            this.hubConnection.on("LocatorPositioning", this.positioning);

            /**Обрабатывает сообщения аварийного оповещения
             */
            this.hubConnection.on("AlarmConfirm", function (data) {
                //Найти локатор от которого пришло подтверждение об аварии
                let locator = app.layers.locators
                    .getLayers()
                    .filter(p => p.feature.properties.locatorNum == data.locatorNum)[0];

                //Если локатор найден создать сообщение об аварийном оповещении
                if (locator) {
                    if (data.confirmType == 2) {
                        let alarmPopup = L.popup()
                            .setLatLng(locator.getLatLng())
                            .setContent(`<span class="glyphicon glyphicon-alert"></span> Подтверждение аварии: <p>${locator.feature.properties.staffFullName}</p>`)
                            .openOn(app.map);
                    }
                    app.hubDataMessages = {
                        message: `Аварийное подтверждение: ${data.confirmType} Локатор: ${data.locatorNum} Подтвердил: ${locator.feature.properties.staffFullName}`,
                        type: 'alert alert-info'
                    };

                    //Включить режим аварии и выключить после 15 сек
                    app.alarm.mode = true;
                    //setTimeout(() => app.alarm.mode = false, 15000);
                }
            });

            /**Статус аварии
             */
            this.hubConnection.on("AlarmStatus", function (data) {
                console.log(`AlarmStatus: ` + data);

                app.hubDataMessages = {
                    message: `Статус аварийного оповещения: ${data}`,
                    type: 'alert alert-info'
                };

                //Переключить карту в режим аварии
                switch (data) {
                    case 1:
                    case 2:
                        app.alarm.mode = true;
                        break;
                    case 8:
                        app.alarm.mode = false;
                        break;
                }
            });

            this.hubConnection.on("SyncVectors", function (data) {
                setTimeout(() => app.layers.vectors.fill(uri + "vectors"), 350);
            });

            this.hubConnection.on("ViolationTimeMode", function (data) {
                console.log(data);
            });


            this.hubConnection
                .start()
                .then(function () {
                    console.log("Map hub started!");
                    //Определить состояние запуска обурудования при открытии карты
                    app.hubConnection.invoke('OnOff', null);

                    //Статус аварийного оповещения
                    app.hubConnection.invoke("AlarmStatus");

                    //Последние призраки (устаревшие)
//                    app.hubConnection.invoke('Old', 1);

                })
                .catch(error => console.error(error.toString()));

        },

        /**Отключает редактирование слоёв
         * */
        disableEdited() {
            if (this.layers) {
                let editLayers = Object.entries(this.layers)
                    .filter(el => ['readers', 'vectors', 'levels'].indexOf(el[0]) !== -1);
                for (let lay of editLayers) {
                    lay[1].disableEdit();
                }
            }
        },

        /**
         * Включает/выключает редактирование слоя
         * @param {string} layerName Имя слоя
         * @param {boolean} action true - on/false - off
         */
        editLayer(layerName, action) {
            let layer = Object.entries(this.layers)
                .filter(el => el[1].name == layerName);

            if (layer[0][1] instanceof Layer) {
                if (action) { //Если редактирование - true
                    //Очистить слой редактирования на всякий случай
                    this.editableLayer.clearLayers();
                    //Поместить в слой редактирования
                    layer[0][1].eachLayer(l => {
                        l.layerName = layer[0][0];
                        this.editableLayer.addLayer(l);
                    });

                    //Активировать редактирование
                    this.editTool.editHandler.enable();
                    this.currentLayer.state = true;
                } else {//Закончить редактирование, сохранить данные
                    this.editTool.editHandler.save(); //Сработает событие сохранения отредактированных слоёв
                    this.editTool.editHandler.disable();//Отключить инструмент редактирования
                    this.editableLayer.clearLayers();
                    this.currentLayer.state = false;
                }
            }
        },

        /**Отменяет редактирование слоя
         * */
        cancelEditLayer() {
            this.editTool.editHandler.revertLayers();
            this.editTool.editHandler.disable();


            this.currentLayer.state = false;

            //Вернуть векторы на свои места
            let editReaders = this.editableLayer.getLayers().some(l => l.layerName == 'readers');
            if (editReaders) {
                //for (let l of this.layers.readers.getLayers()) {
                //    this.syncVectors(l);
                //}
                this.layers.vectors.fill(uri + 'vectors');
            }
            this.editableLayer.clearLayers();
        },

        /**
         * Синхронизирует координаты начальных точек векторов  по считывателю
         * @param {Layer} reader C
         */
        syncVectors(reader) {
            //Искать векторы в которых есть
            let vectors =
                app.layers.vectors
                    .filter(v =>
                        v.feature.properties.readerBeginId == reader.feature.properties.id ||
                        v.feature.properties.readerEndId == reader.feature.properties.id);

            let pnt = reader.getLatLng();

            for (let vect of vectors) {
                let vectCoord = vect.getLatLngs();
                //Если изменилась точка начала вектора
                if (vect.feature.properties.readerBeginId == reader.feature.properties.id) {
                    vectCoord[0] = pnt;
                }
                else {
                    vectCoord[vectCoord.length - 1] = pnt;
                }

                vect.setLatLngs(vectCoord);
            }
        },

        /**
         * Устанавливает вид кружка в зависимости типа
         * @param {any} readerNum
         * @param {any} status
         */
        setReaderStatus(readerNum, status) {

            //Если карта в режиме просмотра
            //Изменить цвет обводки в зависимости от статуса
            if (globalOptions.mode == "view") {

                //Найти считыватель по номеру
                //let reader = this.layers.readers.getLayers()
                //    .filter(l => l.feature.properties.num == readerNum);

                let reader = null;
                //Является ли readerNum ip => это LSEL
                if (/\d+\.\d+\.\d+\.\d+/.test(readerNum)) {
                    //Найти считыватель по ip
                    reader = this.layers.readers.getLayers()
                        .filter(l => l.feature.properties.ip == readerNum);
                } else {
                    //Найти считыватель по номеру
                    reader = this.layers.readers.getLayers()
                        .filter(l => l.feature.properties.num == readerNum);

                }


                if (reader[0] instanceof L.Layer) {
                    switch (status) {
                        case "Online":
                            reader[0].setStyle({ color: 'lime' });
                            break;
                        case "Offline":
                            reader[0].setStyle({ color: 'maroon' });
                            break;
                        case "Unknown":
                            reader[0].setStyle({ color: 'gray' });
                            break;
                        case "Configuration":
                            reader[0].setStyle({ color: 'aqua' });
                            break;
                        case "Synchronization":
                            reader[0].setStyle({ color: 'teal' });
                            break;
                        case "ErrorConfiguration":
                            reader[0].setStyle({ color: 'fuchsia' });
                            break;
                    }
                }
            }
        },

        /**
         * Перелететь на считыватель по ID
         * Добавить подсказку на карту
         * @param {any} readerId Id считывателя в БД
         */
        flyToReader(readerId) {
            let reader = this.layers.readers.getLayers()
                .filter(l => l.feature.properties.id == readerId)[0];

            if (reader) {
                let bou = [L.latLng([reader.getLatLng().lat + 50, reader.getLatLng().lng + 50]),
                L.latLng([reader.getLatLng().lat - 50, reader.getLatLng().lng - 50])];
                this.map.fitBounds(bou);
                L.popup()
                    .setLatLng(reader.getLatLng())
                    .setContent(`<b>Считыватель: ${reader.feature.properties.num}</b>`)
                    .openOn(this.map);
            }
        },

        /**
         * Ищет на карте человека по имени и рисует Popup 
         * @param {string} name Имя человека (staffHistories.staffFullName)
         */
        flyToStaff(name) {
            let staff =
                this.layers.locators.getLayers()
                    .filter(p => p.feature.properties.staffFullName == name)[0];

            if (staff) {
                let bou = [L.latLng([staff.getLatLng().lat + 250, staff.getLatLng().lng + 250]),
                L.latLng([staff.getLatLng().lat - 250, staff.getLatLng().lng - 250])];
                this.map.fitBounds(bou);
                L.popup()
                    .setLatLng(staff.getLatLng())
                    .setContent(`<b>${staff.feature.properties.staffFullName}</b>`)
                    .openOn(this.map);
            }
        },

        /**
         * Перелететь на вектор по ID
         * @param {any} vectorId ID вектора
         */
        flyToVector(vectorId) {
            let vector = this.layers.vectors.getLayers()
                .filter(l => l.feature.properties.id == vectorId)[0];

            if (vector) {
                let bou = vector.getBounds();
                this.map.fitBounds(bou);

                L.popup()
                    .setLatLng(vector.getLatLngs()[0])
                    .setContent(`<b>Вектор ИД: ${vector.feature.properties.id}</b><br/>` +
                        `Имя: ${vector.feature.properties.comment}`)
                    .openOn(this.map);
            }
        },

        /**
         * Перелететь на зону
         * @param {any} levelId
         */
        flyToLevel(levelId) {
            let level = this.layers.levels.getLayers()
                .filter(l => l.feature.properties.id == levelId)[0];

            if (level) {
                let bou = level.getBounds();
                this.map.fitBounds(bou);

                L.popup()
                    .setLatLng(bou.getCenter())
                    .setContent(`Зона: ${level.feature.properties.id}`)
                    .openOn(this.map);
            }
        },

        /**
         * Показывает локатор на векторе
         * @param {number} vectorId ид вектора
         * @param {number} procentPosition позиция на векторе в процентах
         */
        flyToLocator(vectorId, procentPosition) {
            let mark = this.interpolate(vectorId, procentPosition, `Вектор ${vectorId}`);
            mark.addTo(this.map);
        },

        /**
         * Возвращает маркер на векторе на расстоянии в процентах
         * @param {any} vectorId ID вектора
         * @param {any} procentPosition Расстояние в процентах (0..1)
         * @param {string} popupContent HTML подсказки
         */
        interpolate(vectorId, procentPosition, popupContent = "") {
            let vec = this.layers.vectors
                .getLayers()
                .filter(v => v.feature.properties.id === vectorId);
            let ll = L.GeometryUtil.interpolateOnLine(this.map, vec[0], procentPosition);
            return L.marker(ll.latLng).bindPopup(popupContent);
        },

        /**Обновляет статистику statistics
         * */
        statisticsUpdate() {
            //Персонал в шахте
            this.statistics.staffInMine =
                this.layers.locators
                    .filter(l => !l.feature.properties.lselIp)
                    .map(l => l.feature.properties);

            //Персонал в ламповой
            this.statistics.staffInLsel = this.layers.locators
                .filter(l => l.feature.properties.lselIp)
                .map(l => l.feature.properties);

            //Список персонала на карте
            if (this.statistics.isLsel) {
                this.statistics.staffList = this.statistics.staffInLsel;
            } else {
                this.statistics.staffList = this.statistics.staffInMine;
            }

            //Обновить информацию о старых метках
            this.statistics.old = app.layers.locators
                .filter(l => l.feature.properties.old)
                .length;

            //Общее количество на карте
            this.statistics.all = this.layers.locators.getLayers().length;

            //Обновить свойство векторы
            if (this.statistics.vectors.length != this.layers.vectors.getLayers().length) {
                this.statistics.vectors = this.layers.vectors
                    .getLayers()
                    .map(l => l.feature.properties);
            }
        },

        /**Преобразует сообщение для отображения в хаб
         * @param {string} message Сообщение для хаба
         * @returns
         * */
        transformMessage(message) {
            let ret = '';

            if (/MaupId.*LSEL'/i.test(message))
                return message.match(/MaupId.*LSEL'/g)[0];

            return message;
        },

        /**Останавливает аварийное оповещение
         * */
        alarmOff() {
            //Послать SignalR запустить оборудование
            this.hubConnection.invoke('AlarmOff', true);
        },

        /**
         * Отправляет запрос к api со списком персонала
         * Возвращает список id
         * @param {Array} staffs Список имён ["Иванов","Петров"]
         * @param {function} funcThen Функция для обрабоки результата
         */
        staffIds(staffs, funcThen = null) {

            //Преобразовать список в строку с разделителями |
            let queryString = staffs.join('|');

            axios
                .get(baseUrl + `api/staffs/ids/list/${queryString}`)
                .then(function (response) {
                    //Вызов функции обратного вызова
                    typeof funcThen === 'function' && funcThen(response);
                })
                .catch(function (error) {
                    console.log(error);
                });
        },

        /**
         * Подать персональную аварию
         * @param {Array} staffs Список staff.id [1,2,3]
         * @param {function} funcThen функция вызывается после успешного ответа 
         */
        personalAlarm(staffs, funcThen = null) {
            //Найти staffs ids по входящему списку пользователей

            //Alarm.TimeStart=2021-01-11T20%3A33&Alarm.Type=1&staffIds=1&staffIds=2&devices=1&Alarm.Comment=Test3
            this.staffIds(staffs, (response) => {
                let ids =
                    response.data
                        .map(e => e.id);
                //  {
                //         "timeStart": "2021-11-17T20:39:00",
                //         "type": "personal",
                //         "deviceType": 16,
                //         "comment": "Тест api",
                //         "isEnabled": true,
                //         "isDeleted": false
                // }
                // {
                //     "timeStart": "2021-11-17T20:39:00",
                //     "type": "personal",
                //     "deviceType": 16,
                //     "comment": "Тест api",
                //     "alarmConfirms": [],
                //     "isEnabled": true,
                //     "isDeleted": false
                // }

                let alarmModel = {
                    "timeStart": (new Date().toISOString()),
                    "type": "personal",
                    "deviceType": 16,
                    "comment": `${staffs}`,
                    "isEnabled": true,
                    "isDeleted": false
                };

                // let data = JSON.stringify(alarmModel);
                //Отправить запрос о аварии
                //TODO:передалать под отправку нескольким пользователям
                axios
                    .post(baseUrl + `api/alarms/${ids[0]}`, alarmModel)
                    .then(function (response) {
                        console.log(response);
                        //Вызов функции обратного вызова
                        typeof funcThen === 'function' && funcThen(response);
                    })
                    .catch(function (error) {
                        console.log(error);
                    });
            });
        },

        /**
         * Обновление статистики statustics.allStaffs
         */
        allStaffsUpdate() {
            //Загрузить весь персонал
            axios
                .get(baseUrl + 'api/staffs')
                .then(response => {
                    app.statistics.allStaffs = response.data;
                })
                .catch(error => {
                    console.log('Error: get data from api/staffs');
                });

        },

    }
});
