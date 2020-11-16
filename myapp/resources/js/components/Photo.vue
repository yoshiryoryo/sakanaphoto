<template>
  <div class="photo">
    <figure class="photo__wrapper">
      <img
        class="photo__image"
        :src="item.url"
        :alt="`Photo by ${item.owner.name}`"
      >
    </figure>
    <!-- マウスオーバーすると表示される、背景にボタンや投稿者名が載る要素 -->
    <RouterLink
      class="photo__overlay"
      :to="`/photos/${item.id}`"
      :title="`View the photo by ${item.owner.name}`"
    >
      <div class="photo__controls">
        <button
          class="photo__action photo__action--like"
          title="Like photo"
        >
          <i class="icon ion-md-heart"></i>{{ item.likes_count }}
        </button>
        <!-- hrefにはダウンロードリンクを指定、このリンクはVue Routerにハンドリングさせるのではなく、直接サーバーにGETリクエストを
        送信する必要があるので、<Router-link>ではなく<a>でなければならない -->
        <a
          class="photo__action"
          title="Download photo"
          @click.stop
          :href="`/photos/${item.id}/download`"
        >
          <i class="icon ion-md-arrow-round-down"></i>
        </a>
      </div>
      <div class="photo__username">
        {{ item.owner.name }}
      </div>
    </RouterLink>
  </div>
</template>

<script>
export default {
  // 1つ分の写真データとしてitemというpropsを受け取る
  props: {
    item: {
      type: Object,
      required: true
    }
  },
  methods: {
    // クリックされた写真のIDといいね済みかどうかをデータとしてイベント発行先に渡す
    like () {
      this.$emit('like', {
        id: this.item.id,
        liked: this.item.liked_by_user
      })
    }
  }
}
</script>