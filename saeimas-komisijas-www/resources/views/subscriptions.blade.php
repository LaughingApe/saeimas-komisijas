@extends('app')

@section('title')
Abonementi
@endsection

@section('content')
<main class="container">

    <div class="card">
        <div class="card-body">
            <h2>Manas e-pasta adreses</h2>
            <div class="email-list">
                @foreach ($emails as $email)
                <div>
                    <a role="button" data-toggle="modal" data-target="#approveDelete-{{ $email->id }}" class="remove-email-link"><img src="/assets/X.png" alt="Delete"/></a><span>{{ $email->email_address }}</span><br/>
                </div>
                @endforeach
            </div>
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

@foreach ($emails as $email)
<div class="modal fade" id="approveDelete-{{ $email->id }}" tabindex="-1" role="dialog" aria-labelledby="approveDelete-{{ $email->id }}" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="approveDelete-{{ $email->id }}">Dzēst e-pasta adresi?</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          Vai tiešām vēlaties dzēst e-pasta adresi <b>{{ $email->email_address}}</b>? Līdz ar tās dzēšanu tiks dzēsti arī visi tai piesaistītie abonementi.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Aizvērt</button>
          <a href="{{ url('/remove-email/'.$email->id) }}" type="button" class="btn btn-danger">Dzēst</a>
        </div>
      </div>
    </div>
  </div>
@endforeach

@endsection



@section('javascript')
<script>

</script>
@endsection