var app;

window.onload = function() {
  const routes = [
    { path: '/payments', component: Payments },
    { path: '/customers', component: Customers },
    { path: '/products', component: Products },
    { path: '/coupons', component: Coupons },
    { path: '/promotion-codes', component: PromotionCodes },
    { path: '/quotes', component: Quotes },
    { path: '/invoices', component: Invoices },
    { path: '/plans', component: Plans },
    { path: '/subscriptions', component: Subscriptions },
    { path: '*', component: Payments }
  ]
  
  const router = new VueRouter({
    routes: routes
  })
  
  var data = {
    drawer: false,
    group: null,
  }

  app = new Vue({
    data,
    router,
    vuetify: new Vuetify(),
  }).$mount('#stripela')
}
