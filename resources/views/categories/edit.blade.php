@extends('layouts.app')

@section('title', 'Edit Category')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('categories.update', $category->nid) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="cname" class="form-label">Nama Kategori</label>
                        <input type="text" class="form-control @error('cname') is-invalid @enderror" id="cname" name="cname" value="{{ old('cname', $category->cname) }}" required>
                        @error('cname')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <a href="{{ route('categories.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection