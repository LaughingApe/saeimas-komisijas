@extends('outter_website')

@section('title')
Sākums
@endsection

@section('content')
<main class="login-form">
    <div class="cotainer">
        <div class="row justify-content-center">
            <div class="col-md-4">

                <h1 class="mt-5">Seko Saeimai</h1>

                @if (Session::has('success'))
                <div class="alert alert-success">
                  <i class="fas fa-check-circle"></i> {{ Session::get('success') }}
                </div>
                @endif

                @foreach ($errors->all() as $error)
                <div class="alert alert-warning">{{ $error }}</div>
                @endforeach
                <div class="card">
                    <h3 class="card-header text-center">Pieslēgties</h3>
                    <div class="card-body">
                        <form method="POST" action="{{ route('login.post') }}">
                            @csrf
                            <div class="form-group mb-3">
                                <input type="text" placeholder="E-pasta adrese" id="email" class="form-control" name="email" required
                                    autofocus>
                                @if ($errors->has('email'))
                                <span class="text-danger">{{ $errors->first('email') }}</span>
                                @endif
                            </div>

                            <div class="form-group mb-3">
                                <input type="password" placeholder="Parole" id="password" class="form-control" name="password" required>
                                @if ($errors->has('password'))
                                <span class="text-danger">{{ $errors->first('password') }}</span>
                                @endif
                            </div>

                            <div class="d-grid mx-auto">
                                <button type="submit" class="btn btn-primary btn-block">Ieiet</button>
                            </div>
                        </form>

                    </div>
                </div>
                <div class="registration-link-container">
                    <a href="{{ url('registration') }}">Reģistrēties</a>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection