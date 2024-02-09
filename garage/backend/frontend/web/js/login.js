localStorage.setItem('logged',0);
localStorage.setItem('worked', 0);

function checkSession() {
    $.ajax({
        'type': 'post',
        'url': 'site/check-session',
        'data': {},
        'error': function (err) {
            console.log(err.responseText);
        },
        'beforeSend': function () {
            // $('#preload').modal('show');
        },
        'success': function (result) {
            // console.warn(result);
            if (result !== "Сессия активна") {
                showMessage("Сессия не активна! Авторизуйтесь для продолжения работы!");
                console.error("Время сессии истекло! Авторизуйтесь для продолжения работы!");
                //window.vueProject.showLoginForm();
                // document.location.reload();
            }
             else {
                 showMessage("Сессия активна!", "info");
             }
        }
    })
}