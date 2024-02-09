

function showMessage(message, type = 'danger') {
    $.notify({
        message: message
    }, {
        element: 'body',
        placement: {
            from: "top",
            align: "right"
        },
        allow_duplicates: false,
        showAnimation: 'slideDown',
        showDuration: 400,
        hideAnimation: 'slideUp',
        hideDuration: 200,
        gap: 2,
        offset: 50,
        spacing: 10,
        z_index: 1051,
        delay: 3000,
        type: type
    });
}                                               


window.onload = function () {
localStorage.setItem('works', 0);
localStorage.setItem('back',0);
localStorage.setItem('datein', 0);
localStorage.setItem('driverId', 0);
localStorage.setItem('isShowPopup',0);
if (localStorage.getItem('worked')==1) {
showMessage("Сохранены последние настройки окон", "success");
//showZakazchiki();
} else {showMessage("Вы успешно вошли в систему Гаражи", "info");}
}


function getAccessGarageAJAX(item, title){
    $.ajax(
    {
    type: 'post',
    url: 'garage/get-access-garage',
    dataType: 'json',
    data: {
    'id': item
    },
    success: function(data) {
    localStorage.setItem('user_id', data[0].user_id);
    localStorage.setItem('AccessGarage', data[0].Status);
    if (localStorage.getItem('AccessGarage') == 1) {
	showMessage("Выбран гараж: " + title, "info");
	document.getElementById("test1").innerHTML=title;
	$("#middle-panel-garage").css("display","none");
	$("#middle-panel-zakaz").fadeIn("slow");
	$("#middle-panel-zakaz").css("display", "block");
	localStorage.setItem('AccessRead', data[0].Read);
	localStorage.setItem('AccessWrite', data[0].Write);
	localStorage.setItem('garage_id', data[0].garage_id);
	} else {showMessage("У вас не прав на просмотр данных этого гаража","danger");
	}
    },
    'error': function (jqXHR, exception) {
            console.log("Сервис временно не доступен.");
            showMessage("Сервис временно не доступен.", "danger");
            if (jqXHR.status === 0) {
                console.error('Запрос отменен или прерван');
            } else if (jqXHR.status == 404) {
                console.error('НЕ найдена страница запроса [404])');
            } else if (jqXHR.status == 500) {
                console.error('Внутренняя ошибка сервера [500].\n' + jqXHR.responseText);
            } else if (exception === 'parsererror') {
                console.error("Ошибка парсинга: \n" + jqXHR.responseText);
            } else if (exception === 'timeout') {
                console.error('Сервер не ответил на запрос.');
            } else if (exception === 'abort') {
                console.error('Прерван запрос Ajax.');
            } else {
                console.error('Неизвестная ошибка:\n' + jqXHR.responseText);
            }
        }
});
}

Array.from(document.getElementsByClassName('colorful-layout-garage')).forEach(item => {
                    $('#' + item.id).on("click", function() {
			
			let title = document.getElementById('name-'+item.id).innerHTML;
			localStorage.setItem('back', 1);
			//console.log(item.id.replace('garage',''));
			getAccessGarageAJAX(item.id.replace('garage',''), title);
			
			});
			});




function getAccessCompanyAJAX(item, title){
    $.ajax(
    {
    type: 'post',
    url: 'garage/get-access-company',
    dataType: 'json',
    data: {
    'id': item
    },
    success: function(data) {
    localStorage.setItem('AccessCompany', data[0].Status);
    if (localStorage.getItem('AccessCompany') == 1) {
	showMessage("Выбрано подразделение: " + title, "info");
	document.getElementById("test1").innerHTML=title;
	//$("#middle-panel-garage").css("display","none");
	//$("#middle-panel-zakaz").fadeIn("slow");
	//$("#middle-panel-zakaz").css("display", "block");
	//localStorage.setItem('AccessRead', data[0].Read);
	//localStorage.setItem('AccessWrite', data[0].Write);
	//localStorage.setItem('garage_id', data[0].garage_id);
	} else {showMessage("У вас не прав на просмотр данных данного подразделения","danger");
	}
    },
    'error': function (jqXHR, exception) {
            console.log("Сервис временно не доступен.");
            showMessage("Сервис временно не доступен.", "danger");
            if (jqXHR.status === 0) {
                console.error('Запрос отменен или прерван');
            } else if (jqXHR.status == 404) {
                console.error('НЕ найдена страница запроса [404])');
            } else if (jqXHR.status == 500) {
                console.error('Внутренняя ошибка сервера [500].\n' + jqXHR.responseText);
            } else if (exception === 'parsererror') {
                console.error("Ошибка парсинга: \n" + jqXHR.responseText);
            } else if (exception === 'timeout') {
                console.error('Сервер не ответил на запрос.');
            } else if (exception === 'abort') {
                console.error('Прерван запрос Ajax.');
            } else {
                console.error('Неизвестная ошибка:\n' + jqXHR.responseText);
            }
        }
});
}



