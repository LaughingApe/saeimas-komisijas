@extends('app')

@section('title')
Pievienot e-pasta adresi
@endsection

@section('content')
<main class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card">
                @foreach ($errors->all() as $error)
                <div class="alert alert-warning">{{ $error }}</div>
                @endforeach
                <div class="card-body">
                    <h3>Pievienot e-pasta adresi</h3>
                    <form method="POST" action="{{ route('email.store') }}">
                        @csrf
                        <div class="form-group mb-3">
                            <input type="text" placeholder="E-pasta adrese" id="email" class="form-control" name="email" required
                                autofocus>
                            @if ($errors->has('email'))
                            <span class="text-danger">{{ $errors->first('email') }}</span>
                            @endif
                        </div>

                        <div class="d-grid mx-auto">
                            <button type="submit" class="btn btn-success">Pievienot</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</main>
@endsection