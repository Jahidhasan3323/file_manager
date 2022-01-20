import Vue from 'vue';
import Vuex from 'vuex';
import FileManager from 'laravel-file-manager'
Vue.use(Vuex);

// create Vuex store, if you don't have it
const store = new Vuex.Store();

Vue.use(FileManager, {store});
window.Vue = require('vue').default;

require('./bootstrap');


Vue.component('example-component', require('./components/ExampleComponent.vue').default);

const app = new Vue({
    el: '#app',
});