Array.from(document.getElementsByClassName('colorful-layout-zakaz')).forEach(item => {
                    $('#' + item.id).on("click", function() {
//	console.log(item.id);
	let title = document.getElementById('name-'+item.id).innerHTML;
	localStorage.setItem('comp', item.id.replace('comp',''));
	//getZakazchikAJAX();
//	console.log(title);
	showMessage("Выбрано: " + title, "info");
	document.getElementById("test1").innerHTML=title;
	localStorage.setItem('title', title);
	localStorage.setItem('worked', 1);
	//showZakazchiki();
	localStorage.setItem('back', 0);
	//getAccessCompanyAJAX(item.id.replace('comp',''));
	console.log("выбрано из меню "+localStorage.getItem("comp"));
	$('.content-li').css("display", "none");
	showZakazchiki(localStorage.getItem("comp"));
	//showUserFacets();
	//showBusyFacets();
	});
	});


function showZakazchiki(caption) {
$("#middle-panel-garage").css("display","none");
$("#middle-panel-zakaz").css("display", "none");
$("#middle-panel-auto").fadeIn("slow");
$("#middle-panel-auto").css("display", "flex");
document.getElementById("test1").innerHTML=localStorage.getItem('title');
//console.log(caption);
$("#tab-content").css("display", "none");
getZakazchikAJAX(caption);
}


function getZakazchikAJAX(caption){
    
    $.ajax(
    {
    type: 'post',
    url: 'garage/get-zakazchik',
    dataType: 'json',
    data: {
    'garage_id': localStorage.getItem('garage'),
//    'company_id': localStorage.getItem('comp'),
    'company_id': caption,  //company_id
    },
    success: function(data) {
    //console.log(data);
    //$("#allFacets").addClass("disabled");
    //$("#userFacets").addClass("disabled");
    
    ListDeparts(data);
    
    //$("#preload").addClass("hidden");
    //let errors = result.errors;
    //garage_id = data.id;
    
    },
    'error': function (jqXHR, exception) {
            console.log("Сервис временно не доступен.");
            showMessage("Сервис временно не доступен.", "danger");
            if (jqXHR.status === 0) {
                console.error('Запрос отменен или прерван');
            } else if (jqXHR.status == 404) {
                console.error('НЕ найдена страница запроса [404])');
            } else if (jqXHR.status == 500) {
                console.error('Внутренняя ошибка сервера [500].\n' + jqXHR.responseText);
            } else if (exception === 'parsererror') {
                console.error("Ошибка парсинга: \n" + jqXHR.responseText);
            } else if (exception === 'timeout') {
                console.error('Сервер не ответил на запрос.');
            } else if (exception === 'abort') {
                console.error('Прерван запрос Ajax.');
            } else {
                console.error('Неизвестная ошибка:\n' + jqXHR.responseText);
            }
        }
});
}

function ListDeparts(model) {

let n = model.length;
$("#tabs__caption").css("display", "flex");
$("#userFacets").css("display", "block");
//console.log(model[0].id);
str='';

for (let i=0; i<n; i++) {
str +='<li class="li-zakaz tab-all" data-id='+model[i].id+' id="zakaz'+model[i].id+'" >'+model[i].name+'</li>';
}
document.getElementById('tabs__caption').innerHTML = str;
//console.log(localStorage.getItem("tab_id"));
// comp - id company 1,2,3,4,5
if (localStorage.getItem("fromTabs")==0) {
$("#zakaz"+localStorage.getItem('activeTab')).addClass("active").siblings().removeClass("active");}
$("#tab-content").css("display", "block");
getAutosAJAX();
//localStorage.setItem('tab_id', model[0].id);
//showAllAuto();
//showBusyAuto();

}


    //showDepartsAuto(data);
//    showAllAutos(data);
//    showBusyAutos(data);


