let serverDateLocal = null;
let selectedType = null;
let selectedId = null;
let selectedDriver = null;
let selectedDriverId = null;
let selectedDriverStatus = null;
var arraytypes =[]
//localStorage.setItem('works', n);


function getdate() {
    $.ajax(
    {
    type: 'post',
    url: 'admin/get-date',
    success: function(data) {
    serverDateLocal = data;
    //console.log(serverDateLocal);
    
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


setInterval(() => {
getdate();
//document.querySelector("#datenow").innerHTML = serverDateLocal.toLocaleString("ru").replace(",","");
document.querySelector("#datenow").innerHTML = serverDateLocal;
localStorage.setItem('datein', serverDateLocal);
}, 1000);



function getdriversAJAX() {
$.ajax({
    type: 'post',
    url: 'garage/get-drivers',
    dataType: 'json',
    success: function(data) {
    console.log(data);
    $("#driver-select").empty();
    listDrivers(data);

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

function listDrivers(model) {

let n = model.length;
str='';
console.log("перерисовал");
for (let i=0; i<n; i++) {
str +='<option value='+model[i].id+'>'+model[i].fio+'</option>';
}
document.getElementById('driver-select').innerHTML = str;
localStorage.setItem('driverChanged', 1);
}



function listTypeWorks(model) {

let n = model.length;
str='';
for (let i=0; i<n; i++) {
str +='<option value='+model[i].id+'>'+model[i].name+'</option>';
}
document.getElementById('worktype-select').innerHTML = str;
}

$(document).ready(function() {
	$('#driver-select').select2({
		placeholder: "Выберите водителя",
		maximumSelectionLength: 2,
		language: "ru",
		templateSelection : function (data) {
		if (data.id ==='') {
		console.log(data); 
		getdriversAJAX();
		}
//		console.log(data.text);
		selectedDriver = data.text;
		selectedDriverId = data.id;
		selectedDriverStatus = data.status;
		console.log(selectedDriverStatus);
		return data.text;
		
		}
	});
});


$(document).ready(function() {
	$('#worktype-select').select2({
		placeholder: "Выберите вид работ",
		maximumSelectionLength: 2,
		language: "ru",
		templateSelection : function (data) {
		if (data.id ==='') {
		//console.log(data); 
		gettypeworksAJAX();}
//		console.log(data.text);
		selectedType = data.text;
		selectedId = data.id;
		
		return data.text;
		
		}
	});
});

function gettypeworksAJAX() {
    $.ajax(
    {
    type: 'post',
    url: 'garage/get-type-works',
    dataType: 'json',
    data: {},
    success: function(data) {
    //console.log(data);
    listTypeWorks(data);
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

let n=0;
$("#addWorks").on("click",  function() {
n+=1;
//document.getElementById('addedWorks').innerHTML = selectedType;
$('.addedworks').append('<div class="worktypeadded" data-id="'+selectedId+'"><span class="added-types-text">'+selectedType+'</span>\n' + 
'<div class="login-button2 remove-button"><span class="login-button2-text"></span>\n'+
'<div class="icon-zmdi zmdi zmdi-minus"></div></div></div>');

//$(".worktypeadded div").each(function(){
arraytypes.push(selectedId);

localStorage.setItem('driverId',selectedDriverId);
localStorage.setItem('works', n);
localStorage.setItem('typesworks', arraytypes);
console.log(arraytypes);
//});
});

$("div.addedworks").on("click", "div.worktypeadded", function() {
let type=$(this).attr('data-id');
n-=1;
//console.log(type);
index = arraytypes.indexOf(type);
if (index !==-1) {arraytypes.splice(index,1);}
$(this).remove();
console.log(arraytypes);
localStorage.setItem('works', n);
localStorage.setItem('typesworks', arraytypes);

});