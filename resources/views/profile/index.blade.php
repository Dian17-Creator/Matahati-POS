@extends('layouts.app')

@section('title', 'Profil')

@section('content')
<div class="container-fluid px-3 px-md-4 pt-2 pb-3">
    <!-- Top Decorative Banner (Maroon like sidebar) -->
    <div class="profile-banner rounded-4 shadow-sm" style="background: linear-gradient(135deg, #b63352 0%, #8c1d37 100%); height: 160px;"></div>

    <!-- Main Profile Card -->
    <div class="card border-0 shadow-sm rounded-2 position-relative mx-auto" style="max-width: 980px; margin-top: -120px;">
        <div class="card-body p-4 p-md-4">
            <div class="row g-4 align-items-center">
                <!-- Left Column: Form Profile -->
                <div class="col-lg-7 pe-lg-4 border-end-lg">
                    <h4 class="fw-bold text-dark mb-3">Profile Saya</h4>

                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Nama -->
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-medium mb-1">Nama</label>
                            <div class="input-group">
                                <input type="text" name="cname" class="form-control form-control-lg fs-6 @error('cname') is-invalid @enderror" value="{{ old('cname', $user->cname) }}" required>
                                <span class="input-group-text bg-white text-muted">
                                    <i class="bi bi-pencil"></i>
                                </span>
                                @error('cname')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-medium mb-1">Email</label>
                            <div class="input-group">
                                <input type="email" name="cemail" class="form-control form-control-lg fs-6 @error('cemail') is-invalid @enderror" value="{{ old('cemail', $user->cemail) }}" required>
                                <span class="input-group-text bg-white text-muted">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                @error('cemail')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Handphone -->
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-medium mb-1">Handphone</label>
                            <div class="input-group">
                                <input type="text" name="cphone" class="form-control form-control-lg fs-6 @error('cphone') is-invalid @enderror" value="{{ old('cphone', $user->cphone ?? '') }}" placeholder="Contoh: +628123456789">
                                <span class="input-group-text bg-white text-muted">
                                    <i class="bi bi-pencil"></i>
                                </span>
                                @error('cphone')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Kata Sandi -->
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-medium mb-1">Kata Sandi</label>
                            <div>
                                <button type="button" class="btn btn-outline-primary btn-sm px-3 py-2 fw-medium rounded-3" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="bi bi-key me-1"></i> Ganti Kata Sandi
                                </button>
                            </div>
                        </div>

                        <!-- Ganti Bahasa -->
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-medium mb-1">Ganti Bahasa</label>
                            <select class="form-select form-select-lg fs-6" disabled style="background-color: #f8fafc;">
                                <option selected>Bahasa Indonesia</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-medium">
                                <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Status & Info (Olsera Style - Vertically Centered) -->
                <div class="col-lg-5 ps-lg-4 d-flex flex-column align-items-center justify-content-center text-center my-auto py-2">
                    <div class="profile-illustration-box mb-3 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; background-color: #e0f2fe; border-radius: 50%;">
                        <i class="bi bi-shield-check text-primary" style="font-size: 3rem;"></i>
                    </div>

                    <h5 class="fw-bold text-dark mb-2">Informasi Akun Anda</h5>
                    <p class="text-muted small mb-3" style="max-width: 300px;">
                        Data akun Anda terhubung dengan sistem POS Matahati. Pastikan data profil selalu mutakhir.
                    </p>

                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-medium">
                            <i class="bi bi-person-badge text-primary me-1"></i> {{ $user->role_name ?? 'Supervisor' }}
                        </span>
                        <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-medium">
                            <i class="bi bi-shop text-primary me-1"></i> {{ $outletName }}
                        </span>
                        @if($user->dtanggalmasuk)
                        <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-medium">
                            <i class="bi bi-calendar3 text-primary me-1"></i> Masuk: {{ date('d M Y', strtotime($user->dtanggalmasuk)) }}
                        </span>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ganti Kata Sandi -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="changePasswordModalLabel">Ganti Kata Sandi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('profile.password') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">Kata Sandi Saat Ini</label>
                        <div class="position-relative">
                            <input type="password" name="current_password" id="current_password" class="form-control pe-5" required placeholder="Masukkan kata sandi lama">
                            <span class="position-absolute end-0 top-50 translate-middle-y me-3 toggle-password-btn text-muted" role="button" data-target="current_password" title="Tampilkan / Sembunyikan Kata Sandi" style="cursor: pointer; z-index: 5;">
                                <i class="bi bi-eye fs-6"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">Kata Sandi Baru</label>
                        <div class="position-relative">
                            <input type="password" name="new_password" id="new_password" class="form-control pe-5" required minlength="6" placeholder="Minimal 6 karakter">
                            <span class="position-absolute end-0 top-50 translate-middle-y me-3 toggle-password-btn text-muted" role="button" data-target="new_password" title="Tampilkan / Sembunyikan Kata Sandi" style="cursor: pointer; z-index: 5;">
                                <i class="bi bi-eye fs-6"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">Konfirmasi Kata Sandi Baru</label>
                        <div class="position-relative">
                            <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control pe-5" required minlength="6" placeholder="Ulangi kata sandi baru">
                            <span class="position-absolute end-0 top-50 translate-middle-y me-3 toggle-password-btn text-muted" role="button" data-target="new_password_confirmation" title="Tampilkan / Sembunyikan Kata Sandi" style="cursor: pointer; z-index: 5;">
                                <i class="bi bi-eye fs-6"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Simpan Kata Sandi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-password-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (input && icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }
        });
    });
});
</script>
@endpush