function getAutosAJAX(){
    $.ajax(
    {
    type: 'post',
    url: 'garage/get-autos',
    dataType: 'json',
    data: {
    },
    success: function(data) {
    showDepartsAuto(data);
    showAllAutos(data);
    showBusyAutos(data);
    
if (localStorage.getItem('AccessWrite')==0 && (localStorage.getItem('AccessRead')==0)) {
    showMessage("Права только на просмотр ТС","danger");
    $("#allFacets").addClass("disabled");
    $("#userFacets").addClass("disabled");}

if (localStorage.getItem('AccessWrite')==0 && (localStorage.getItem('AccessRead')==1)) {
    showMessage("Права только на передачу техники в общий список ТС","info");
    $("#allFacets").addClass("disabled");
    $("#userFacets").removeClass("disabled");}
    
if (localStorage.getItem('AccessWrite')==1  && (localStorage.getItem('AccessRead')==0)) {
    showMessage("Права только на передачу техники из общего списка ТС","info");
    $("#allFacets").removeClass("disabled");
    $("#userFacets").addClass("disabled");}

if (localStorage.getItem('AccessWrite')==1  && (localStorage.getItem('AccessRead')==1)) {
    showMessage("У вас все права на распоряжение ТС","info");
    $("#allFacets").removeClass("disabled");
    $("#userFacets").removeClass("disabled");
    $("#busyFacets").removeClass("disabled");
    
    }
    //listAutos(data, tab_id);
    //console.log(data);
    //console.log(tab_id);
    //$("#preload").addClass("hidden");
    //let errors = result.errors;
//    garage_id = data.id;
    
    },
    'error': function (jqXHR, exception) {
            console.log("Сервис временно не доступен.");
            showMessage("Сервис временно не доступен.", "danger");
            if (jqXHR.status === 0) {
                console.error('Запрос отменен или прерван');
            } else if (jqXHR.status == 404) {
                console.error('НЕ найдена страница запроса [404])');
            } else if (jqXHR.status == 500) {
                console.error('Внутренняя ошибка сервера [500].\n' + jqXHR.responseText);
            } else if (exception === 'parsererror') {
                console.error("Ошибка парсинга: \n" + jqXHR.responseText);
            } else if (exception === 'timeout') {
                console.error('Сервер не ответил на запрос.');
            } else if (exception === 'abort') {
                console.error('Прерван запрос Ajax.');
            } else {
                console.error('Неизвестная ошибка:\n' + jqXHR.responseText);
            }
        }
});
//			console.log(data);
}

function showDepartsAuto(model)
{
//$('.facet-user-list').remove();
$('.facet-user').remove();
tab_current = 1;

//getAutosAJAX(model);
//let tab_id = localStorage.getItem('tab_id');
//let active_tab = localStorage.getItem('activeComp');
//console.log("fromtabs " + localStorage.getItem("fromTabs")+"tab_id: " + tab_id + " active: " + active_tab);
if (localStorage.getItem("fromTabs")==1) 
    {
//    tab_current = 0;
    tab_current = localStorage.getItem('activeComp');
//    console.log("from tabs");
    
    
    } else {
//    tab_current = 0;
    tab_current = localStorage.getItem('activeTab');
//    console.log("from busy");
    }
console.log("в итоге " + tab_current);
localStorage.setItem('tab_id', tab_current);
let n = model.length;
if (n<1) {

}
str=''
for (let i=0; i<n; i++) {
if (model[i].work_status==tab_current) {

$('#userFacets').append(''+
'<li class="facet-user" data-id='+model[i].id+'>\n'+
    '<div class="row">\n'+
	'<div id="userFacetsCard" class="card">\n'+
	    '<div class="card-info">\n'+
        	    '<img src="/img/auto/'+model[i].img_path+'" class="imageFlex">\n'+
            '</div>\n'+
		'<div class="card-text">\n'+
    	        '<div class = "card-text-title">'+model[i].name+'\n'+
		'</div>\n'+
		'<div class = "card-text-main">г/н '+model[i].gosnomer+'\n'+
		'</div>\n'+
	    '</div>\n'+
	'</div>\n'+
    '</div>\n'+
'</li>');
}
}

}

//$("#tabs").on("click", "li", function() {
//let autos=$(this).attr('data-id');
//    localStorage.setItem('tab_id', autos);
//    console.log(autos);
//
//    getAutosAJAX(autos);
//});


