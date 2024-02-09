
function showMessage(message, type = 'danger') {
    $.notify({                                                                                          // Уведомление о действии
        message: message
    }, {
        element: 'body',
        placement: {
            from: "top",
            align: "right"
        },
        allow_duplicates: true,
        showAnimation: 'slideDown',
        showDuration: 4000,
        hideAnimation: 'slideUp',
        hideDuration: 2000,
        gap: 2,
        offset: 150,
        spacing: 10,
        z_index: 1051,
        delay: 3000,
        type: type
    });
}                                               

$("#garage").on("click", function() {

window.location = '/garage';

localStorage.setItem('logged', 1);
localStorage.setItem('worked', 0);
});


window.onload = function () {

if (localStorage.getItem('logged')==0) {
showMessage("Вы вошли в систему АвтоУправление", "success");
localStorage.setItem('logged', 1);
}

}


$('#jobs').on("click", function() {
window.location = '/jobs';
localStorage.setItem('logged', 1);
localStorage.setItem('worked', 0);
showMessage("Вы вошли в систему АвтоУправление", "success");
});


$('#reports').on("click", function() {
showMessage("Меню находится в стадии разработки", "info");
});

$('#stoptime').on("click", function() {
showMessage("Меню находится в стадии разработки", "info");
});

$('#repair').on("click", function() {

showMessage("Меню находится в стадии разработки", "info");
});

