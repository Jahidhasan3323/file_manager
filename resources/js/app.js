import Vue from 'vue';
import VueLazyload from 'vue-lazyload'
window.Vue = require('vue').default;
require('./bootstrap');

Vue.use(VueLazyload)
//sweetalert2
import Swal from 'sweetalert2'
window.Swal = require('sweetalert2');

//Toast
window.Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});


Vue.component('example-component', require('./components/ExampleComponent.vue').default);

const app = new Vue({
    el: '#app',
});