$("ul.tabs__caption").on("click", "li:not(.active)", function() {
    $('.content-li').css("display", "block");
    //$('.facet-user').remove();
    //$(".facet-list-user").remove("facet-all");
    //$('#content-li').add("<div></div>").addClass("facet-user-list");
    $(this).addClass("active").siblings().removeClass("active");
    $("#tab-content").css("display", "block");
    //console.log($(this).attr('data-id')+ " tab");
    let autos=$(this).attr('data-id');
    //localStorage.setItem('tab_id', autos);
    localStorage.setItem('activeComp', autos);
    localStorage.setItem('activeCompName', $("#zakaz"+autos).html());
    console.log("для сменного задания id Заказчика: "+ autos);
    //localStorage.setItem('comp', autos);
//    console.log(localStorage.getItem('activeComp') + ' активный department через tabs');
    getAutosAJAX();
    showMessage("Выбран заказчик: " + $("#zakaz"+autos).html(), "warning");
    localStorage.setItem("fromTabs", 1);
});

function showAllAutos(model) {
let n = model.length;
str=''
$('.facet-all').remove();
for (let i=0; i<n; i++) {
if (model[i].work_status==0) {
$('#allFacets').append(''+
'<li class="facet-all" data-id='+model[i].id+'>\n'+
    '<div class="row">\n'+
	'<div id="userFacetsCard" class="card">\n'+
	    '<div class="card-info">\n'+
        	    '<img src="/img/auto/'+model[i].img_path+'" class="imageFlex">\n'+
            '</div>\n'+
		'<div class="card-text">\n'+
    	        '<div class = "card-text-title">'+model[i].name+'\n'+
		'</div>\n'+
		'<div class = "card-text-main">г/н '+model[i].gosnomer+'\n'+
		'</div>\n'+
	    '</div>\n'+
	'</div>\n'+
    '</div>\n'+
'</li>');
}
}

}

function showBusyAutos(model) {
let n = model.length;
$('.facet-busy').remove();
str='';
for (let i=0; i<n; i++) {
if (model[i].work_status!==0) {
$('#busyFacets').append(''+
'<li class="facet-busy" data-id='+model[i].id+'>\n'+
    '<div class="row">\n'+
	'<div id="userFacetsCard" class="card">\n'+
	    '<div class="card-info">\n'+
        	    '<img src="/img/auto/'+model[i].img_path+'" class="imageFlex">\n'+
            '</div>\n'+
		'<div class="card-text">\n'+
    	        '<div class = "card-text-title">'+model[i].name+'\n'+
		'</div>\n'+
		'<div class = "card-text-main">г/н '+model[i].gosnomer+'\n'+
		'</div>\n'+
	    '</div>\n'+
	'</div>\n'+
    '</div>\n'+
'</li>');
}
}
}

function getServerDate() {
    $.ajax(
    {
    type: 'post',
    url: 'admin/get-date',
    success: function(data) {
    localStorage.setItem('date_end', data);
    },
    'error': function (jqXHR, exception) {
            console.log("Сервис временно не доступен.");
            if (jqXHR.status === 0) {
                console.error('Запрос отменен или прерван');
            } else if (jqXHR.status == 404) {
                console.error('НЕ найдена страница запроса [404])');
            } else if (jqXHR.status == 500) {
                console.error('Внутренняя ошибка сервера [500].\n' + jqXHR.responseText);
            } else if (exception === 'parsererror') {
                console.error("Ошибка парсинга: \n" + jqXHR.responseText);
            } else if (exception === 'timeout') {
                console.error('Сервер не ответил на запрос.');
            } else if (exception === 'abort') {
                console.error('Прерван запрос Ajax.');
            } else {
                console.error('Неизвестная ошибка:\n' + jqXHR.responseText);
            }
        }
});
}


