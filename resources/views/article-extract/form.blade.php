@extends('layouts.app')

@section('content')
<div class="container" style="max-width:720px">
    <h1 class="mb-3">Trích xuất bài viết</h1>

    @if ($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
    @endif

    <form method="post" action="{{ route('extract.view') }}">
        @csrf
        <div class="mb-3">
            <label for="url" class="form-label">URL bài viết</label>
            <input id="url" name="url" type="url" class="form-control" placeholder="https://..."
                   value="{{ old('url') }}" required>
        </div>
        <button class="btn btn-primary">Trích xuất</button>
    </form>
</div>
@endsection
