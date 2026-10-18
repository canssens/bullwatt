const TRAININGS_URL = './trainings/trainings.json';
const TRAINING_CARDS_SELECTOR = '#trainingCards';
const TRAINING_DETAILS_URL = 'star-bike.html';

function getI18nLabel(key, fallback) {
    if (typeof window.i18n === 'undefined' || typeof window.i18n.t !== 'function') {
        return fallback;
    }

    return window.i18n.t(key);
}

function getPowerColor(ratio) {
    if (ratio < 0.6) return '#93CE07';
    if (ratio < 0.76) return '#FBDB0F';
    if (ratio < 0.9) return '#FC7D02';
    if (ratio < 1.05) return '#FD0100';
    if (ratio <= 1.2) return '#AA069F';
    return '#AC3B2A';
}



function buildTrainingGraph(phases = [], duration) {
    return phases.flatMap((phase, index) => {
        const start = Number(phase.start);
        const end = Number(phases[index + 1]?.start ?? duration);
        const ratio = Number(phase.ftp_ratio);

        if (!Number.isFinite(start) || !Number.isFinite(end) ||
            !Number.isFinite(ratio) || start < 0 || end <= start || ratio < 0) {
            return [];
        }

        return [{ value: [start, end, ratio], itemStyle: { color: getPowerColor(ratio) } }];
    });
}

function renderPowerBlock(params, api) {
    const start = api.coord([api.value(0), api.value(2)]);
    const end = api.coord([api.value(1), 0]);
    const width = end[0] - start[0];
    const gap = Math.min(2, width * 0.15);

    return {
        type: 'rect',
        shape: {
            x: start[0] + gap / 2,
            y: start[1],
            width: Math.max(0, width - gap),
            height: Math.max(0, end[1] - start[1]),
            r: [Math.min(3, width / 4), Math.min(3, width / 4), 0, 0]
        },
        style: api.style()
    };
}

function buildChartOptions(trainingGraph) {
    return {
        backgroundColor: 'transparent',
        grid: { left: 24, right: 24, top: 12, bottom: 24 },
        xAxis: {
            type: 'value',
            min: 0,
            max: trainingGraph.length ? trainingGraph[trainingGraph.length - 1].value[1] : 1,
            splitNumber: 3,
            splitLine: { show: false },
            axisLine: { lineStyle: { color: '#777' } },
            axisTick: { show: false },
            axisLabel: {
                color: '#aaa',
                hideOverlap: true,
                formatter: (seconds) => `${Math.floor(seconds / 60)}:${String(Math.floor(seconds % 60)).padStart(2, '0')}`
            }
        },
        yAxis: {
            type: 'value',
            min: 0,
            max: (extent) => Math.max(1.2, extent.max * 1.1),
            splitNumber: 3,
            splitLine: { lineStyle: { color: 'rgba(255, 255, 255, 0.12)' } },
            axisLine: { show: false },
            axisTick: { show: false },
            axisLabel: { show: false }
        },
        series: [{
            type: 'custom',
            renderItem: renderPowerBlock,
            encode: { x: [0, 1], y: 2 },
            data: trainingGraph,
            silent: true
        }]
    };
}

function createTrainingCard(training) {
    const col = document.createElement('div');
    col.className = 'col';

    const chartId = `chartTraining${training.id}`;
    col.innerHTML = `
        <div class="card h-100">
            <a href="${TRAINING_DETAILS_URL}?training=${training.id}" class="text-decoration-none text-light">
                <div class="card-body">
                    <h5 class="card-title">${training.training_name}</h5>
                    <p class="card-text">${training.description}</p>
                    <div id="${chartId}" style="width: 100%; height: 160px;"></div>
                </div>
            </a>
        </div>
    `;

    return { card: col, chartId };
}

function createContactCard() {
    const col = document.createElement('div');
    col.className = 'col';
    col.innerHTML = `
        <div class="card h-100">
            <a href="#contact" class="text-decoration-none text-light">
                <div class="card-body">
                    <h5 class="card-title" data-i18n="training.need_more">${getI18nLabel('training.need_more', 'Need more training ?')}</h5>
                    <p class="card-text" data-i18n="training.contact_me">${getI18nLabel('training.contact_me', 'Contact me')}</p>
                </div>
            </a>
        </div>
    `;

    return col;
}

function createMCPCard() {
    const col = document.createElement('div');
    col.className = 'col';
    col.innerHTML = `
        <div class="card h-100">
            <a href="articles/create-workout-with-mcp.html" class="text-decoration-none text-light">
                <div class="card-body">
                    <h5 class="card-title" data-i18n="training.need_more_mcp">${getI18nLabel('training.need_more_mcp', 'Training with AI?')}</h5>
                    <p class="card-text" data-i18n="training.need_more_mcp2">${getI18nLabel('training.need_more_mcp2', 'Create your own workout with ChatGPT or Claude via MCP')}</p>
                </div>
            </a>
        </div>
    `;

    return col;
}

function renderTrainingChart(chartId, trainingGraph) {
    if (typeof window.echarts === 'undefined') {
        return null;
    }

    const chartDom = document.getElementById(chartId);
    if (!chartDom) {
        return null;
    }

    const chart = window.echarts.init(chartDom);
    chart.setOption(buildChartOptions(trainingGraph));
    return chart;
}

async function fetchTrainings() {
    const response = await fetch(TRAININGS_URL);

    if (!response.ok) {
        throw new Error(`Unable to load trainings: ${response.status}`);
    }

    return response.json();
}

export async function initHomeTrainingCatalog() {
    const trainingCards = document.querySelector(TRAINING_CARDS_SELECTOR);
    if (!trainingCards) {
        return;
    }

    try {
        const trainings = await fetchTrainings();
        const charts = [];

        trainings.forEach((training) => {
            const { card, chartId } = createTrainingCard(training);
            trainingCards.appendChild(card);

            const trainingGraph = buildTrainingGraph(training.phases, training.duration);
            const chart = renderTrainingChart(chartId, trainingGraph);
            if (chart) {
                charts.push(chart);
            }
        });

        trainingCards.appendChild(createContactCard());
        trainingCards.appendChild(createMCPCard());

        if (charts.length > 0) {
            window.addEventListener('resize', () => {
                charts.forEach((chart) => chart.resize());
            });
        }
    } catch (error) {
        console.error('There was a problem with the training catalog fetch operation:', error);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHomeTrainingCatalog);
} else {
    initHomeTrainingCatalog();
}
