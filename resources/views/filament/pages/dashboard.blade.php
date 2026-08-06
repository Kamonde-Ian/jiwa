<x-filament-panels::page class="fi-dashboard-page">
    <div class="admin-hero">
        <div class="admin-hero-cols">
            <div class="admin-hero-copy">
                <h1>Welcome back, {{ auth()->user()->name }}</h1>
                <p>Here's a snapshot of platform activity today.</p>
            </div>
            <div class="admin-hero-stats">
                @foreach ($this->getHeroStats() as $stat)
                    <div class="admin-hero-stat">
                        <small>{{ $stat['label'] }}</small>
                        <b>{{ $stat['value'] }}</b>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="$this->getWidgetData()"
        :widgets="$this->getVisibleWidgets()"
    />
</x-filament-panels::page>
