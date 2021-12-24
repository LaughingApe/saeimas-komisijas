@extends('app')

@section('title')
Abonementi
@endsection

@section('content')
<main class="container">

    <div class="card">
        <div class="card-body">
            <a href="{{ url('/add-email') }}" class="btn btn-success">Pievienot e-pasta adresi</a> 
        </div>
    </div>

    <div class="subscription-grid">
        @foreach ($commissions as $commission)
        <div class="card">
            <div class="card-body">
                <p class="h3">{{ $commission->display_name }}</p>

                <form method="POST" action="{{ route('subscriptions.store') }}">
                    @csrf
                    @foreach ($emails as $email)
                    <input type="checkbox" id="{{ $commission->id.':'.$email->id }}" name="{{ $commission->id.':'.$email->id }}" value="{{ $email->email_address }}" {{ $subscriptions[$commission->id][$email->id] ? 'checked' : '' }}>
                    <label for="{{ $commission->id.':'.$email->id }}">{{ $email->email_address }}</label><br>
                    @endforeach
                    <button type="submit" name="submit-{{ $commission->id }}" class="btn btn-primary">Saglabāt</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</main>
@endsection