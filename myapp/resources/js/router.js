import Vue from 'vue'
import VueRouter from 'vue-router'

// ページコンポーネントをインポートする
import PhotoList from './pages/PhotoList.vue'
import PhotoDetail from './pages/PhotoDetail.vue'
import Login from './pages/Login.vue'
import SystemError from './pages/errors/System.vue'
import NotFound from './pages/errors/NotFound.vue'

import store from './store'

// VueRouterプラグインを使用する
// これによって<RouterView />コンポーネントなどを使うことができる
Vue.use(VueRouter)

// パスとコンポーネントのマッピング
const routes = [
  {
    path: '/',
    component: PhotoList,
    /* 
   <PhotoList>コンポーネントにクエリパラメータの値が、pageというpropsとして渡される、
   propsに関数を指定する場合は、その返却値がpropsとしてページコンポーネントに渡される、そして、その関数の引数はルート情報を表すrouteを使用している。
   routeからクエリパラメータpageを取り出し、正規表現を使用し整数と解釈されない値は「1」とみなして返却している。
   */
    props: route => {
      const page = route.query.page
      return { page: /^[1-9][0-9]*$/.test(page) ? page * 1 : 1 }
    }
  },
  {
    // idはURLの変化する部分を表す
    path: '/photos/:id',
    component: PhotoDetail,
    // props: trueで変数部分(写真IDの値)をpropsで受け取ることを意味する
    props: true
  },
  {
    path: '/login',
    component: Login,
    beforeEnter(to, from, next) {
      if (store.getters['auth/check']) {
        next('/')
      } else {
        next()
      }
    }
  },
  {
    path: '/500',
    component: SystemError
  },
  {
    path: '*',
    component: NotFound
  }
]

// VueRouterインスタンスを作成する
const router = new VueRouter({
  mode: 'history',
  scrollBehavior() {
    return { x: 0, y: 0 }
  },
  routes
})

// VueRouterインスタンスをエクスポートする
// app.jsでインポートするため
export default router