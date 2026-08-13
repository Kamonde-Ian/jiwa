<div>
    @if (session()->has('trade'))
        @php $done = session('trade'); @endphp
        @if ($done['kind'] === 'allocated')
            <div class="alert alert-success d-flex align-items-center gap-3" role="alert">
                <i class="fa-solid fa-circle-check fa-xl"></i>
                <div>
                    <strong>Allocation placed!</strong>
                    ${{ number_format($done['amount'], 2) }} allocated to
                    {{ $pool->name }} at NAV ${{ number_format($done['nav'], 2) }}
                    ({{ number_format($done['units'], 6) }} units).
                </div>
            </div>
        @else
            <div class="alert alert-success d-flex align-items-center gap-3" role="alert">
                <i class="fa-solid fa-circle-check fa-xl"></i>
                <div>
                    <strong>Funds released!</strong>
                    ${{ number_format($done['amount'], 2) }} was returned to your
                    Earnings Wallet and is available to withdraw.
                </div>
            </div>
        @endif
    @endif

    <div class="deriv-hub">
        {{-- ======= Trader header ======= --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="derive-brand-mark">{{ $pool->symbol }}</div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="mb-0 fw-bold">{{ $pool->name }}</h4>
                        <span class="live-pill @if ($today['live']) live @endif">
                            <span class="pulse-dot"></span>{{ $today['live'] ? 'LIVE' : 'CLOSED' }}
                        </span>
                    </div>
                    <small class="text-muted">Custodial · Bot-managed pooled JIWA</small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-4 flex-grow-1 flex-wrap justify-content-md-end">
                <div class="ticker-block">
                    <small class="text-muted d-block" id="marketPairLabel">{{ $pair }}</small>
                    <div class="d-flex align-items-center gap-2">
                        <span class="ticker-price" id="marketPrice">—</span>
                        <span class="ticker-change" id="marketChange"></span>
                    </div>
                </div>
                <div class="ticker-block">
                    <small class="text-muted d-block">Open (window)</small>
                    <b id="marketOpen">—</b>
                </div>
                <div class="ticker-block">
                    <small class="text-muted d-block">Bot trades today</small>
                    <b>{{ number_format($today['trades']) }} trades</b>
                </div>
                <div class="ticker-block">
                    <small class="text-muted d-block">Withdrawable</small>
                    <b class="text-success">${{ number_format($withdrawable, 2) }}</b>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- ======= Chart ======= --}}
            <div class="col-12 col-xl-8">
                <div class="card h-100 deriv-chart-card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-2 gap-2">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                @foreach ($pairs as $p)
                                    <button type="button"
                                        wire:click="setPair('{{ $p['symbol'] }}')"
                                        data-market-pair="{{ $p['symbol'] }}"
                                        class="symbol-chip pair-chip {{ $p['symbol'] === $pair ? 'active' : '' }}"
                                        title="Chart {{ $p['label'] }}">
                                        {{ $p['symbol'] }}
                                    </button>
                                @endforeach
                                <span class="symbol-chip text-muted">
                                    <i class="fa-solid fa-database me-1"></i>Binance
                                </span>
                            </div>
                            <div class="range-switcher">
                                @foreach ($timeframes as $tf)
                                    <button type="button"
                                        wire:click="setTimeframe('{{ $tf }}')"
                                        data-market-timeframe="{{ $tf }}"
                                        class="{{ $tf === $timeframe ? 'active' : '' }}">
                                        {{ $tf }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div id="tradeMarketPanel"
                            data-pair="{{ $pair }}"
                            data-timeframe="{{ $timeframe }}"
                            data-proxy="{{ route('trade.market.klines') }}"
                            class="chart-fill deriv-chart"
                            style="height: 360px;">
                            <div class="market-loading">Loading live candles…</div>
                        </div>

                        <div class="ohlc-strip border-top mt-2 pt-2">
                            <span>O <b id="market-ohlc-open">—</b></span>
                            <span>H <b id="market-ohlc-high" class="text-success">—</b></span>
                            <span>L <b id="market-ohlc-low" class="text-danger">—</b></span>
                            <span>C <b id="market-ohlc-price">—</b></span>
                            <span class="text-muted">NAV = ${{ number_format($nav, 2) }}</span>
                            <span class="market-status text-muted" id="marketSourceStatus">Connecting to Binance…</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======= Trade panel ======= --}}
            <div class="col-12 col-xl-4">
                <div class="card h-100 deriv-trade-card">
                    <div class="card-body d-flex flex-column">
                        <div class="trade-tabs mb-3">
                            <button type="button" class="{{ $panel === 'allocate' ? 'active' : '' }}" wire:click="setPanel('allocate')">
                                Allocate
                            </button>
                            <button type="button" class="{{ $panel === 'withdraw' ? 'active' : '' }}" wire:click="setPanel('withdraw')">
                                Withdraw
                            </button>
                        </div>

                        @if ($panel === 'allocate')
                            <form wire:submit="allocate">
                                <label class="form-label small fw-semibold">Stake in the pooled fund (USD)</label>
                                <div class="input-group input-group-lg mb-2">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" wire:model.live="allocateAmount"
                                        class="form-control" placeholder="Minimum ${{ number_format($pool->min_allocate, 0) }}">
                                </div>
                                <div class="quick-chips d-flex gap-2 mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill" wire:click="setAllocatePercent(25)">25%</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill" wire:click="setAllocatePercent(50)">50%</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill" wire:click="setAllocatePercent(100)">Max</button>
                                </div>

                                <div class="trade-facts mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted small">Available principal</span>
                                        <b>${{ number_format($principal, 2) }}</b>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted small">NAV per unit</span>
                                        <b>${{ number_format($nav, 2) }}</b>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted small">Units you acquire</span>
                                        <b>{{ $allocateAmount ? number_format($allocateAmount / max($nav, 0.0001), 6) : '—' }}</b>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted small">Bot-managed daily return</span>
                                        <b>{{ $today['is_profit'] ? '+' : '' }}{{ number_format($today['change_pct'], 2) }}%</b>
                                    </div>
                                </div>

                                @error('allocateAmount')
                                    <p class="text-danger small">{{ $message }}</p>
                                @enderror

                                <button type="submit" class="btn btn-lg w-100 derive-buy"
                                    @if (!($allocateAmount > 0)) disabled @endif>
                                    <i class="fa-solid fa-rocket me-2"></i>Allocate to Bot Fund
                                </button>
                                <p class="text-center text-muted small mt-2 mb-0">
                                    <i class="fa-solid fa-lock me-1"></i>Custodial — the bot trades pooled deposits on your behalf.
                                </p>
                                <p class="text-center small mb-0 mt-1 {{ $today['is_profit'] ? '' : 'risk-tint' }}">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>Returns are variable. The bot can lose money on any given day, which reduces your position's value.
                                </p>
                            </form>
                        @else
                            <form wire:submit="withdraw">
                                <label class="form-label small fw-semibold">Release funds to Earnings Wallet (USD)</label>
                                <div class="input-group input-group-lg mb-2">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" wire:model.live="withdrawAmount"
                                        class="form-control" placeholder="Up to ${{ number_format($positionValue, 0) }}">
                                </div>
                                <div class="quick-chips d-flex gap-2 mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill" wire:click="setWithdrawPercent(25)">25%</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill" wire:click="setWithdrawPercent(50)">50%</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill" wire:click="setWithdrawPercent(100)">Max</button>
                                </div>

                                <div class="trade-facts mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted small">Current fund value</span>
                                        <b>${{ number_format($positionValue, 2) }}</b>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted small">Units held</span>
                                        <b>{{ number_format($units, 6) }}</b>
                                    </div>
                                </div>

                                @error('withdrawAmount')
                                    <p class="text-danger small">{{ $message }}</p>
                                @enderror

                                <button type="submit" class="btn btn-lg w-100 derive-sell"
                                    @if (!($withdrawAmount > 0)) disabled @endif>
                                    <i class="fa-solid fa-money-bill-wave me-2"></i>Release Funds
                                </button>
                                <p class="text-center text-muted small mt-2 mb-0">
                                    Released funds land in your Earnings Wallet, ready to withdraw.
                                </p>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ======= Position + results ======= --}}
        <div class="row g-4 mt-1">
            <div class="col-12 col-lg-5">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa-solid fa-briefcase me-2 text-primary"></i>Your Position</h5>
                        <span class="badge badge-soft-success">{{ $positionValue > 0 ? 'Active' : 'No position' }}</span>
                    </div>
                    <div class="card-body">
                        @if ($positionValue > 0)
                            <div class="row g-3 text-center mb-3">
                                <div class="col-4">
                                    <small class="text-muted d-block mb-1">Fund value</small>
                                    <div class="projection-value">${{ number_format($positionValue, 2) }}</div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block mb-1">Today's result</small>
                                    <div class="projection-value {{ $todayPnl >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $todayPnl >= 0 ? '+' : '' }}${{ number_format($todayPnl, 2) }}
                                    </div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block mb-1">Returns credited</small>
                                    <div class="projection-value text-success">${{ number_format($returnsCredited, 2) }}</div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Units held</span>
                                <b>{{ number_format($units, 6) }}</b>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Net cash deployed</span>
                                <b>${{ number_format(max($invested - $withdrawnFromPool, 0), 2) }}</b>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Total return (incl. unrealised)</span>
                                <b class="{{ $totalReturn >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $totalReturn >= 0 ? '+' : '' }}${{ number_format($totalReturn, 2) }}
                                    ({{ $returnPct >= 0 ? '+' : '' }}{{ number_format($returnPct, 2) }}%)
                                </b>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">Strategy</span>
                                <b class="small">{{ $today['strategy'] }}</b>
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="fa-solid fa-briefcase"></i>
                                <p class="mb-0">Allocate funds above to join the pooled bot fund and start earning daily results.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa-regular fa-calendar me-2 text-primary"></i>Daily Bot Results</h5>
                        <span class="text-muted small">
                            Last {{ $sessions->count() }} settled @if ($today['live']) · today LIVE @endif
                        </span>
                    </div>
                    <div class="card-body p-0">
                        @if ($sessions->isEmpty() && ! $today['live'])
                            <div class="empty-state">
                                <i class="fa-solid fa-calendar-days"></i>
                                <p class="mb-0">No bot sessions yet. Results will appear after the first daily cycle.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 results-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th class="text-end">Open</th>
                                            <th class="text-end">Close</th>
                                            <th class="text-end">Result</th>
                                            <th class="text-end">Trades</th>
                                            <th class="text-end">Pool P&L</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($today['live'])
                                            <tr class="today-live-row">
                                                <td>
                                                    <b>Today</b>
                                                    <span class="live-pill live ms-1"><span class="pulse-dot"></span>LIVE</span>
                                                    <small class="d-block text-muted">settles daily at 00:20 UTC</small>
                                                </td>
                                                <td class="text-end">${{ number_format($today['open'], 2) }}</td>
                                                <td class="text-end">${{ number_format($today['price'], 2) }}</td>
                                                <td class="text-end">
                                                    <span class="badge {{ $today['is_profit'] ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                                        {{ $today['is_profit'] ? '+' : '' }}{{ number_format($today['change_pct'], 2) }}%
                                                    </span>
                                                </td>
                                                <td class="text-end text-muted">{{ $today['trades'] }}</td>
                                                <td class="text-end text-muted">pending</td>
                                            </tr>
                                        @endif
                                        @foreach ($sessions as $session)
                                            <tr>
                                                <td class="text-muted">{{ $session->session_date->format('M j, Y') }}</td>
                                                <td class="text-end">${{ number_format((float) $session->open_nav, 2) }}</td>
                                                <td class="text-end">${{ number_format((float) $session->close_nav, 2) }}</td>
                                                <td class="text-end">
                                                    <span class="badge {{ $session->is_profit ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                                        {{ $session->is_profit ? '+' : '' }}{{ number_format((float) $session->return_pct, 2) }}%
                                                    </span>
                                                </td>
                                                <td class="text-end text-muted">{{ $session->trade_count }}</td>
                                                <td class="text-end {{ $session->pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $session->pnl >= 0 ? '+' : '-' }}${{ number_format(abs((float) $session->pnl), 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if ($dailyPayouts->isNotEmpty())
            <div class="row g-4 mt-1">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fa-solid fa-coins me-2 text-primary"></i>Your Daily Returns</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dailyPayouts as $payout)
                                            <tr>
                                                <td class="text-muted">{{ $payout->created_at->format('M j, Y g:i A') }}</td>
                                                <td>{{ $payout->description }}</td>
                                                <td class="text-end text-success">+${{ number_format((float) $payout->amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="row g-4 mt-1">
            <div class="col-12">
                <div class="card risk-disclaimer-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <i class="fa-solid fa-triangle-exclamation text-warning fa-lg mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-2">Risk Disclosure</h6>
                                <p class="small text-muted mb-2">
                                    Trading involves substantial risk of loss and is not suitable for all investors. The
                                    Bot Fund is a custodial pooled product whose daily returns are generated by an
                                    automated strategy — returns are <strong>variable</strong>. The bot can post losses
                                    on any given day, which reduce both your position's value and the NAV of the pooled
                                    fund. Losses reduce what you may ultimately withdraw.
                                </p>
                                <p class="small text-muted mb-0">
                                    Past performance does not guarantee future results. Nothing on this page, including
                                    the presented history, strategy label, or net asset value, constitutes financial,
                                    investment, or trading advice. You are solely responsible for your allocation
                                    decisions; only trade with funds you can afford to lose.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const SYMBOLS = { 'BTC/USDT': 'BTCUSDT', 'ETH/USDT': 'ETHUSDT', 'BNB/USDT': 'BNBUSDT' };
    const HOSTS = [
        'https://api.binance.com',
        'https://data-api.binance.vision',
        'https://api1.binance.com',
        'https://api2.binance.com',
    ];
    const TIMEOUT = 8000;
    const LIMIT = 250;
    const cache = new Map();

    let panel = null;
    let chart = null;
    let state = { pair: 'BTC/USDT', tf: '5m' };
    let started = false;

    function symbolFor(pair) {
        return SYMBOLS[pair] || pair.replace('/', '').toUpperCase();
    }

    function toCandles(rows) {
        return rows.map(function (r) {
            if (Array.isArray(r.y)) {
                return { x: r.x, y: [parseFloat(r.y[0]), parseFloat(r.y[1]), parseFloat(r.y[2]), parseFloat(r.y[3]), parseFloat(r.y[4])] };
            }
            return { x: r[0], y: [parseFloat(r[1]), parseFloat(r[2]), parseFloat(r[3]), parseFloat(r[4]), parseFloat(r[5])] };
        });
    }

    async function fetchFrom(url) {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), TIMEOUT);
        try {
            const res = await fetch(url, { signal: controller.signal, mode: 'cors' });
            if (!res.ok) return null;
            const rows = await res.json();
            const data = Array.isArray(rows) && Array.isArray(rows[0]) ? rows : (rows && rows.candles);
            if (!Array.isArray(data) || data.length === 0) return null;
            return toCandles(data);
        } catch (e) {
            return null;
        } finally {
            clearTimeout(timer);
        }
    }

    async function fetchKlines(pair, tf, limit) {
        const key = pair + '|' + tf;
        if (cache.has(key)) return cache.get(key);

        const symbol = encodeURIComponent(symbolFor(pair));
        const interval = encodeURIComponent(tf);

        const sources = HOSTS.map(function (host) {
            return host + '/api/v3/klines?symbol=' + symbol + '&interval=' + interval + '&limit=' + limit;
        });
        if (panel && panel.dataset.proxy) {
            sources.push(panel.dataset.proxy + '?pair=' + encodeURIComponent(pair) + '&interval=' + tf + '&limit=' + limit);
        }

        const results = await Promise.all(sources.map(fetchFrom));
        const candles = results.find(Boolean) || null;
        if (candles) cache.set(key, candles);
        return candles;
    }

    function fmt(n, d) {
        if (n === null || n === undefined || isNaN(n)) return '—';
        return Number(n).toLocaleString('en-US', {
            minimumFractionDigits: d || 2,
            maximumFractionDigits: d || 2,
        });
    }

    function summarize(candles) {
        if (!candles || !candles.length) return null;
        const open = candles[0].y[0];
        let high = candles[0].y[1];
        let low = candles[0].y[2];
        const close = candles[candles.length - 1].y[3];

        candles.forEach(function (c) {
            if (c.y[1] > high) high = c.y[1];
            if (c.y[2] < low) low = c.y[2];
        });

        const change = open > 0 ? ((close - open) / open) * 100 : 0;

        return { price: close, open: open, high: high, low: low, change_pct: change, is_profit: change >= 0 };
    }

    function swingSeries(candles) {
        const pts = candles.map(function (c) { return { x: c.x, h: c.y[1], l: c.y[2], close: c.y[3] }; });
        const n = pts.length;
        if (n === 0) return [];
        if (n < 3) return pts.map(function (p) { return { x: p.x, y: p.close }; });

        const span = Math.max.apply(null, pts.map(function (p) { return p.h; }))
            - Math.min.apply(null, pts.map(function (p) { return p.l; }));
        const minMove = Math.max(span * 0.02, 0.01);
        const trendsUp = pts[n - 1].close >= pts[0].close;

        const swings = [];
        let pivot = pts[0];
        let lookingForHigh = trendsUp;

        for (let i = 1; i < n; i++) {
            const p = pts[i];
            if (lookingForHigh) {
                if (p.h > pivot.h) { pivot = p; continue; }
                if (pivot.h - p.l >= minMove) {
                    swings.push({ x: pivot.x, y: pivot.h });
                    pivot = p;
                    lookingForHigh = false;
                }
                continue;
            }
            if (p.l < pivot.l) { pivot = p; continue; }
            if (p.h - pivot.l >= minMove) {
                swings.push({ x: pivot.x, y: pivot.l });
                pivot = p;
                lookingForHigh = true;
            }
        }

        swings.push({ x: pivot.x, y: lookingForHigh ? pivot.h : pivot.l });

        const line = [];
        if (swings[0].x === pts[0].x) {
            line.push({ x: swings[0].x, y: +swings[0].y.toFixed(2) });
        } else {
            line.push({ x: pts[0].x, y: +pts[0].close.toFixed(2) });
        }
        swings.forEach(function (s) {
            if (line[line.length - 1].x !== s.x) line.push({ x: s.x, y: +s.y.toFixed(2) });
        });
        if (line[line.length - 1].x !== pts[n - 1].x) {
            line.push({ x: pts[n - 1].x, y: +pts[n - 1].close.toFixed(2) });
        }
        return line;
    }

    function chartIsDark() {
        return document.documentElement.getAttribute('data-theme') === 'dark'
            || document.documentElement.classList.contains('dark');
    }

    function buildConfig(candles, pair, tf) {
        const line = swingSeries(candles);
        return {
            chart: { type: 'candlestick', toolbar: { show: false }, height: Math.max(260, panel.clientHeight || 360) },
            series: [
                { name: pair, type: 'candlestick', data: candles },
                { name: 'Trend', type: 'line', color: '#C8942A', data: line },
            ],
            xaxis: { type: 'datetime' },
            plotOptions: { candlestick: { colors: { upward: '#71dd37', downward: '#ff5b5b' } } },
            stroke: { width: 2 },
            dataLabels: { enabled: false },
            tooltip: { shared: false },
            legend: { show: false },
            grid: { borderColor: 'rgba(216,168,57,0.15)' },
        };
    }

    function applyTheme() {
        if (!chart) return;
        const dark = chartIsDark();
        const fg = dark ? '#C9BFA3' : '#566a7f';
        chart.updateOptions({
            chart: { foreColor: fg },
            grid: { borderColor: dark ? 'rgba(216,168,57,0.2)' : '#eceef1' },
            xaxis: { labels: { style: { colors: fg } } },
            yaxis: { labels: { style: { colors: fg } } },
        }, false, false);
    }

    function setStatus(msg, ok) {
        const el = document.getElementById('marketSourceStatus');
        if (!el) return;
        el.textContent = msg;
        el.classList.toggle('text-success', !!ok);
        el.classList.toggle('text-danger', !ok && ok !== undefined);
    }

    function updateUI(pair, candles) {
        const tick = summarize(candles);
        const label = document.getElementById('marketPairLabel');
        const priceEl = document.getElementById('marketPrice');
        const openEl = document.getElementById('marketOpen');
        const changeEl = document.getElementById('marketChange');

        label.textContent = pair;
        openEl.textContent = tick ? '$' + fmt(tick.open) : '—';

        if (!tick) {
            priceEl.textContent = '—';
            changeEl.textContent = '';
            ['market-ohlc-open', 'market-ohlc-high', 'market-ohlc-low', 'market-ohlc-price'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.textContent = '—';
            });
            setStatus('Market data unavailable', false);
            return;
        }

        priceEl.textContent = '$' + fmt(tick.price);
        changeEl.textContent = (tick.is_profit ? '+' : '') + fmt(Math.abs(tick.change_pct)) + '%';
        changeEl.className = 'ticker-change ' + (tick.is_profit ? 'up' : 'down');

        document.getElementById('market-ohlc-open').textContent = '$' + fmt(tick.open);
        document.getElementById('market-ohlc-high').textContent = '$' + fmt(tick.high);
        document.getElementById('market-ohlc-low').textContent = '$' + fmt(tick.low);
        document.getElementById('market-ohlc-price').textContent = '$' + fmt(tick.price);

        setStatus('Live · Binance', true);
    }

    function setActive(pair, tf) {
        document.querySelectorAll('.pair-chip').forEach(function (b) {
            b.classList.toggle('active', b.dataset.marketPair === pair);
        });
        document.querySelectorAll('.range-switcher button').forEach(function (b) {
            b.classList.toggle('active', b.dataset.marketTimeframe === tf);
        });
    }

    async function renderChart(force) {
        if (!panel) return;
        if (!force && state.pair === panel.dataset.pair && state.tf === panel.dataset.timeframe && chart) return;

        state = { pair: panel.dataset.pair || 'BTC/USDT', tf: panel.dataset.timeframe || '5m' };
        setActive(state.pair, state.tf);

        if (chart) {
            chart.destroy();
            chart = null;
        }
        panel.innerHTML = '<div class="market-loading">Loading live candles…</div>';

        const candles = await fetchKlines(state.pair, state.tf, LIMIT);

        if (state.pair !== panel.dataset.pair || state.tf !== panel.dataset.timeframe) return;

        if (!candles) {
            updateUI(state.pair, null);
            panel.innerHTML = '<div class="empty-state"><i class="fa-solid fa-chart-line"></i><p class="mb-2">Live market data is unavailable right now.</p><button type="button" class="btn btn-sm btn-outline-primary market-retry">Retry</button></div>';
            return;
        }

        updateUI(state.pair, candles);
        chart = new ApexCharts(panel, buildConfig(candles, state.pair, state.tf));
        chart.render();
        applyTheme();
    }

    function reconcile() {
        const el = document.getElementById('tradeMarketPanel');
        if (!el) return;
        if (panel !== el) {
            if (chart) { chart.destroy(); chart = null; }
            panel = el;
        }
        setActive(el.dataset.pair || 'BTC/USDT', el.dataset.timeframe || '5m');
        renderChart(false);
    }

    document.addEventListener('click', function (e) {
        if (!panel) return;
        const retry = e.target.closest('.market-retry');
        if (retry) {
            renderChart(true);
            return;
        }
        const pairBtn = e.target.closest('.pair-chip');
        if (pairBtn) {
            const pair = pairBtn.dataset.marketPair;
            if (pair && pair !== panel.dataset.pair) {
                panel.dataset.pair = pair;
                renderChart(true);
            }
            return;
        }
        const tfBtn = e.target.closest('.range-switcher button');
        if (tfBtn) {
            const tf = tfBtn.dataset.marketTimeframe;
            if (tf && tf !== panel.dataset.timeframe) {
                panel.dataset.timeframe = tf;
                renderChart(true);
            }
        }
    });

    window.addEventListener('theme-changed', applyTheme);
    window.addEventListener('resize', function () {
        if (!chart) return;
        chart.updateOptions({ chart: { height: Math.max(260, panel.clientHeight || 360) } }, false, false);
    });

    function registerHooks() {
        if (window.Livewire && !window.__tradeHooksRegistered) {
            window.__tradeHooksRegistered = true;
            window.Livewire.hook('morph.updated', reconcile);
        }
    }

    function start() {
        if (started) return;
        panel = document.getElementById('tradeMarketPanel');
        if (!panel) return;
        started = true;

        registerHooks();
        document.addEventListener('livewire:init', registerHooks);
        document.addEventListener('livewire:navigated', registerHooks);

        renderChart(false);
    }

    // sneat.js sets window.ApexCharts in a deferred module, so wait for it
    // before booting instead of bailing silently.
    function tryStart() {
        if (window.ApexCharts && document.getElementById('tradeMarketPanel')) {
            start();
            return true;
        }
        return false;
    }

    if (!tryStart()) {
        const interval = setInterval(function () {
            if (tryStart()) clearInterval(interval);
        }, 100);
        window.addEventListener('load', function () { tryStart(); clearInterval(interval); });
    }
})();
</script>
@endpush
