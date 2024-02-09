<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;
use yii\web\View;

$this->title = "Книга предписаний";
?>
<div id="app">
    <div class="fill-injunction">
        <div class="navbar"></div>
        <div class="main">
            <div class="aside"></div>
            <div class="section">
                <div class="tabs"></div>
                <div class="table">
                    <div class="report-info">
                        <div></div>
                        <div>
                            <button @click="activeModal = 'modal', openModal($event)">show modal</button>
                        </div>
                        <div></div>
                    </div>
                    <div class="report-events"></div>
                </div>
            </div>
        </div>

        <div class="modal" id="modal" v-show="activeModal === 'modal'"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery"></script>
<script src="https://cdn.jsdelivr.net/npm/axios@0.18.0/dist/axios.min.js"></script>
<script>
</script>


<script>

    new Vue({
        el: '#app',
        props: {

        },
        data() {
            return {
                activeModal: null
            }
        },
        methods: {
            openModal(event) {
                console.log("clicked on btn modal")
                let dpd = document.getElementById('modal');

                dpd.style.top = event.target.getBoundingClientRect().top;
                dpd.style.left = event.target.getBoundingClientRect().left;

            }

        },
        created() {
            // sendAjax(config).then(res => {
            //     if(res.status) {
            //         console.log("Данные по предписанию №"+this.checkingID+" получены!");
            //         this.checking = {...res.Items};
            //     }
            // }).catch(err => console.log(err));
            axios.get('/read-manager-amicum?controller=industrial_safety\\Checking&method=GetInfoAboutInjunction&subscribe=&data={%22injunction_id%22:167}').then(res => {
                if(res.data.status) {
                    console.log("Данные по предписанию № 167 получены!");
                    console.info("Результат ответа по запросу: ", res.data);

                    this.checking = {...res.data.Items};
                } else {
                    console.error("Ошибка при получении данных!");
                }
            }).catch(err => console.log(err));
        }
    });
</script>

<!---->
<style scoped>
    #app {
        width: 100%;
        height: calc(100vh - 190px);
        border: 1px solid rosybrown;
        font-family: Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .fill-injunction {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .navbar {
        width: 100%;
        height: 40px;
    }

    .main {
        display: flex;
        width: 100%;
        height: calc(100% - 40px);
        border: 1px solid red;
    }

    .aside {
        width: 350px;
        height: 100%;
        border: 2px solid darksalmon;
    }

    .section {
        width: calc(100% - 350px);
        height: 100%;
        border: 2px solid darksalmon;
    }

    .tabs {
        width: 100%;
        height: 40px;
    }

    .table {
        width: 100%;
        height: calc(100% - 40px);
    }

    .report-info, .report-events {
        display: flex;
        width: 100%;
        height: 210px;
        border: 2px solid darksalmon;
    }
    .modal {
        position: absolute;
        top: 0;
        left: 0;
        width: 210px;
        height: 280px;
        border: 1px solid black;
    }

</style>
