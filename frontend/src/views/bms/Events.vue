<template>
    <div class="fade-in-up">
        <div class="content-card">
            <h1 class="mb-4">Events</h1>
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div v-else>
                <div v-if="events.length === 0" class="text-center py-4">
                    <p>No events found.</p>
                </div>
                <div class="list-group list-group-flush">
                    <div v-for="event in events" :key="event.id" class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">{{ event.title }}</h5>
                            <small class="text-muted">{{ formatDate(event.event_date) }}</small>
                        </div>
                        <p class="mb-1">{{ event.description }}</p>
                        <small class="text-muted">{{ event.location }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState, mapActions } from 'pinia'
import { useBmsStore } from '../../stores/api'

export default {
    name: 'Events',
    computed: {
        ...mapState(useBmsStore, ['events'])
    },
    data() {
        return {
            loading: false
        }
    },
    async mounted() {
        this.loading = true
        try {
            await this.fetchEvents()
        } finally {
            this.loading = false
        }
    },
    methods: {
        ...mapActions(useBmsStore, ['fetchEvents']),
        formatDate(date) {
            return new Date(date).toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
        }
    }
}</script>