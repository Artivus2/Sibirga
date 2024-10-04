'use strict';
var baseUrl = globalOptions.appUrl + '/';
var uri = baseUrl + 'api/';
var uriData = baseUrl + 'data/';
class DateTime {
    static toRuTime(timeStr) {
        let ms = Date.parse(timeStr);
        let dt = new Date(ms);
        let options = {
            year: 'numeric',
            month: 'numeric',
            day: 'numeric',
            hour: 'numeric',
            minute: 'numeric',
            second: 'numeric',
            timezone: 'UTC'
        };
        let ret = dt.toLocaleString("ru", options);
        return ret;
    }
    static create(obj) {
        return new this(obj);
    }
}
class Geometry {
    constructor(obj) {
        switch (this.defineType(obj)) {
            case 'model':
                this.model = obj;
                this.geoJson = Geometry.toGeoJSON(obj);
                break;
            case 'geojson':
                this.geoJson = obj;
                this.model = Geometry.toModel(obj);
                break;
            case 'undefined':
                this.model = {};
                this.geoJson = {};
                break;
        }
    }
    static create(obj) {
        return new this(obj);
    }
    defineType(obj) {
        let objectType = 'undefined';
        if (obj.type && obj.type == 'Feature') {
            objectType = 'geojson';
        }
        if (obj.id) {
            objectType = 'model';
        }
        return objectType;
    }
    static toGeoJSON(obj) {
        let out = {};
        let prop = {};
        out.type = 'Feature';
        out.geometry = obj.geom;
        let map_ = new Map(Object.entries(obj).filter(l => l[0] != 'geom'));
        map_.forEach((v, k) => prop[k] = v);
        out.properties = prop;
        return out;
    }
    static toModel(geoJson) {
        let out = {};
        Object.entries(geoJson.properties).forEach(el => out[el[0]] = el[1]);
        out.geom = geoJson.geometry;
        return JSON.parse(JSON.stringify(out));
    }
}
var Layer = L.GeoJSON.extend({
    constructor(geoJson) {
        this.addData(geoJson);
    },
    name: "No name",
    enableEdit() {
        this.eachLayer(l => l.editing.enable());
    },
    disableEdit() {
        this.eachLayer(l => l.editing.disable());
    },
    post(apiUrl, funcThen = null) {
        let modelObjects = this.getLayers()
            .filter(l => l.feature.properties.state == 'created')
            .map(l => Geometry.create(l.feature).model);
        for (let mod of modelObjects) {
            console.log(mod);
            axios
                .post(apiUrl, mod)
                .then(function (response) {
                    typeof funcThen === 'function' && funcThen(response);
                })
                .catch(function (error) {
                    console.log(error);
                });
        }
    },
    put(apiUrl, funcThen = null) {
        let modelObjects = this.getLayers()
            .map(l => Geometry.create(l.feature).model);
        for (let mod of modelObjects) {
            axios
                .put(apiUrl + `/${mod.id}`, mod)
                .then(function (response) {
                    typeof funcThen === 'function' && funcThen(response);
                })
                .catch(function (error) {
                    console.log(error);
                });
        }
    },
    fill(apiUrl, funcThen = null) {
        let data = [];
        let l = {};
        axios
            .get(apiUrl)
            .then(response => {
                data = response.data;
                this.clearLayers();
                if (data.type && data.type == 'FeatureCollection') {
                    this.addData(data);
                }
                else {
                    data.forEach(el => {
                        l = Geometry.create(el).geoJson;
                        this.addData(l);
                    });
                }
                typeof funcThen === 'function' && funcThen(response);
            })
            .catch(error => {
                console.log(error);
            });
    },
    counterSelected() {
        return this.getLayers().filter(el => el.feature.properties.isSelected == true).length;
    },
    getSelected(reverse = false) {
        let ret = this.getLayers().filter(el => el.feature.properties.isSelected == true);
        return reverse ? ret.reverse() : ret;
    },
    getSelectedYX() {
        let ret = [];
        let sel = this.getSelected();
        if (sel.every(el => el.feature.geometry.type === 'Point')) {
            ret = sel.map(el => {
                let c = el.feature.geometry.coordinates;
                return [c[1], c[0]];
            })
        }
        else {
            ret = 'Не все элементы Point';
        }
        return ret;
    },
    clearSelection() {
        this.getLayers()
            .filter(el => el.feature.properties.isSelected)
            .forEach(el => {
                el.feature.properties.isSelected = false;
                el.setStyle({ fillColor: 'green' });
            });
    },
    filter(func) {
        return this.getLayers().filter(func);
    }
});
class Layers {
    constructor() {
        this.filledLayers = new Set();
        this.background = {
            anno: new Layer([], {
                style: function (feature) {
                    return {
                        color: 'gray',
                        weight: 1,
                        smootFactor: 1
                    };
                }
            }),
            lines: new Layer([], {
                interactive: false,
                style: function (feature) {
                    return {
                        color: 'gray',
                        weight: 1,
                        smoothFactor: 10
                    };
                }
            })
        };
        this.levels = new Layer([], {
            pane: 'levels',
            draggable: false,
            style: function (feature) {
                return {
                    color: feature.properties.color,
                    weight: 2,
                    className: 'horizon-map-levels'
                };
            },
            filter: (feature, layer) => {
                return feature.properties.isEnabled;
            }
        });
        this.levels.bindPopup(function (layer) {
            let content = layer.feature.properties.comment ? `<h4>Зона "${layer.feature.properties.comment}"</h4>` :
                `<h4>Зона без названия</h4>`;
            content += `<br><span>Высота(м): ${layer.feature.properties.heigth}</span>`;
            content += `<br><span>ИД: ${layer.feature.properties.id}</span>`;
            return content;
        }, {
            className: 'horizon-map-popup',
            opacity: 0.7
        });
        this.vectors = new Layer([], {
            pane: 'vectors',
            style: function (feature) {
                return {
                    color: '#046C86',
                    weight: 3
                };
            },
            onEachFeature: function (feature, layer) {
                layer.setText('           ►', {
                    repeat: true,
                    offset: 5.5,
                    below: false,
                    attributes: {
                        'fill': '#046C86',
                        'font-size': '16'
                    }
                });
            },
        });
        this.vectors.bindTooltip(function (layer) {
            let content = '<h4>Вектор</h4>';
            content += `<br><span>ИД: ${layer.feature.properties.id}</span>`;
            content += layer.feature.properties.comment ? `<h5>${layer.feature.properties.comment}</h5>` :
                `<h5>Нет названия</h5>`;
            content += `<br><span>Координаты: ${layer.feature.geometry.coordinates[0]}..</span>`;
            content += `<br><span>Длина: ${layer.feature.properties.length}</span>`;
            return content;
        }, {
            className: 'horizon-map-tooltip',
            opacity: 0.7
        });
        this.readers = new Layer([], {
            pane: 'readers',
            style: function (feature) {
                if (globalOptions.mode == 'Config') {
                    switch (feature.properties.isSelected) {
                        case true:
                            return { fillColor: 'red' };
                        case false:
                            if (feature.geometry.coordinates[0] < 0)
                                return { fillColor: 'cyan' };
                            else
                                return { fillColor: 'green' };
                    }
                }
            },
            pointToLayer: function (feature, latlng) {
                return L.circleMarker(latlng, {
                    radius: 8,
                    fillColor: feature.geometry.coordinates[0] < 0 ? 'cyan' : 'green',
                    color: "#FFFFCD",
                    weight: 4,
                    opacity: 1,
                    fillOpacity: 0.8
                });
            },
            filter: (feature, layer) => {
                return feature.properties.isEnabled;
            }
        });
        this.readers.bindPopup((layer) => {
            let context = `<h4>Считыватель ${layer.feature.properties.num}</h4>`
            context += `<a href="${globalOptions.appUrl}/Readers/Edit/${layer.feature.properties.id}">Редактировать</a>`;
            context += `<br/><a href="${globalOptions.appUrl}/Readers/Details/${layer.feature.properties.id}">Детали</a>`;
            return context;
        }, {
            className: 'horizon-map-popup',
            opacity: 0.5
        }
        );
        this.readers.bindTooltip(function (layer) {
            let content = '<h4>Считыватель</h4>';
            content += layer.feature.properties.comment ? `<h5>${layer.feature.properties.comment}</h5>` :
                `<h5>Нет названия</h5>`;
            content += `<br><span>Номер: <b>${layer.feature.properties.num}</b></span>`;
            content += `<br><span>Слот: ${layer.feature.properties.slot} </span>`;
            content += `<br><span>ИД: ${layer.feature.properties.id}</span>`;
            content += `<br><span>Выделен: ${layer.feature.properties.isSelected} </span>`;
            content += `<br><span>Имя: ${layer.feature.properties.comment} </span>`;
            content += `<br><span>Координаты: ${layer.feature.geometry.coordinates[0]}...</span>`;
            return content;
        }, {
            className: 'horizon-map-tooltip',
            opacity: 0.7
        });
        this.locators = new Layer([], {
            style: function (feature) {
                return feature.properties.old ? { markerColor: 'red' } : { markerColor: 'cadetblue' };
            },
            pointToLayer: function (feature, latlng) {
                return L.marker(latlng, {
                    icon: L.AwesomeMarkers.icon({
                        icon: 'user',
                        prefix: 'glyphicon',
                        markerColor: 'cadetblue',
                        spin: false
                    })
                });
            },
            onEachFeature: function (feature, layer) {
                setTimeout(() => {
                    layer.feature.properties.old = true;
                    layer.setOpacity(0.7);
                },
                    60000
                );
            }
        });
        this.locators.bindTooltip(function (layer) {
            let content = '';
            content += `<br><h5><span>${layer.feature.properties.staffFullName}</span></h5>`;
            content += `<br><span>Комментарий: ${layer.feature.properties.comment}</span>`;
            content += `<br><span>Номер: ${layer.feature.properties.locatorNum}</span>`;
            content += `<br><span>Расстояние: ${layer.feature.properties.distance}</span>`;
            content += `<br><span>Скорость: ${Math.abs(layer.feature.properties.speed)}</span>`;
            if (layer.feature.properties.old) {
                content += `<br><span>Время: ${DateTime.toRuTime(layer.feature.properties.time)}</span>`;
            }
            return content;
        },
            {
                className: 'horizon-map-tooltip-locator',
                opacity: 0.7
            }
        );
        this.history = new Layer([], {
            pointToLayer: function (feature, latlng) {
                return L.marker(latlng, {
                    icon: L.AwesomeMarkers.icon({
                        icon: 'dashboard',
                        prefix: 'glyphicon',
                        markerColor: "magenta",
                        spin: false
                    })
                });
            }
        });
        this.history.bindTooltip(function (layer) {
            let content = '';
            content += `<br><h5><span>${layer.feature.properties.staffFullName}</span></h5>`;
            content += `<br><span>Комментарий: ${layer.feature.properties.comment}</span>`;
            content += `<br><span>Номер: ${layer.feature.properties.locatorNum}</span>`;
            content += `<br><span>Расстояние: ${layer.feature.properties.distance}</span>`;
            content += `<br><span>Скорость: ${Math.abs(layer.feature.properties.speed)}</span>`;
            if (layer.feature.properties.old) {
                content += `<br><span>Время: ${DateTime.toRuTime(layer.feature.properties.time)}</span>`;
            }
            return content;
        },
            {
                className: 'horizon-map-tooltip-locator',
                opacity: 0.7
            }
        );
        this.chemical = new Layer([], {
            pointToLayer: function (feature, latlng) {
                let color = 'white';
                let els = feature.properties.chemicalHistories;
                if (els != null) {
                    for (let el of els) {
                        let sign = Math.sign(el.preThreshold) >= 0;
                        let preThreshold = false;
                        let threshold = false;
                        let spin = false;
                        if (sign) {
                            preThreshold =
                                el.meterage >= Math.abs(el.preThreshold) &&
                                el.meterage < Math.abs(el.threshold);
                            threshold =
                                el.meterage >= el.threshold;
                        } else {
                            preThreshold =
                                el.meterage <= Math.abs(el.preThreshold) &&
                                el.meterage > Math.abs(el.threshold);
                            threshold =
                                el.meterage <= Math.abs(el.threshold);
                        }
                        if (preThreshold) {
                            color = "blue";
                        }
                        if (threshold) {
                            color = "red";
                            spin = true;
                        }
                    }
                }
                return L.marker(latlng, {
                    icon: L.AwesomeMarkers.icon({
                        icon: 'fire',
                        iconColor: color,
                        prefix: 'glyphicon',
                        markerColor: 'red',
                        spin: false
                    })
                });
            },
            onEachFeature: function (feature, layer) {
                setTimeout(() => {
                    layer.feature.properties.old = true;
                    layer.setOpacity(0.7);
                },
                    60000
                );
            }
        });
        this.chemical.bindTooltip(function (layer) {
            let content = '';
            content += `<br><h5><span>${layer.feature.properties.staffFullName}</span></h5>`;
            content += `<br><span>Номер: ${layer.feature.properties.locatorNum}</span>`;
            content += `<br><span>Расстояние: ${layer.feature.properties.distance}</span>`;
            content += `<br><span>Скорость: ${Math.abs(layer.feature.properties.speed)}</span>`;
            if (layer.feature.properties.old) {
                content += `<br><span>Время: ${DateTime.toRuTime(layer.feature.properties.time)}</span>`;
            }
            content += `<br><b>Газоанализ</b>`;
            let hist = layer.feature.properties.chemicalHistories;
            if (hist != null) {
                for (let ch of hist) {
                    content += `<br><span>&emsp;${ch.substance} = ${ch.meterage}</span>`;
                }
            }
            return content;
        },
            {
                className: 'horizon-map-tooltip-chemical',
                opacity: 0.7
            }
        );
        this.background.anno.name = 'Аннотации';
        this.background.lines.name = 'Схема';
        this.levels.name = 'Зоны';
        this.vectors.name = 'Векторы';
        this.readers.name = 'Считыватели';
        this.locators.name = 'Персонал';
        this.history.name = "История";
        this.chemical.name = 'Газоанализ';
    }
    fill(apiUrl, afterLoadingLayers = null) {
        this.background.anno.fill(uriData + 'anno.json');
        this.levels.fill(apiUrl + 'levels',
            response => {
                this.filledLayers.add('levels');
                typeof afterLoadingLayers === 'function' && afterLoadingLayers(this.filledLayers);
            });
        this.readers.fill(apiUrl + 'readers/lite', response => {
            this.filledLayers.add('readers');
            typeof afterLoadingLayers === 'function' && afterLoadingLayers(this.filledLayers);
        });
        setTimeout(f => {
            this.vectors.fill(apiUrl + 'vectors', response => {
                this.filledLayers.add('vectors');
                typeof afterLoadingLayers === 'function' && afterLoadingLayers(this.filledLayers);
            });
        }, 500);
        this.background.lines.fill(uriData + 'lines.json');
        return this;
    }
    addTo(obj, layers = ['readers', 'vectors', 'locators']) {
        this.background.lines.addTo(obj);
        let customLayers = Object.entries(this)
            .filter(el => layers.indexOf(el[0]) >= 0);
        for (let i of customLayers)
            i[1].addTo(obj);
    }
    refresh(layerName) {
        let layer = Object.entries(this).filter(l => l[0] == layerName);
        if (layer[0][1] instanceof Layer) {
            layer[0][1].fill(uri + layerName, console.log);
        }
    }
    get oldLocators() {
        let old = this.locators.filter(l => l.feature.properties.old);
        let ret = {
            locators: old,
            count: old.length
        };
        return ret;
    }
}
var htmlReaderEdit = '';
var L_CANVAS_PREFER = true;
var app = new Vue({
    el: '#vue',
    data: {
        menu: {
            mapManagement: false,
            hardwareEvents: false,
            layerSettings: globalOptions.features == 'vectorCreate' ? true : false,
            lampRoom: false,
            info: false,
            changeItem: function (itemName) {
                if (typeof this[itemName] === "boolean") {
                    this[itemName] = !this[itemName];
                }
                for (let i in this) {
                    if (i != itemName && typeof this[i] === "boolean") {
                        this[i] = false;
                    }
                }
            }
        },
        messageHub: {
            bufferSize: 100,
            filter: ''
        },
        currentLocators: [],
        readerEditContent: htmlReaderEdit,
        startReaderId: globalOptions.readerId,
        editableLayer: null,
        editTool: {
            toolbar: null,
            editHandler: null,
        },
        map: null,
        name: '0',
        zoom: null,
        check: false,
        point: "[0, 0]",
        currentLayer: {
            name: 'nothing',
            id: -1,
            state: false
        },
        mapLayers: null,
        currentZoom: null,
        selectedMode: globalOptions.features == 'vectorCreate' ? true : false,
        vectorError: null,
        layers: null,
        layersControl: null,
        newVectors: [],
        newReaders: [],
        formVector: {
            name: null,
            len: null,
            reverse: false
        },
        formReader: {
            num: null,
            slot: null
        },
        levelBounds: null,
        hubUrl: baseUrl + 'hardware',
        hubConnection: null,
        hubData: [],
        hardwareOnOff: {
            disabled: false,
            on: false,
            timeDelay: 0
        },
        statistics: {
            isLsel: false,
            staffList: [],
            filter: "",
            vectors: [],
            currentVectorId: null,
            allStaffs: [],
            staffInLsel: [],
            staffInMine: [],
            old: 0,
            all: 0,
            updateStaffs: function () {
                app.allStaffsUpdate();
            }
        },
        history: {
            staffHistories: [],
            staffFilter: "",
            staff: [],
            changesStaff: [],
            after: null,
            before: null,
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
            play() {
                app.layers.history.clearLayers();
                let lsel = app.layers.readers
                    .filter(l => l.feature.properties.ip != null)[0]
                    .toGeoJSON();
                for (let el of this.staffHistories) {
                    if (!el.vectorId && el.lselIp != null) {
                        lsel.properties = el;
                        app.layers.history
                            .addData(lsel);
                        continue;
                    }
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
            clearChange() {
                this.staff
                    .filter(el => el.checked == true)
                    .forEach(el => {
                        el.checked = false;
                    });
            },
            get checkedStaff() {
                let checked =
                    this.staff
                        .filter(el => el.checked == true);
                return checked;
            },
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
        alarm: {
            mode: false,
            status: -1
        }
    },
    mounted() {
        this.initMap(this.initLayers);
        this.initEvents();
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
        this.editableLayer = new Layer([], { draggable: true });
        this.editableLayer.bindTooltip(function (layer) {
            let content = `<h4>${layer.feature.properties.comment}</h4>`;
            return content;
        }, {
            className: 'horizon-map-tooltip',
            opacity: 0.7
        });
        this.editTool.toolbar = new L.EditToolbar({
            featureGroup: this.editableLayer,
        });
        this.editTool.editHandler = this.editTool.toolbar.getModeHandlers()[0].handler;
        this.editTool.editHandler._map = this.map;
        this.currentZoom = this.map.getZoom();
    },
    created: function () {
    },
    watch: {
        selectedMode: function (val) {
        },
        currentZoom: function () {
            var z = this.map.getZoom();
            if (this.currentZoom != z) {
                this.map.setZoom(z);
            }
        },
    },
    computed: {
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
        hubDataMessages: {
            get: function () {
                let f = this.messageHub.filter;
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
        hardwareStartStop: {
            get: function () {
                return this.hardwareOnOff.on;
            },
            set: function (value) {
                if (this.hubConnection && this.hubConnection.state) {
                    this.hardwareOnOff.on = value;
                    this.hardwareOnOff.disabled = true;
                    this.disabledDelay = 10;
                    setTimeout(() => this.hardwareOnOff.disabled = false, 10000);
                    this.hubConnection.invoke('OnOff', this.hardwareOnOff.on);
                }
            }
        },
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
    methods: {
        sortLevels() {
            let sortLevels = this.layers.levels
                .getLayers()
                .sort((a, b) => a.feature.properties.heigth - b.feature.properties.heigth);
            for (let l of sortLevels) {
                l.bringToFront();
            }
        },
        afterLoadingLayers(filledLayers) {
            let sa = Array.from(filledLayers);
            let loading = ['vectors', 'readers', 'levels'].every(l => sa.includes(l));
            if (loading) {
                this.initHub();
            }
        },
        initMap(callback) {
            this.map = L.map('map', {
                editable: true,
                editOptions: {
                },
                crs: L.CRS.Simple,
                minZoom: -6,
                maxZoom: 6
            }).setView([6000, 3000], 2);
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
            typeof callback === 'function' && callback();
            return this.map;
        },
        initLayers() {
            this.layers = new Layers();
            this.layers.fill(uri, this.afterLoadingLayers);
            if (globalOptions.layers.length > 0) {
                this.layers.addTo(this.map, globalOptions.layers);
            } else {
                this.layers.addTo(this.map);
            }
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
        initEvents() {
            this.layers.readers.on('click', function (event) {
                let ll = event.latlng;
                let flag;
                app.layers.readers.getLayers()
                    .filter(l => l.getLatLng().lat == ll.lat && l.getLatLng().lng == ll.lng)
                    .forEach(l => {
                        if (app.selectedMode) {
                            l.feature.properties.isSelected = !l.feature.properties.isSelected;
                            flag = l.feature.properties.isSelected;
                            l.setStyle({ fillColor: flag ? 'red' : 'green' });
                        }
                    });
                console.log({ ll: event.latlng, ev: event, isSelected: flag });
            });
            this.map.on(L.Draw.Event.CREATED, function (e) {
                let type = e.layerType,
                    layer = e.layer;
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
            this.map.on('draw:edited', (e) => {
                let editedLayers = e.layers.getLayers();
                let tempLayers = {};//Временные слои для отправки измененией на сервер
                if (editedLayers.some(l => l.layerName == "readers")) {
                    let readersIds = editedLayers.map(l => l.feature.properties.id);
                    tempLayers["vectors"] = new Layer();
                    let vectors = app.layers.vectors.filter(v => readersIds.some(s => s == v.feature.properties.readerBeginId) ||
                        readersIds.some(s => s == v.feature.properties.readerEndId));
                    vectors.forEach(v => tempLayers["vectors"]
                        .addData(v.toGeoJSON()));
                }
                for (let l of editedLayers) {
                    if (tempLayers[l.layerName] === undefined) {
                        tempLayers[l.layerName] = new Layer();
                    }
                    tempLayers[l.layerName].addData(l.toGeoJSON());
                }
                for (let lay in tempLayers) {
                    tempLayers[lay].put(uri + lay, l => {
                        app.layers.refresh(lay);//Обновить слой на карте
                    });
                }
            });
            this.map.on('draw:editmove', (e) => {
                if (e.layer.layerName === "readers") {
                    this.syncVectors(e.layer);
                }
            });
            this.layers.readers.on('contextmenu', (e) => {
                let marker = e.target;
                let feature = e.layer.feature;
                let form = {};
                console.log(e);
                if (feature.properties.state === 'created') {
                    this.map.openPopup(this.readerEditContent, e.layer.getLatLng());
                    let error = L.DomUtil.get('error-message');
                    let inputNum = L.DomUtil.get('input-num');
                    inputNum.value = feature.properties.num;
                    let inputSlot = L.DomUtil.get('input-slot');
                    inputSlot.value = feature.properties.slot;
                    let inputComment = L.DomUtil.get('input-comment');
                    inputComment.value = feature.properties.comment;
                    L.DomEvent.addListener(inputNum, 'change', function (e) {
                        form.num = e.target.value;
                        inputComment.value = form.comment = 'R' + form.num;
                    });
                    L.DomEvent.addListener(inputSlot, 'change', function (e) {
                        form.slot = e.target.value;
                    });
                    L.DomEvent.addListener(inputComment, 'change', (e) => {
                        form.comment = e.target.value;
                    });
                    let buttonSubmit = L.DomUtil.get('button-submit');
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
            this.layers.levels.on('layeradd', (e) => {
                this.levelBounds = this.getLevelBounds();
                this.map.fitBounds(this.levelBounds[0].bound);
                app.sortLevels();
            });
            this.layers.levels.on('add', (e) => {
                app.sortLevels();
            });
            this.layers.readers.on('layeradd', (e) => {
                if (globalOptions.readerId) {
                    app.flyToReader(globalOptions.readerId);
                }
            });
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
            this.map.on('draw:editvertex1', (e) => {
                console.log(e);
                if (e.poly.layerName == "vectors" && vector) {
                    let vector = app.editableLayer.getLayers().filter(f => f.feature.properties.id == e.poly.feature.properties.id)[0];
                    let vectorCoordinates = vector.getLatLngs();
                    let nodeBeginCoordinates = vector.feature.properties.nodeBegin.geom.coordinates;
                    nodeBeginCoordinates = [nodeBeginCoordinates[1], nodeBeginCoordinates[0]];
                    let nodeEndCoordinates = vector.feature.properties.nodeEnd.geom.coordinates;
                    nodeEndCoordinates = [nodeEndCoordinates[1], nodeEndCoordinates[0]]
                    if (!vectorCoordinates[0].equals(nodeBeginCoordinates)) {
                        vectorCoordinates[0] = L.latLng(nodeBeginCoordinates);
                        vector.setLatLngs(vectorCoordinates);
                    }
                    if (!vectorCoordinates[vectorCoordinates.length - 1].equals(nodeEndCoordinates)) {
                        vectorCoordinates[vectorCoordinates.length - 1] = L.latLng(nodeEndCoordinates);
                        vector.setLatLngs(vectorCoordinates);
                    }
                }
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
        verifyVectorForm() {
            let error = [];
            if (this.layers.readers.counterSelected() != 2)
                error.push('Выделите 2 считывателя');
            let fields = Object.entries(this.formVector).filter(l => l[1] == null).map(l => l[0]);
            if (fields.length)
                error.push(`Не заполнены поля: ${fields}`);
            let sel = this.layers.readers.getSelected().map(l => l.feature.properties.num);
            let und = sel.filter(el => el == undefined)
            if (und.length > 0)
                error.push('Не у всех считывателей задан номер');
            let vec = this.layers.vectors
                .getLayers()
                .map(l => [l.feature.properties.nodeBegin.num, l.feature.properties.nodeEnd.num].toString());
            let selVec = vec.includes(sel.sort().toString());
            if (selVec)
                error.push(`Уже есть вектор для считывателей: ${sel}`);
            return error;
        },
        verifyNewVectors() {
        },
        createVector: function () {
            let error = this.verifyVectorForm();
            if (!error.length) {
                let c = this.layers.readers.getSelected(this.formVector.reverse);
                let coor = this.layers.readers.getSelectedYX();
                let nodes = this.formVector.reverse ?
                    this.layers.readers.getSelected().reverse() :
                    this.layers.readers.getSelected();
                let pl = L.polyline(this.formVector.reverse ? coor.reverse() : coor);
                let geo = pl.toGeoJSON();
                geo.properties.isEnabled = true;
                geo.properties.state = 'created';
                geo.properties.comment = this.formVector.name;
                geo.properties.length = this.formVector.len;
                geo.properties.nodeBegin = Geometry.create(nodes[0].feature).model;
                geo.properties.nodeEnd = Geometry.create(nodes[1].feature).model;
                geo.properties.readerBeginId = nodes[0].feature.properties.id;
                geo.properties.readerEndId = nodes[1].feature.properties.id;
                this.layers.vectors.addData(geo);
                this.layers.readers.clearSelection();
                this.vectorError = null;
                this.newVectors = this.getNewVectors();
            }
            else {
                this.vectorError = error;
            }
        },
        removeGuide(latLng) {
            let point = this.map.latLngToLayerPoint(latLng);
            let strPoint = `${point.x}px, ${point.y}px`;
            let guide = document.querySelector(`div[style*='${strPoint}']`);
            if (guide) {
                guide.remove();
            }
        },
        saveCreatedVectors() {
            let dbVec = new Layer();
            let newVectors = this.getNewVectors();
            for (let v of newVectors) {
                delete v.lay.feature.properties.nodeBegin;
                delete v.lay.feature.properties.nodeEnd;
                dbVec.addData(v.lay.toGeoJSON());
            }
            function responseFill(res) {
                console.log('Сохранение выполнено успешно, векторы загружены');
                if (res.status == 200)//OK
                    app.newVectors = app.getNewVectors();
            }
            function responsePost(res) {
                console.log(res);
                if (res.status == 201) //Created
                    app.layers.vectors.fill(uri + 'vectors', responseFill);
            }
            dbVec.post(uri + 'vectors', responsePost);
        },
        saveEditedVectors() {
            let dbVec = new Layer();
            let newVectors = this.layers
                .vectors.getLayers()
                .filter(l => l.edited == true);
            for (let v of newVectors) {
                delete v.feature.properties.nodeBegin;
                delete v.feature.properties.nodeEnd;
                dbVec.addData(v.toGeoJSON());
            }
            function responseFill(res) {
                console.log('Сохранение отредактированных векторов выполнено успешно, векторы загружены');
                if (res.status == 200)//OK
                    app.newVectors = app.getNewVectors();
            }
            function responsePost(res) {
                console.log(res);
                if (res.status == 201) //Created
                    app.layers.vectors.fill(uri + 'vectors', responseFill);
            }
            dbVec.put(uri + 'vectors', responsePost);
        },
        saveEditedReaders() {
            let dbReaders = new Layer();
            let newReaders = this.layers
                .readers.getLayers()
                .filter(l => l.edited == true);
            for (let v of newReaders) {
                delete v.feature.properties.nodeBegin;
                delete v.feature.properties.nodeEnd;
                dbReaders.addData(v.toGeoJSON());
            }
            function responseFill(res) {
                console.log('Сохранение отредактированных считывателей выполнено успешно, считыватели загружены');
                if (res.status == 200) {//OK
                    app.newReaders = [];
                }
            }
            function responsePut(res) {
                console.log(res);
                if (res.status == 201) //Created
                    app.layers.readers.fill(uri + 'readers', responseFill);
            }
            dbReaders.put(uri + 'readers', responsePut);
        },
        removeVector: function (index) {
            this.layers.vectors.removeLayer(this.newVectors[index].lay);
            this.newVectors.splice(index, 1);
            this.newVectors = this.getNewVectors();
        },
        getNewVectors: function () {
            let i = 0;
            let ret = this.layers.vectors.getLayers()
                .filter(el => el.feature.properties.state === 'created')
                .map(l => {
                    return { id: i++, lay: l };
                });
            return ret;
        },
        verifyReaderNum(num) {
            let readers = this.layers.readers
                .getLayers()
                .map(el => el.feature.properties.num);
            return readers.includes(num);
        },
        getLevelBounds() {
            let bounds = this.layers.levels.getLayers().map(el => {
                return {
                    bound: el.getBounds(),
                    name: `${el.feature.properties.comment} [${el.feature.properties.heigth}]`,
                    color: el.feature.properties.color
                };
            });
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
        positioning(data) {
            let currentLocators =
                app.layers.locators
                    .getLayers()
                    .map(el => el.feature.properties);
            let uniq = [];
            uniq = data.filter(el => !currentLocators.some(l => l.hash == el.hash));
            let removedLayres = app.layers.locators.filter(el =>
                uniq.some(l =>
                    l.staffFullName === el.feature.properties.staffFullName));
            let removedChemical =
                app.layers.chemical.filter(el =>
                    uniq.some(l => l.staffFullName === el.feature.properties.staffFullName));
            for (let l of removedLayres) {
                app.layers.locators.removeLayer(l);
            }
            for (let l of removedChemical) {
                app.layers.chemical.removeLayer(l);
            }
            let gasRegex = /.*Г[АС].*/;
            let lsels = uniq.filter(el => el.lselIp && !gasRegex.test(el.comment));
            let lsrs = uniq.filter(el => !el.lselIp && !gasRegex.test(el.comment));
            let analyzers = uniq.filter(el => gasRegex.test(el.comment));
            for (let el of lsels) {
                let lsel = app.layers.readers
                    .filter(l => l.feature.properties.ip == el.lselIp)[0];
                if (lsel) {
                    let lselPoint = lsel.toGeoJSON();
                    let point = lselPoint.geometry.coordinates;
                    let deltaX = Math.floor(Math.random() * 10);
                    let deltaY = Math.floor(Math.random() * 10);
                    let newPoint = [point[0] - 5 + deltaX, point[1] - 5 + deltaY];
                    lselPoint.geometry.coordinates = newPoint;
                    lselPoint.properties = el;
                    app.layers.locators.addData(lselPoint);
                }
            }
            for (let el of lsrs) {
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
            app.statisticsUpdate();
        },
        initHub() {
            this.hubConnection = new signalR.HubConnectionBuilder()
                .withUrl(this.hubUrl)
                .configureLogging(signalR.LogLevel.Information)
                .build();
            this.hubConnection.on("Chemical", function (data) {
                console.log(data);
            });
            this.hubConnection.on("Messages", function (data) {
                data.message = app.transformMessage(data.message);
                app.hubDataMessages = data;
            });
            this.hubConnection.on("DevicesStatus", function (data) {
                let stat = data;
                if (['LSR', 'LSEL', 'BKRO', 'LSER'].includes(stat.type)) {
                    app.setReaderStatus(stat.name, stat.state);
                }
                if (stat.type == 'MAUP') {
                    console.log(`Type MAUP ${stat.name} Статус: ${stat.state}`)
                }
            });
            this.hubConnection.on("HardwareStatus", function (data) {
                app.hubDataMessages = {
                    message: `Запуск оборудования: ${data}`,
                    type: 'alert alert-info'
                };
                app.hardwareOnOff.on = data;
            });
            this.hubConnection.onclose(async () => {
                await app.hubRestart();
            });
            this.hubConnection.on("LocatorPositioning", this.positioning);
            this.hubConnection.on("AlarmConfirm", function (data) {
                let locator = app.layers.locators
                    .getLayers()
                    .filter(p => p.feature.properties.locatorNum == data.locatorNum)[0];
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
                    app.alarm.mode = true;
                }
            });
            this.hubConnection.on("AlarmStatus", function (data) {
                console.log(`AlarmStatus: ` + data);
                app.hubDataMessages = {
                    message: `Статус аварийного оповещения: ${data}`,
                    type: 'alert alert-info'
                };
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
                    app.hubConnection.invoke('OnOff', null);
                    app.hubConnection.invoke("AlarmStatus");
                })
                .catch(error => console.error(error.toString()));
        },
        disableEdited() {
            if (this.layers) {
                let editLayers = Object.entries(this.layers)
                    .filter(el => ['readers', 'vectors', 'levels'].indexOf(el[0]) !== -1);
                for (let lay of editLayers) {
                    lay[1].disableEdit();
                }
            }
        },
        editLayer(layerName, action) {
            let layer = Object.entries(this.layers)
                .filter(el => el[1].name == layerName);
            if (layer[0][1] instanceof Layer) {
                if (action) { //Если редактирование - true
                    this.editableLayer.clearLayers();
                    layer[0][1].eachLayer(l => {
                        l.layerName = layer[0][0];
                        this.editableLayer.addLayer(l);
                    });
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
        cancelEditLayer() {
            this.editTool.editHandler.revertLayers();
            this.editTool.editHandler.disable();
            this.currentLayer.state = false;
            let editReaders = this.editableLayer.getLayers().some(l => l.layerName == 'readers');
            if (editReaders) {
                this.layers.vectors.fill(uri + 'vectors');
            }
            this.editableLayer.clearLayers();
        },
        syncVectors(reader) {
            let vectors =
                app.layers.vectors
                    .filter(v =>
                        v.feature.properties.readerBeginId == reader.feature.properties.id ||
                        v.feature.properties.readerEndId == reader.feature.properties.id);
            let pnt = reader.getLatLng();
            for (let vect of vectors) {
                let vectCoord = vect.getLatLngs();
                if (vect.feature.properties.readerBeginId == reader.feature.properties.id) {
                    vectCoord[0] = pnt;
                }
                else {
                    vectCoord[vectCoord.length - 1] = pnt;
                }
                vect.setLatLngs(vectCoord);
            }
        },
        setReaderStatus(readerNum, status) {
            if (globalOptions.mode == "view") {
                let reader = null;
                if (/\d+\.\d+\.\d+\.\d+/.test(readerNum)) {
                    reader = this.layers.readers.getLayers()
                        .filter(l => l.feature.properties.ip == readerNum);
                } else {
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
        flyToLocator(vectorId, procentPosition) {
            let mark = this.interpolate(vectorId, procentPosition, `Вектор ${vectorId}`);
            mark.addTo(this.map);
        },
        interpolate(vectorId, procentPosition, popupContent = "") {
            let vec = this.layers.vectors
                .getLayers()
                .filter(v => v.feature.properties.id === vectorId);
            let ll = L.GeometryUtil.interpolateOnLine(this.map, vec[0], procentPosition);
            return L.marker(ll.latLng).bindPopup(popupContent);
        },
        statisticsUpdate() {
            this.statistics.staffInMine =
                this.layers.locators
                    .filter(l => !l.feature.properties.lselIp)
                    .map(l => l.feature.properties);
            this.statistics.staffInLsel = this.layers.locators
                .filter(l => l.feature.properties.lselIp)
                .map(l => l.feature.properties);
            if (this.statistics.isLsel) {
                this.statistics.staffList = this.statistics.staffInLsel;
            } else {
                this.statistics.staffList = this.statistics.staffInMine;
            }
            this.statistics.old = app.layers.locators
                .filter(l => l.feature.properties.old)
                .length;
            this.statistics.all = this.layers.locators.getLayers().length;
            if (this.statistics.vectors.length != this.layers.vectors.getLayers().length) {
                this.statistics.vectors = this.layers.vectors
                    .getLayers()
                    .map(l => l.feature.properties);
            }
        },
        transformMessage(message) {
            let ret = '';
            if (/MaupId.*LSEL'/i.test(message))
                return message.match(/MaupId.*LSEL'/g)[0];
            return message;
        },
        alarmOff() {
            this.hubConnection.invoke('AlarmOff', true);
        },
        staffIds(staffs, funcThen = null) {
            let queryString = staffs.join('|');
            axios
                .get(baseUrl + `api/staffs/ids/list/${queryString}`)
                .then(function (response) {
                    typeof funcThen === 'function' && funcThen(response);
                })
                .catch(function (error) {
                    console.log(error);
                });
        },
        personalAlarm(staffs, funcThen = null) {
            this.staffIds(staffs, (response) => {
                let ids =
                    response.data
                        .map(e => e.id);
                let alarmModel = {
                    "timeStart": (new Date().toISOString()),
                    "type": "personal",
                    "deviceType": 16,
                    "comment": `${staffs}`,
                    "isEnabled": true,
                    "isDeleted": false
                };
                axios
                    .post(baseUrl + `api/alarms/${ids[0]}`, alarmModel)
                    .then(function (response) {
                        console.log(response);
                        typeof funcThen === 'function' && funcThen(response);
                    })
                    .catch(function (error) {
                        console.log(error);
                    });
            });
        },
        allStaffsUpdate() {
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
