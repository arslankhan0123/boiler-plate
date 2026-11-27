{{-- Page hero with an auto-rotating e-commerce image slider behind a brand overlay.
     Usage: @include('ecommerce.store.partials.hero', ['title'=>'All <span>Products</span>', 'text'=>'...', 'crumbs'=>[['Home',route(...)],['Shop',null]]]) --}}
@push('styles')
<style>
.phero{position:relative;min-height:300px;display:flex;align-items:center;overflow:hidden;background:var(--grad)}
@media(max-width:768px){.phero{min-height:220px}}
.phero::before{display:none}
.phero-slides,.phero-ov{position:absolute;inset:0}
.phero-slide{position:absolute;inset:0;background-size:cover;background-position:center;opacity:0;transition:opacity 1.3s ease;will-change:opacity,transform}
.phero-slide.is-on{opacity:1;animation:pheroZoom 8s ease-out forwards}
@keyframes pheroZoom{from{transform:scale(1.06)}to{transform:scale(1.16)}}
.phero-ov{z-index:1;background:linear-gradient(90deg,rgba(11,43,40,.93),rgba(15,118,110,.74) 55%,rgba(20,184,166,.45))}
.phero-ov::after{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.05) 1px,transparent 1px);background-size:42px 42px;transform:perspective(500px) rotateX(55deg) translateY(90px) scale(2);transform-origin:center bottom;opacity:.4}
.phero .wrap{position:relative;z-index:2;padding:40px 24px 44px;width:100%}
.phero-dots{position:absolute;left:24px;bottom:16px;z-index:3;display:flex;gap:7px}
.phero-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.45);border:0;cursor:pointer;padding:0;transition:.3s}
.phero-dot.is-on{background:#fff;width:24px;border-radius:5px}
@media(prefers-reduced-motion:reduce){.phero-slide.is-on{animation:none}}
</style>
@endpush

@php
  $pheroImgs = [
    'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1600&q=70&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1600&q=70&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1472851294608-062f824d29cc?w=1600&q=70&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1600&q=70&auto=format&fit=crop',
  ];
@endphp

<section class="phero">
  <div class="phero-slides" aria-hidden="true">
    @foreach($pheroImgs as $i => $img)
      <div class="phero-slide {{ $i===0?'is-on':'' }}" style="background-image:url('{{ $img }}')"></div>
    @endforeach
  </div>
  <div class="phero-ov" aria-hidden="true"></div>
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
  <div class="phero-dots">
    @foreach($pheroImgs as $i => $img)<button type="button" class="phero-dot {{ $i===0?'is-on':'' }}" aria-label="Slide {{ $i+1 }}"></button>@endforeach
  </div>
</section>

@push('scripts')
<script>
(function(){
  const slides=[...document.querySelectorAll('.phero-slide')];
  const dots=[...document.querySelectorAll('.phero-dot')];
  if(slides.length<2) return;
  let i=0, timer=null;
  function go(n){ slides[i].classList.remove('is-on'); dots[i]&&dots[i].classList.remove('is-on'); i=(n+slides.length)%slides.length; slides[i].classList.add('is-on'); dots[i]&&dots[i].classList.add('is-on'); }
  function start(){ timer=setInterval(()=>go(i+1),4800); }
  dots.forEach((d,n)=>d.addEventListener('click',()=>{ clearInterval(timer); go(n); start(); }));
  start();
})();
</script>
@endpush
