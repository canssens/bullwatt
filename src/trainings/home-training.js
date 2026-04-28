const TRAININGS_URL = './trainings/trainings.json';
const TRAINING_CARDS_SELECTOR = '#trainingCards';
const TRAINING_DETAILS_URL = 'star-bike.html';

function getI18nLabel(key, fallback) {
    if (typeof window.i18n === 'undefined' || typeof window.i18n.t !== 'function') {
        return fallback;
    }

    return window.i18n.t(key);
}

function buildTrainingGraph(phases = []) {
    return phases.map((phase) => [Number(phase.start), Number(phase.value)]);
}

function buildChartOptions(trainingGraph) {
    return {
        backgroundColor: '#000',
        xAxis: {
            type: 'value',
            splitLine: { show: false },
            axisLine: { show: false },
            axisTick: { show: false },
            axisLabel: { show: false }
        },
        yAxis: {
            type: 'value',
            min: 'dataMin',
            axisLabel: { show: false }
        },
        visualMap: {
            show: false,
            pieces: [
                { gt: 0, lte: 0.6, color: '#93CE07' },
                { gt: 0.6, lte: 0.75, color: '#FBDB0F' },
                { gt: 0.75, lte: 0.9, color: '#FC7D02' },
                { gt: 0.9, lte: 1.05, color: '#FD0100' },
                { gt: 1.05, lte: 1.2, color: '#AA069F' },
                { gt: 1.2, color: '#AC3B2A' }
            ]
        },
        series: [
            {
                data: trainingGraph,
                type: 'line',
                smooth: false,
                step: 'end'
            }
        ]
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
                    <div id="${chartId}" style="width: 100%; height: 100%;"></div>
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

            const trainingGraph = buildTrainingGraph(training.phases);
            const chart = renderTrainingChart(chartId, trainingGraph);
            if (chart) {
                charts.push(chart);
            }
        });

        trainingCards.appendChild(createContactCard());

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
