<template>
  <div v-show="value" class="photo-form">
    <h2 class="title">Submit a photo</h2>
    <div v-show="loading" class="panel">
      <Loader>投稿中...</Loader>
    </div>
    <form v-show="! loading" class="form" @submit.prevent="submit">
      <div class="errors" v-if="errors">
        <ul v-if="errors.photo">
          <li v-for="msg in errors.photo" :key="msg">{{ msg }}</li>
        </ul>
      </div>
      <input class="form__item" type="file" @change="onFileChange">
      <output class="form__output" v-if="preview">
        <img :src="preview" alt="">
      </output>
      <div class="form__button">
        <button type="submit" class="button button--inverse">送信</button>
      </div>
    </form>
  </div>
</template>

<script>
import { CREATED, UNPROCESSABLE_ENTITY } from '../util'
import Loader from './Loader.vue'

export default {
  component: {
    Loader
  },
  props: {
    value: {
      type: Boolean,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      preview: null,
      // ファイルの格納を行う
      photo: null,
      errors: null
    }
  },
  methods: {
    // フォームでファイルが選択されたら実行される
    onFileChange(event) {
      // 何も選択がされていない場合は処理を中断する
      if (event.target.files.length === 0) {
        this.reset()
        return false
      }

      // ファイルが画像出ない場合は処理を中断
      if (! event.target.files[0].type.match('image.*')) {
        this.reset()
        return false
      }

      // FileReaderクラスのインスタンスを取得
      const reader = new FileReader()

      // ファイルを読み込み終わった時に実行する処理
      reader.onload = e => {
        // previewに読み込み結果(データURL)を代入する
        // previewに値が入ると<output>につけたv-ifがtrueと判定される
        // <output>内部の<img>のsrc属性はpreviewの値を参照しているので、結果として画像が表示される
        this.preview = e.target.result
      }

      // ファイルを読み込む
      // 読み込まれたファイルはデータURL形式で受け取れる
      reader.readAsDataURL(event.target.files[0])
      this.photo = event.target.files[0]
    },
    // 入力欄の値とプレビュー表示をクリアするメソッド
    reset() {
      this.preview = ''
      this.photo = null
      // this.$elはコンポーネントそのもののDOM要素を指す
      this.$el.querySelector('input[type="file"]').value = null
    },
    async submit() {
      // メソッドの冒頭でloadingをtrueにすることで最初にloadingの表示が行われる
      this.loading = true

      const formData = new FormData()
      formData.append('photo', this.photo)
      const response = await axios.post('/api/photos', formData)
      // 通信が終わったらfalseを返してloadingを非表示にする
      this.loading = false

      // バリデーションエラーはエラーメッセージを表示する関係から、値をクリアしたりフォームを閉じる前にreturn falseで処理を中断
      if (response.status === UNPROCESSABLE_ENTITY) {
        this.errors = response.data.errors
        return false
      }

      this.reset()
      this.$emit('input', false)

      if (response.status !== CREATED) {
        this.$store.commit('error/setCode', response.status)
        return false
      }

      // メッセージ登録
      this.$store.commit('message/setContent', {
        content: '写真が投稿されました！',
        timeout: 6000
      })

      // 投稿完了後に写真詳細ページに移動
      this.$router.push(`/photos/${response.data.id}`)
    }
  }
}
</script>