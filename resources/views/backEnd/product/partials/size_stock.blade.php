{{-- 📦 সাইজ অনুযায়ী স্টক (ভ্যারিয়েন্ট না থাকলে কিছু দেখাবে না) --}}
@php $sizeStocks = $product->sizeStocks(); @endphp
@if ($sizeStocks->isNotEmpty())
    <div class="d-flex flex-wrap gap-1 mt-1">
        @foreach ($sizeStocks as $sizeName => $qty)
            <span class="badge {{ $qty > 0 ? 'badge-soft-success' : 'badge-soft-danger' }}" style="font-size:10px;"
                  title="{{ $qty > 0 ? $qty . ' in stock' : 'Stock Out' }}">
                {{ $sizeName }}: {{ $qty > 0 ? $qty : 'Out' }}
            </span>
        @endforeach
    </div>
@endif
