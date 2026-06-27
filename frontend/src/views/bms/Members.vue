<template>
    <div class="fade-in-up">
        <div class="content-card">
            <h1 class="mb-4">Members</h1>
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div v-else>
                <div v-if="members.length === 0" class="text-center py-4">
                    <p>No members found.</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Instrument</th>
                                <th>Join Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="member in members" :key="member.id">
                                <td>{{ member.name }}</td>
                                <td>{{ member.instrument }}</td>
                                <td>{{ formatDate(member.join_date) }}</td>
                                <td>
                                    <span :class="`badge bg-${member.status === 'active' ? 'success' : 'secondary'}`">
                                        {{ member.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState, mapActions } from 'pinia'
import { useBmsStore } from '../../stores/api'

export default {
    name: 'Members',
    computed: {
        ...mapState(useBmsStore, ['members'])
    },
    data() {
        return {
            loading: false
        }
    },
    async mounted() {
        this.loading = true
        try {
            await this.fetchMembers()
        } finally {
            this.loading = false
        }
    },
    methods: {
        ...mapActions(useBmsStore, ['fetchMembers']),
        formatDate(date) {
            return date ? new Date(date).toLocaleDateString('id-ID') : '-'
        }
    }
}</script>