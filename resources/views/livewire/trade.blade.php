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
                    <small class="text-muted d-block">{{ $pair }}</small>
                    <div class="d-flex align-items-center gap-2">
                        @if ($market['live'])
                            <span class="ticker-price">${{ number_format($market['price'], 2) }}</span>
                            <span class="ticker-change {{ $market['is_profit'] ? 'up' : 'down' }}">
                                {{ $market['is_profit'] ? '+' : '' }}{{ number_format($market['change_pct'], 2) }}%
                            </span>
                        @else
                            <span class="ticker-price">—</span>
                        @endif
                    </div>
                </div>
                <div class="ticker-block">
                    <small class="text-muted d-block">Open</small>
                    <b>{{ $market['live'] ? '$'.number_format($market['open'], 2) : '—' }}</b>
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
                                        class="symbol-chip pair-chip {{ $p['symbol'] === $pair ? 'active' : '' }}"
                                        wire:click="setPair('{{ $p['symbol'] }}')"
                                        title="Chart {{ $p['label'] }}">
                                        {{ $p['symbol'] }}
                                    </button>
                                @endforeach
                                <span class="symbol-chip text-muted">
                                    <i class="fa-solid fa-database me-1"></i>Binance · {{ $timeframe }}
                                </span>
                            </div>
                            <div class="range-switcher">
                                @foreach ($timeframes as $tf)
                                    <button type="button"
                                        class="{{ $tf === $timeframe ? 'active' : '' }}"
                                        wire:click="setTimeframe('{{ $tf }}')">
                                        {{ $tf }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        @if (($chartConfig['series'][0]['data'] ?? null))
                            <div data-chart='@json($chartConfig)' class="chart-fill deriv-chart" style="height: 360px;"></div>
                        @else
                            <div class="empty-state">
                                <i class="fa-solid fa-chart-line"></i>
                                <p class="mb-0">Live market data is unavailable right now. The chart will appear once Binance is reachable.</p>
                            </div>
                        @endif

                        <div class="ohlc-strip border-top mt-2 pt-2">
                            <span>O <b>{{ $market['live'] ? '$'.number_format($market['open'], 2) : '—' }}</b></span>
                            <span>H <b class="text-success">{{ $market['live'] ? '$'.number_format($market['high'], 2) : '—' }}</b></span>
                            <span>L <b class="text-danger">{{ $market['live'] ? '$'.number_format($market['low'], 2) : '—' }}</b></span>
                            <span>C <b>{{ $market['live'] ? '$'.number_format($market['price'], 2) : '—' }}</b></span>
                            <span class="text-muted">NAV = ${{ number_format($nav, 2) }} · {{ $today['live'] ? 'settling at 00:20 UTC' : 'settled' }}</span>
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
                        <span class="text-muted small">Last {{ $sessions->count() }} sessions</span>
                    </div>
                    <div class="card-body p-0">
                        @if ($sessions->isEmpty())
                            <div class="empty-state">
                                <i class="fa-solid fa-calendar-days"></i>
                                <p class="mb-0">No bot sessions yet. Results will appear after the first daily cycle.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
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