$("#userFacets, #allFacets").sortable({
    connectWith: "#userFacets, #allFacets",
    dropOnEmpty: true,
    cancel: ".disabled",
    revert: true,
    out: function(event, ui) {
    },
    start: function(event, ui) {
    showMessage("Выбрано транспортное средство: " + $(ui.item).text(), "warning");
    localStorage.setItem('autoToChange', ui.item.attr('data-id'));
    getServerDate();
    console.log(localStorage.getItem('autoToChange'));
    },
    stop: function(event, ui) {
    //console.log($(event.target).closest("#allFacets").length);
        var sortuser =[]
        $(".facet-list-user li").each(function(){
        sortuser.push($(this).data('id'));
        $(".facet-list-user li").removeClass("facet-all");
        $(".facet-list-user li").addClass("facet-user");
	});
    console.log(sortuser);
    localStorage.setItem('userFacets', sortuser);
	var sortall =[]
	$(".facet-list li").each(function(){
	sortall.push($(this).data('id'));
	$(".facet-list li").removeClass("facet-user");
	$(".facet-list li").addClass("facet-all");
        });
    //console.log(sortall);
    localStorage.setItem('allFacets', sortall);

    //saveAutosAJAX();
    
    localStorage.setItem('worked', 1);
    //console.log(event.target.closest("ul#allFacets"));
    if ($(event.target).closest("ul#allFacets").length) {
    
    showPopup();
    }
    if ($(event.target).closest("ul#userFacets").length) {
    saveAutosAJAX();
    //добавить попап на потдверждение
    getAutosAJAX(localStorage.getItem('tab_id'));
    }
    
    }
    }).disableSelection();


function disableRooms () {
$("#allFacets").addClass("disabled");
$("#userFacets").addClass("disabled");
$("#busyFacets").addClass("disabled");
}

function enableRooms () {
$("#allFacets").removeClass("disabled");
$("#userFacets").removeClass("disabled");
$("#busyFacets").removeClass("disabled");
}

function showPopup() {
$(".dialog").css("display","block");
localStorage.setItem('isShowPopup', 1);
localStorage.setItem('driverChanged', 0);
document.getElementById('current-zakazchik').innerHTML = localStorage.getItem('activeCompName');
disableRooms();
}



$("#closeDialog").on("click", function() {
$(".dialog").css("display","none");
if (localStorage.getItem('isShowPopup')==1) {
    showMessage("Данные о ТС не сохранены.", "danger");
    getAutosAJAX();
    enableRooms();
    }
});

//$(document).click(function(event) {
//if (!$(event.target).closest(".dialog").length) {
//    $("body").find(".dialog").css("display","none");
//    if (localStorage.getItem('isShowPopup')==1) {
//    showMessage("Данные о ТС не сохранены.", "danger");
//    getAutosAJAX();
//    enableRooms();
//    }
//    }
//});


function saveAutosAJAX(){
    $.ajax(
    {
    type: 'post',
    url: 'garage/save-autos',
    dataType: 'json',
    data: {
    'tab_id': localStorage.getItem('tab_id'),
    'userFacets': localStorage.getItem('userFacets'),
    'autoToChange': localStorage.getItem('autoToChange'),
    'allFacets': localStorage.getItem('allFacets'),
    'status_id': null,
    'date_begin':localStorage.getItem('datein'),
    'date_end': localStorage.getItem('date_end'),
    'garage_id': localStorage.getItem('garage_id'),
    'zakazchik_id': localStorage.getItem('activeComp'),
    'driver_id':localStorage.getItem('driverId'),
    'user_id': localStorage.getItem('user_id'),
    'worksTypes': localStorage.getItem('typesworks'),
    },
    success: function(data) {
    console.log(data);
    localStorage.setItem('isShowPopup', 0);
    showMessage("Данные о ТС переданы.", "success");
    getAutosAJAX();
    },
    'error': function (jqXHR, exception) {
            console.log("Сервис временно не доступен.");
            if (jqXHR.status === 0) {
                console.error('Запрос отменен или прерван');
            } else if (jqXHR.status == 404) {
                console.error('НЕ найдена страница запроса [404])');
            } else if (jqXHR.status == 500) {
                console.error('Внутренняя ошибка сервера [500].\n' + jqXHR.responseText);
            } else if (exception === 'parsererror') {
                console.error("Ошибка парсинга: \n" + jqXHR.responseText);
            } else if (exception === 'timeout') {
                console.error('Сервер не ответил на запрос.');
            } else if (exception === 'abort') {
                console.error('Прерван запрос Ajax.');
            } else {
                console.error('Неизвестная ошибка:\n' + jqXHR.responseText);
            }
        }
});
//			console.log(data);
}

