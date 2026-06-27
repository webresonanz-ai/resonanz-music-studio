<template>
    <div class="fade-in-up">
        <div class="content-card">
            <h1 class="mb-4">Courses & Fees</h1>
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div v-else>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <div v-for="course in courses" :key="course.id" class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">{{ course.name }}</h5>
                                <p class="card-text">{{ course.description }}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-primary">{{ course.duration }}</span>
                                    <span class="fw-bold text-primary">Rp {{ formatPrice(course.fee) }}</span>
                                </div>
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
    name: 'CoursesFees',
    computed: {
        ...mapState(useTrmsStore, ['courses'])
    },
    data() {
        return {
            loading: false
        }
    },
    async mounted() {
        this.loading = true
        try {
            await this.fetchCourses()
        } finally {
            this.loading = false
        }
    },
    methods: {
        ...mapActions(useTrmsStore, ['fetchCourses']),
        formatPrice(price) {
            return new Intl.NumberFormat('id-ID').format(price)
        }
    }
}</script>