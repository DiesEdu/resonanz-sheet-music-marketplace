<template>
  <section class="verify-page d-flex align-items-center justify-content-center py-5 px-3">
    <div class="verify-card card animate-fade-scale">
      <div class="card-body p-4 p-md-5 text-center">
        <span class="badge bg-gold mb-3">Email Verified</span>
        <h1 class="verify-title mb-3">Verification Successful</h1>
        <p class="text-muted mb-4">
          Your email has been verified. You can now sign in to your account.
        </p>
        <router-link to="/login" class="btn btn-primary px-4">Go to Login</router-link>
      </div>
    </div>
  </section>
</template>

<style scoped>
.verify-page {
  min-height: calc(100vh - 220px);
}

.verify-card {
  width: 100%;
  max-width: 560px;
  border-radius: 24px;
}

.verify-title {
  font-size: 2.1rem;
  font-weight: 600;
  color: #2c1810;
}

.bg-gold {
  background: linear-gradient(135deg, #c5a572, #a88c5c);
}
</style>
