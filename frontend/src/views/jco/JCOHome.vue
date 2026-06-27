<template>
    <div class="fade-in-up">
        <div class="content-card bg-dark text-white">
            <h1 class="display-4 fw-bold mb-4">Jakarta Concert Orchestra</h1>
            <p class="lead">
                Experience the magic of classical music with our world-class orchestra.
            </p>
        </div>

        <div class="row mt-4">
            <div class="col-lg-8 mb-4">
                <div class="content-card">
                    <h3><i class="bi bi-music-player me-2"></i>Featured Performance</h3>
                    <div class="ratio ratio-16x9">
                        <div class="bg-light d-flex align-items-center justify-content-center">
                            <i class="bi bi-play-circle display-1 text-primary"></i>
                        </div>
                    </div>
                    <h5 class="mt-3">Symphony No. 5 - Beethoven</h5>
                    <p class="text-muted">Performed at Jakarta Arts Center, December 2023</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="content-card h-100">
                    <h3><i class="bi bi-calendar-check me-2"></i>Upcoming Concerts</h3>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <h6>New Year's Concert</h6>
                            <small class="text-muted">January 1, 2025</small>
                        </div>
                        <div class="list-group-item">
                            <h6>Spring Symphony</h6>
                            <small class="text-muted">March 20, 2025</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'JCOHome'
}
</script>