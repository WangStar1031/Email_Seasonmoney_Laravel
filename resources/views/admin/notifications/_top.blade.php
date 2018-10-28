@foreach ($notifications as $n)
    <div class="alert alert-{{$n->level}}">
        {{$n->message}}
    </div>
@endforeach
