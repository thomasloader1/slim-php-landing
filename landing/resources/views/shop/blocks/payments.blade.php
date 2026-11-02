{{--
    Medios de pago + texto de pie (Tienda → Configuración). Si no hay nada que mostrar no se renderiza.
--}}
@php
    $showPay    = ($settings['menu_show_payment'] ?? '0') === '1';
    $payAlias   = $settings['menu_payment_alias'] ?? '';
    $payMethods = array_filter(explode(',', $settings['menu_payment_methods'] ?? 'cash,transfer'));
    $footerTxt  = $settings['menu_footer_text'] ?? '';
    $payLabels  = [
        'cash'     => ['Efectivo', 'fa-money-bill-wave'],
        'transfer' => ['Transferencia', 'fa-building-columns'],
        'qr'       => ['QR / Mercado Pago', 'fa-qrcode'],
        'card'     => ['Tarjeta', 'fa-credit-card'],
    ];
@endphp
@if($showPay || $footerTxt)
<footer class="max-w-7xl mx-auto px-4 sm:px-6">
    <div class="ds-card px-5 py-8 sm:px-10 text-center">
        @if($showPay)
            <h2 class="text-lg sm:text-xl font-extrabold mb-5">Medios de pago</h2>
            <ul class="flex justify-center gap-2 sm:gap-3 flex-wrap mb-5">
                @foreach($payMethods as $method)
                    @if(isset($payLabels[$method]))
                        <li class="ds-chip !text-sm !px-4 !py-2" style="color: var(--text)">
                            <i class="fa-solid {{ $payLabels[$method][1] }} accent-text" aria-hidden="true"></i>
                            {{ $payLabels[$method][0] }}
                        </li>
                    @endif
                @endforeach
            </ul>

            @if($payAlias)
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl" style="background: var(--accent-soft); border: 1px solid var(--border);">
                    <span class="text-xs t-muted">Alias:</span>
                    <span class="font-bold tracking-wide text-sm">{{ $payAlias }}</span>
                    <button type="button" data-copy-alias="{{ $payAlias }}" aria-label="Copiar alias al portapapeles"
                            class="ml-1 w-8 h-8 inline-flex items-center justify-center rounded-lg accent-text hover:opacity-80">
                        <i class="fa-solid fa-copy text-xs" aria-hidden="true"></i>
                    </button>
                </div>
                <p id="copy-msg" class="text-xs mt-2 accent-text transition-opacity opacity-0" aria-live="polite"></p>
            @endif
        @endif

        @if($footerTxt)
            <p class="text-sm t-muted {{ $showPay ? 'mt-6' : '' }}">{{ $footerTxt }}</p>
        @endif
    </div>
</footer>
@endif
