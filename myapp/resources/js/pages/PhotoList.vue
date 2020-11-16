<template>
<div class="photo-list">
    <div class="grid">
        <Photo 
        class="grid__item" 
        v-for="photo in photos" 
        :key="photo.id" 
        :item="photo" 
         @like="onLikeClick"
        />
    </div>
    <Pagination :current-page="currentPage" :last-page="lastPage" />
</div>
</template>

<script>
import { OK } from '../util'
import Photo from '../components/Photo.vue'
import Pagination from '../components/Pagination.vue'

export default {
    components: {
        Photo,
        Pagination
    },
    // ルーターから渡されるpageプロパティを受け取る
    props: {
        type: Number,
        required: false,
        default: 1
    },
    data () {
        return {
            // 写真一覧データが入る
            photos: [],
            currentPage: 0,
            lastPage: 0
        }
    },
    methods: {
        async fetchPhotos () {
            const response = await axios.get(`/api/photos/?page=${this.page}`)

            if (response.status !== OK) {
                this.$store.commit('error/setCode', response.status)
                return false
            }
            /* 
            response.dataでレスポンスのJSONが習得できる。そのJSONファイルの中でdataという項目があり、
            その中の写真一覧を呼び出すことができる
            */
            this.photos = response.data.data
            // データ変数にレスポンスの該当する値を記入
            this.currentPage = response.data.current_page
            this.lastPage = response.data.last_page
        },

        onLikeClick ({ id, liked }) {
            if (! this.$store.getters['auth/check']) {
                alert('いいね機能を使うにはログインしてください。')
                return false
            }

            if (liked) {
                this.unlike(id)
            } else {
                this.like(id)
            }
        },

        async like (id) {
            const response = await axios.put(`/api/photos/${id}/like`)

            if (response.status !== OK) {
                this.$store.commit('error/setCode', response.status)
                return false
            }
            // いいね付与のAPI通信完了後、ページ上の写真の見た目(いいね数とボタンの色)を変えるため、this.photoのデータを更新
            this.photos = this.photos.map(photo => {
                if (photo.id === response.data.photo_id) {
                    // いいね数を一つ増やして、いいねしたかどうかを表すliked_by_userをtrueに更新する(これでボタンの見た目が変わる)
                    photo.likes_count += 1
                    photo.liked_by_user = true
                }
                return photo
            })
        },

        async unlike (id) {
            const response = await axios.delete(`/api/photos/${id}/like`)

            if (response.status !== OK) {
                this.$store.commit('error/setCode', response.status)
                return false
            }

         this.photos = this.photos.map(photo => {
          if (photo.id === response.data.photo_id) {
           photo.likes_count -= 1
           photo.liked_by_user = false
        }
        return photo
      })
        }
    },
    /*
    watchメソッドで$routeを監視して、ページが切り替わったときにfetchPhotosが実行されるように記述、
    さらに、immediateオプションをtrueに設定しているので、コンポーネントが生成された実行される
    */
    watch: {
        $route: {
            async handler () {
                await this.fetchPhotos()
            },
            immediate: true
        }
    }
}
</script>
