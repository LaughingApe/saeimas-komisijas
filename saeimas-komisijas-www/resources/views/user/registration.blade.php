@extends('outter_website')

@section('title')
Reģistrēties
@endsection

@section('content')
<main class="signup-form">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="registration-back">
                    <a href="{{ url('/') }}">Atpakaļ</a>
                </div>
                <div class="card">
                    <h3 class="card-header text-center">Reģistrēt jaunu lietotāju</h3>
                    <div class="card-body">

                        <form action="{{ route('register.post') }}" method="POST">
                            @csrf
                            <div class="form-group mb-3">
                                <input type="text" placeholder="Vārds" id="name" class="form-control" name="name"
                                    required autofocus>
                                @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                                @endif
                            </div>

                            <div class="form-group mb-3">
                                <input type="text" placeholder="E-pasta adrese" id="email_address" class="form-control"
                                    name="email" required autofocus>
                                @if ($errors->has('email'))
                                <span class="text-danger">{{ $errors->first('email') }}</span>
                                @endif
                            </div>

                            <div class="form-group mb-3">
                                <input type="password" placeholder="Parole" id="password" class="form-control"
                                    name="password" required>
                                @if ($errors->has('password'))
                                <span class="text-danger">{{ $errors->first('password') }}</span>
                                @endif
                            </div>

                            <div class="form-group mb-3">
                                <input type="password" placeholder="Atkārtot paroli" id="password-repeat" class="form-control"
                                    name="password-repeat" required>
                                @if ($errors->has('password-repeat'))
                                <span class="text-danger">{{ $errors->first('password-repeat') }}</span>
                                @endif
                            </div>

                            <div class="d-grid mx-auto">
                                <button type="submit" class="btn btn-primary btn-block">Reģistrēties</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection