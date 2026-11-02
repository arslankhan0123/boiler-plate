{{-- Page hero. Usage: @include('ecommerce.store.partials.hero', ['title'=>'All <span>Products</span>', 'text'=>'...', 'crumbs'=>[['Home',route(...)],['Shop',null]]]) --}}
<section class="phero">
  <div class="wrap">
    @if(!empty($crumbs))
    <div class="crumb">
      @foreach($crumbs as $i => $c)
        @if($c[1] ?? null)<a href="{{ $c[1] }}">{{ $c[0] }}</a>@else<span>{{ $c[0] }}</span>@endif
        @if(!$loop->last)<i class="fa-solid fa-chevron-right" style="font-size:9px"></i>@endif
      @endforeach
    </div>
    @endif
    <h1>{!! $title !!}</h1>
    @if(!empty($text))<p>{{ $text }}</p>@endif
  </div>
</section>
