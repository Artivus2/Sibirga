<a href="/reports/summary-report-employee-and-transport-zones"><div class="menu-item" id="menuL1">История местоположения персонала и транспорта</div></a>
<a href="/reports/summary-report-employee-forbidden-zones"><div class="menu-item" id="menuL2">Нахождение персонала в запрещенных зонах</div></a>
<a href="/reports/summary-report-time-spent"><div class="menu-item" id="menuL3">Время нахождения персонала по зонам</div></a>
<!--<a href="/summary-report-transport-history"><div class="menu-item" id="menuL4">История нахождения транспорта в шахте</div></a>-->
    <a href="/reports/summary-report-time-table-report"><div class="menu-item" id="menuL5">Табельный отчет</div></a>
<a href="/reports/summary-report-end-of-shift"><div class="menu-item" id="menuL6">Время выхода персонала по окончанию смены</div></a>
<!--    <a href="/summary-report-people-in-zones"><div class="menu-item" id="menuL7">Нахождение людей по забоям</div></a>-->
<!--<a href="/summary-report-gaz-concentration"><div class="menu-item" id="menuL8">Риски, связанные с концентрацией газов</div></a>-->
<a href="/reports/summary-report-motionless-people"><div class="menu-item" id="menuL9">Нахождение персонала без движения (события)</div></a>
<a href="/reports/summary-report-motionless-people-general"><div class="menu-item" id="menuL10">Нахождение персонала без движения (общее время)</div></a>
<a href="/reports/summary-report-excess-density-gas"><div class="menu-item" id="menuL11">Превышение концентрации газа</div></a>
<!--<a href="/pers-move-conveyors" class=""><div class="menu-item" id="menuL12">Нахождение персонала на движущихся конвейерах</div></a>-->

<script>
    /**
     * Функция инициализации календаря
     * @param dateStart - DOM-элемент, по клику на который нужно вызвать календарь для выбора даты начала фильтрации
     * @param dateFinish - DOM-элемент, по клику на который нужно вызвать календарь для выбора конечной даты фильтрации
     */
    function initializeCalendar(dateStart, dateFinish, timeFlag = true){
        console.warn(arguments);
        $.datetimepicker.setLocale("ru");                                                                                       //устанавливаем русскую локализацию для календаря
        // const dateStart = document.getElementById('date1'),
        //     dateFinish = document.getElementById('date2');
        $(dateStart).datetimepicker({
            format: "d.m.Y H:i",
            mask: true,
            step: 1,
            timepicker: timeFlag,
            defaultTime: '00:00',
            dayOfWeekStart: 1,
            onSelectTime: (dateTime) => {
                let d = new Date(dateTime);
                let date = d.getDate().toString().padStart(2, '0') + '.' + (d.getMonth() + 1).toString().padStart(2, '0') + '.' + d.getFullYear();
                let time = d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
                dateStart.textContent = date + ' ' + time
                //dateStart.textContent = dateTime.toLocaleString().replace(',', '').slice(0, 16);
            },
            onSelectDate: (dateTime) => {
                if (timeFlag === false) {
                    let date = new Date(dateTime);
                    dateStart.textContent = date.getDate().toString().padStart(2, '0') + '.' + (date.getMonth() + 1).toString().padStart(2, '0') + '.' + date.getFullYear();
                    // dateStart.textContent = dateTime.toLocaleDateString();
                }
            }
            // weeks: true
        });
        $(dateFinish).datetimepicker({
            format: "d.m.Y H:i",
            mask: true,
            step: 1,
            timepicker: timeFlag,
            dayOfWeekStart: 1,
            onSelectTime: (dateTime) => {
                let d = new Date(dateTime);
                let date = d.getDate().toString().padStart(2, '0') + '.' + (d.getMonth() + 1).toString().padStart(2, '0') + '.' + d.getFullYear();
                let time = d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
                dateFinish.textContent = date + ' ' + time;
            },
            onSelectDate: (dateTime) => {
                if (timeFlag === false) {
                    let date = new Date(dateTime);
                    dateFinish.textContent = date.getDate().toString().padStart(2, '0') + '.' + (date.getMonth() + 1).toString().padStart(2, '0') + '.' + date.getFullYear();
                }
            }
            // weeks: true
        });
    }
</script>