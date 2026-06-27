<template>
    <div class="fade-in-up">
        <div class="content-card">
            <h1 class="mb-4">News</h1>
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div v-else>
                <div v-if="news.length === 0" class="text-center py-4">
                    <p>No news articles found.</p>
                </div>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <div v-for="article in news" :key="article.id" class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">{{ article.title }}</h5>
                                <p class="card-text">{{ article.content }}</p>
                                <small class="text-muted">{{ formatDate(article.published_at) }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState, mapActions } from 'pinia'
import { useTrmsStore } from '../../stores/api'

export default {
    name: 'News',
    computed: {
        ...mapState(useTrmsStore, ['news'])
    },
    data() {
        return {
            loading: false
        }
    },
    async mounted() {
        this.loading = true
        try {
            await this.fetchNews()
        } finally {
            this.loading = false
        }
    },
    methods: {
        ...mapActions(useTrmsStore, ['fetchNews']),
        formatDate(date) {
            return new Date(date).toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })
        }
    }
}</script>