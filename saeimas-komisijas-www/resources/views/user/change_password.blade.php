@extends('app')

@section('title')
Lietotāja dati
@endsection

@section('content')
<main class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            @if (Session::has('success'))
            <div class="alert alert-success">
              <i class="fas fa-check-circle"></i> {{ Session::get('success') }}
            </div>
            @endif

            <div class="card">

                <div class="card-body">
                    <h3>Mainīt paroli</h3>
                    <form method="POST" action="{{ route('change-password') }}">
                        @csrf
                        <div class="form-group mb-3">
                            <input type="password" placeholder="Līdzšinējā parole" id="oldPassword" class="form-control mt-3" name="oldPassword" required autofocus>
                            @if ($errors->has('oldPassword'))
                            <span class="text-danger">{{ $errors->first('oldPassword') }}</span>
                            @endif
                            <input type="password" placeholder="Jaunā parole" id="newPassword" class="form-control mt-3" name="newPassword" required>
                            @if ($errors->has('newPassword'))
                            <span class="text-danger">{{ $errors->first('newPassword') }}</span>
                            @endif
                            <input type="password" placeholder="Jaunā parole vēlreiz" id="newPasswordRepeat" class="form-control mt-3" name="newPasswordRepeat" required>
                            @if ($errors->has('newPasswordRepeat'))
                            <span class="text-danger">{{ $errors->first('newPasswordRepeat') }}</span>
                            @endif
                        </div>

                        <div class="d-grid mx-auto">
                            <button type="submit" class="btn btn-success">Saglabāt</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="mt-2">
                <a href="{{ url('delete-account') }}" class="link-danger">Dzēst kontu</a>
            </div>
        </div>
    </div>
</main>
@endsection