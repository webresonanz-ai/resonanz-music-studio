<template>
    <div class="fade-in-up">
        <div class="content-card">
            <h1 class="mb-4">{{ pageTitle }}</h1>
            <p class="lead text-muted">
                This is the {{ pageTitle }} page. Content coming soon.
            </p>
        </div>
    </div>
</template>

<script>
export default {
    name: 'PageName',
    data() {
        return {
            pageTitle: 'Page Title'
        }
    }
}
</script>