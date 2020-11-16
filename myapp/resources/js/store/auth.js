
import { OK, CREATED, UNPROCESSABLE_ENTITY } from '../util'

const state = {
  // ログイン済みユーザーを保持
  user: null,
  // APIの呼び出しが成功か失敗かを表す
  apiStatus: null,
  // エラーメッセージを入れる
  loginErrorMessages: null,
  registerErrorMessages: null
}

const getters = {
  check: state => !! state.user,
  username: state => state.user ? state.user.name : ''
}

const mutations = {
  // ユーザーのstate値を更新
  // mutationsの第一引数は必ずstate
  setUser (state, user) {
    state.user = user
  },
  setApiStatus (state, status) {
    state.apiStatus = status
  },
  setLoginErrorMessages (state, messages) {
    state.loginErrorMessages = messages
  },
  setRegisterErrorMessages (state, messages) {
    state.registerErrorMessages = messages
  }
}

const actions = {
  // 会員登録APIを呼び出す
  // actionの第一引数はコンテキストオブジェクトが渡される、コンテクストオブジェクトにはmutationを呼び出すためのcommitメソッドが入っている
  async register (context, data) {
    context.commit('setApiStatus', null)
    const response = await axios.post('/api/register', data)

    if (response.status === CREATED) {
      context.commit('setApiStatus', true)
      context.commit('setUser', response.data)
      return false
    }

    context.commit('setApiStatus', false)
    if (response.status === UNPROCESSABLE_ENTITY) {
      context.commit('setRegisterErrorMessages', response.data.errors)
    } else {
      context.commit('error/setCode', response.status, { root: true })
    }
  },
  // ログイン
  async login (context, data) {
    // apistatusを通信結果によって更新
    // 最初はnull
    context.commit('setApiStatus', null)
  const response = await axios.post('/api/login', data)
   
  if (response.status === OK) {
    // 成功したらtrue
    context.commit('setApiStatus', true)
    context.commit('setUser', response.data)
    return false
  }
   // 失敗したらfalse
  context.commit('setApiStatus', false)
  // バリデーションエラーの場合はルートコンポーネントに制御を渡さない
  if (response.status === UNPROCESSABLE_ENTITY) {
    context.commit('setLoginErrorMessages', response.data.errors)
  } else {
    context.commit('error/setCode', response.status, { root: true })
  }
  },
  // ログアウト
  async logout (context) {
    context.commit('setApiStatus', null)
    const response = await axios.post('/api/logout')

    if (response.status === OK) {
      context.commit('setApiStatus', true)
      context.commit('setUser', null)
      return false
    }

    context.commit('setApiStatus', false)
    context.commit('error/setCode', response.status, { root: true })
  },
  // ログインユーザーチェック
  async currentUser (context) {
    context.commit('setApiStatus', null)
    const response = await axios.get('/api/user')
    const user = response.data || null

    if (response.status === OK) {
      context.commit('setApiStatus', true)
      context.commit('setUser', user)
      return false
    }

    context.commit('setApiStatus', false)
    context.commit('error/setCode', response.status, { root: true })
  }
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
}