$("#saveAutos").on("click", function() {
//console.log(localStorage.getItem('allFacets'));
//console.log(localStorage.getItem('userFacets'));
//console.log(localStorage.getItem('tab_id'));
console.log('количество работ:' + localStorage.getItem('works'));
if (localStorage.getItem('works')>0) {
    if (localStorage.getItem('driverId')>0) {
	console.log('Итого в сменный лист: ID Водителя: ' + localStorage.getItem('driverId') + ', Заказчик: ' +localStorage.getItem('activeComp') + ', ID заданий:' +localStorage.getItem('typesworks') + ', время записи в журнал:' + localStorage.getItem('datein') + ', автор задания: ' + localStorage.getItem('user_id'));
	saveAutosAJAX();
	localStorage.setItem('driverChanged', 0);
	showMessage("Данные сохранены","success");
	localStorage.setItem('worked', 0);
	localStorage.setItem('back',0);
	$("#dialog").toggleClass("toggle");
	//getAutosAJAX(localStorage.getItem('tab_id'));
	//$('.dialog').remove();
	$(".dialog").css("display","none");
	//$('#driver-select').val(null).trigger('change');
	enableRooms();
	} else {showMessage("Не выбран ни один водитель!","danger");}
	} else {showMessage("Добавьте хотя бы одно сменное задание!","danger");}

});


$("#busyFacets").on("click","li", function() {
//$("#tabs").css("display", "none");
//$('.facet-user').remove();
$("#tabs__caption").css("display", "none");
$("#userFacets").css("display", "none");
let autoSelected=$(this).attr('data-id');
localStorage.setItem('autoSelected', autoSelected),
//console.log(autoSelected + "auto selected");
getZakazchikAutoAJAX(autoSelected);
$("#tab-content").css("display", "block");
//getZakazchikAJAX(localStorage.getItem('tab_id'));
//
//getAutosAJAX(localStorage.getItem('activeComp'));
//localStorage.setItem('comp', localStorage.getItem('activeComp'));
//getZakazchikAJAX();
//getAutosAJAX(localStorage.getItem('comp'));
localStorage.setItem("fromTabs", 0);
});


function getZakazchikAutoAJAX(autoSelected){
    
    $.ajax(
    {
    type: 'post',
    url: 'garage/get-zakazchik-auto',
    dataType: 'json',
    data: {
    'auto_id': autoSelected,
    },
    success: function(data) {
    //console.log(data);
    //localStorage.setItem('comp', data[0].company_id);
    //localStorage.setItem('tab_id', data[0].id);
    
    localStorage.setItem('activeComp', data[0].company_id);
    localStorage.setItem('activeTab', data[0].id);
    //console.log(localStorage.getItem('activeTab') + ' активный department из auto');
    //console.log(localStorage.getItem('activeComp') + ' #company');
    showZakazchiki(data[0].company_id);
    setActiveTab(localStorage.getItem('activeComp'));
    },
    'error': function (jqXHR, exception) {
            console.log("Сервис временно не доступен.");
            showMessage("Сервис временно не доступен.", "danger");
            if (jqXHR.status === 0) {
                console.error('Запрос отменен или прерван');
            } else if (jqXHR.status == 404) {
                console.error('НЕ найдена страница запроса [404])');
            } else if (jqXHR.status == 500) {
                console.error('Внутренняя ошибка сервера [500].\n' + jqXHR.responseText);
            } else if (exception === 'parsererror') {
                console.error("Ошибка парсинга: \n" + jqXHR.responseText);
            } else if (exception === 'timeout') {
                console.error('Сервер не ответил на запрос.');
            } else if (exception === 'abort') {
                console.error('Прерван запрос Ajax.');
            } else {
                console.error('Неизвестная ошибка:\n' + jqXHR.responseText);
            }
        }
});
}

function setActiveTab(tab) {
//console.log("active tab: "+ tab);
//$("#zakaz"+tab).addClass("active").siblings().removeClass("active");
//$("body").find("#zakaz"+tab).addClass("active");
}



$("#back").on("click", function() {

//localStorage.setItem('back',0);

if (localStorage.getItem('back')==0) {
window.location = '/';
localStorage.clear();
}
if (localStorage.getItem('back')==1) { 
console.log("Вернулись на управление гаражами");
$("#middle-panel-zakaz").css("display", "none");
$("#middle-panel-auto").css("display", "none");
$("#middle-panel-garage").css("display","");
//document.getElementById("test1").innerHTML="Управление гаражами";
localStorage.setItem('back',0);}

if (localStorage.getItem('back')==2) { 
//console.log("Вернулись на подразделения");
$("#middle-panel-zakaz").fadeIn("slow");
$("#middle-panel-zakaz").css("display", "block");
$("#middle-panel-auto").css("display", "none");
$("#middle-panel-garage").css("display","none");
$('.facet-user').remove();
$("#dialog").css("display","none");
//document.getElementById("test1").innerHTML="Управление гаражами";
localStorage.setItem('back',0);} 
});
