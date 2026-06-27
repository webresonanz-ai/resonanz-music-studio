<template>
    <div class="fade-in-up">
        <div class="content-card">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">The Resonanz Music Studio</h1>
                    <p class="lead text-muted mb-4">
                        Discover your musical potential with world-class instruction and state-of-the-art facilities.
                    </p>
                    <div class="d-flex gap-3">
                        <router-link to="/trms/courses-fees" class="btn btn-primary btn-lg">
                            Explore Courses
                        </router-link>
                        <router-link to="/trms/contact" class="btn btn-outline-primary btn-lg">
                            Contact Us
                        </router-link>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="bg-light rounded-3 p-4 text-center">
                        <i class="bi bi-music-note-beamed display-1 text-primary"></i>
                        <h3 class="mt-3">Start Your Musical Journey Today</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4 mb-4">
                <div class="content-card h-100">
                    <div class="text-center mb-3">
                        <i class="bi bi-people-fill display-4 text-primary"></i>
                    </div>
                    <h4 class="text-center">Expert Teachers</h4>
                    <p class="text-muted">Learn from internationally acclaimed musicians and educators.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="content-card h-100">
                    <div class="text-center mb-3">
                        <i class="bi bi-building display-4 text-primary"></i>
                    </div>
                    <h4 class="text-center">World-Class Facilities</h4>
                    <p class="text-muted">State-of-the-art studios and performance spaces.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="content-card h-100">
                    <div class="text-center mb-3">
                        <i class="bi bi-calendar-check display-4 text-primary"></i>
                    </div>
                    <h4 class="text-center">Flexible Schedules</h4>
                    <p class="text-muted">Programs designed to fit your busy lifestyle.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TRMSHome'
}
</script>