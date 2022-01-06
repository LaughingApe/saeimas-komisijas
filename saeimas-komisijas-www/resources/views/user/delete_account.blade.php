@extends('app')

@section('title')
Dzēst kontu
@endsection

@section('content')
<main class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">

            @foreach ($errors->all() as $error)
            <div class="alert alert-warning">{{ $error }}</div>
            @endforeach

            <div class="card">
                <div class="card-body">
                    <h3>Dzēst lietotāju</h3>
                    <p>
                        <span class="badge bg-danger">Uzmanību!</span> Ja nospiedīsiet pogu "Dzēst kontu", jūsu konts, e-pasta adrešu saraksts un abonementi tiks neatgriezeniski dzēsti. Tos vairs nebūs iespējams atjaunot.
                    </p>
                    <form method="POST" action="{{ route('user.delete') }}">
                        @csrf

                        <input name="userId" name="userId" value="{{ $userId }}" style="display:none;" hidden/>

                        <div class="d-grid mx-auto">
                            <button type="submit" class="btn btn-danger">Dzēst kontu</button>
                            <a href="{{ url('change-password' )}}" class="btn btn-light mt-2">Atpakaļ</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</main>
@endsection