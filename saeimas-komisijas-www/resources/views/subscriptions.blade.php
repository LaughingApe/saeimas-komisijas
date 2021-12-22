@extends('app')

@section('title')
Abonementi
@endsection

@section('content')
<main class="container">

    <div class="card">
        <div class="card-body">
            <a href="{{ url('/add-email') }}" class="btn btn-primary">Pievienot e-pasta adresi</a> 
        </div>
    </div>

    <div class="subscription-grid">
        @foreach ($commissions as $commission)
        <div class="card">
            <div class="card-body">
                <p class="h3">{{ $commission->display_name }}</p>

                @foreach ($emails as $email)
                <input type="checkbox" id="{{ $commission->id.':'.$email->id }}" name="{{ $commission->id.':'.$email->id }}" value="{{ $email->email_address }}">
                <label for="{{ $commission->id.':'.$email->id }}">{{ $email->email_address }}</label><br>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</main>
@endsection