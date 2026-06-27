<template>
    <div class="fade-in-up">
        <div class="content-card">
            <h1 class="mb-4">Teachers</h1>
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div v-else>
                <div v-if="teachers.length === 0" class="text-center py-4">
                    <p>No teachers found.</p>
                </div>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <div v-for="teacher in teachers" :key="teacher.id" class="col">
                        <div class="card h-100">
                            <img v-if="teacher.photo" :src="teacher.photo" class="card-img-top" :alt="teacher.name">
                            <div class="card-body">
                                <h5 class="card-title">{{ teacher.name }}</h5>
                                <p class="card-text text-muted">{{ teacher.position }}</p>
                                <p class="card-text">{{ teacher.bio }}</p>
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
    name: 'Teachers',
    computed: {
        ...mapState(useTrmsStore, ['teachers'])
    },
    data() {
        return {
            loading: false
        }
    },
    async mounted() {
        this.loading = true
        try {
            await this.fetchTeachers()
        } finally {
            this.loading = false
        }
    },
    methods: {
        ...mapActions(useTrmsStore, ['fetchTeachers'])
    }
}</script>