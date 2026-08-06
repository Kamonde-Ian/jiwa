import * as bootstrap from 'bootstrap';
import ApexCharts from 'apexcharts';

window.bootstrap = bootstrap;
window.ApexCharts = ApexCharts;

const mountedCharts = new Map();

function chartHasData(config) {
    const series = config && config.series;
    if (!Array.isArray(series) || series.length === 0) return false;

    return series.some((s) => {
        if (typeof s === 'number') return s !== 0;
        if (Array.isArray(s)) return s.length > 0;
        return Array.isArray(s && s.data) && s.data.length > 0;
    });
}

function mountChart(element) {
    if (!window.ApexCharts || !(element instanceof HTMLElement)) return;
    if (!element.dataset.chart) return;

    let config;
    try {
        config = JSON.parse(element.dataset.chart);
    } catch (e) {
        return;
    }

    if (!chartHasData(config)) return;

    const existing = mountedCharts.get(element);
    if (existing && existing.src === element.dataset.chart) return;

    if (existing) {
        if (typeof existing.unfit === 'function') existing.unfit();
        existing.chart.destroy();
        mountedCharts.delete(element);
    }

    const chart = new window.ApexCharts(element, config);
    chart.render();

    const entry = { chart, src: element.dataset.chart };

    if (element.classList.contains('chart-fill')) {
        const fit = () => {
            const height = Math.max(240, element.clientHeight);
            chart.updateOptions({ chart: { height } }, false, false);
        };
        fit();
        window.addEventListener('resize', fit);
        entry.unfit = () => window.removeEventListener('resize', fit);
    }

    mountedCharts.set(element, entry);
}

function mountAllCharts() {
    document.querySelectorAll('[data-chart]').forEach(mountChart);
}

document.addEventListener('DOMContentLoaded', mountAllCharts);
document.addEventListener('livewire:init', mountAllCharts);
document.addEventListener('livewire:navigated', mountAllCharts);
document.addEventListener('livewire:init', () => {
    if (!window.Livewire) return;
    window.Livewire.hook('morph.updated', mountAllCharts);
    window.Livewire.hook('morph.added', mountAllCharts);
});

document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('layout-menu-toggle');
    if (toggle) {
        toggle.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
        });
    }

    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((el) => {
        new bootstrap.Dropdown(el);
    });
});

function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        return navigator.clipboard.writeText(text);
    }
    return new Promise((resolve, reject) => {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            resolve();
        } catch (e) {
            reject(e);
        }
        document.body.removeChild(textarea);
    });
}

function flashCopySuccess(trigger) {
    const icon = trigger.querySelector('i');
    const label = trigger.dataset.copyLabel || 'Copy';
    if (icon) {
        const original = icon.className;
        icon.className = 'fa-solid fa-check';
        setTimeout(() => { icon.className = original; }, 1500);
    }
    if (label && trigger.textContent.includes(label)) {
        const original = trigger.innerHTML;
        trigger.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        setTimeout(() => { trigger.innerHTML = original; }, 1500);
    }
}

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-copy]');
    if (!trigger) return;
    event.preventDefault();
    const text = trigger.getAttribute('data-copy');
    if (!text) return;
    copyToClipboard(text)
        .then(() => flashCopySuccess(trigger))
        .catch(() => {});
});
