import ExampleComponent from './components/ExampleComponent.vue'
import Vue from 'vue';
import VeeValidate from 'vee-validate';
// ES6 Modules or TypeScript


/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

Vue.component('example-component', require('./components/ExampleComponent.vue'));

Vue.use(VeeValidate, {
    locale: 'en',
});

const app = new Vue({
    el: '#app',
    template: '<ExampleComponent/>',
    components: { ExampleComponent },
});
