const USER_UUID_STORAGE_KEY = 'myuuid';
const FTP_STORAGE_KEY = 'myFTP';
const UNIT_SYSTEM_STORAGE_KEY = 'unitSystem';
const ATHLETE_WEIGHT_STORAGE_KEY = 'athleteWeight';
const MILES_COUNTRY_CODES = ['US', 'GB', 'MM', 'LR'];

function generateUuid() {
    return '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, (character) =>
        (+character ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> +character / 4).toString(16)
    );
}

function persistUserCookie(uuid) {
    const expirationDate = new Date();
    expirationDate.setFullYear(expirationDate.getFullYear() + 100);
    document.cookie = `userBullWatt=${uuid};expires=${expirationDate.toUTCString()};path=/`;
}

function getStoredUuid() {
    return localStorage.getItem(USER_UUID_STORAGE_KEY);
}

export function initializeUserUuid() {
    let uuid = getStoredUuid();

    if (!uuid) {
        uuid = generateUuid();
        localStorage.setItem(USER_UUID_STORAGE_KEY, uuid);
    }

    persistUserCookie(uuid);
    window.myUUID = uuid;
    return uuid;
}

export async function checkConnectionStrava() {
    console.log('Checking Strava connection status...');

    try {
        const response = await fetch('./service/strava.php');
        if (!response.ok) {
            throw new Error(`Strava connection check failed: ${response.status}`);
        }

        const data = await response.json();
        if (data.message === 'logged') {
            const stravaLink = document.getElementById('stravaLink');
            if (stravaLink) {
                stravaLink.innerHTML = 'logout Strava';
                stravaLink.href = '/service/strava.php?logout=true';
            }
        }

        if (data['athlete.weight']) {
            localStorage.setItem(ATHLETE_WEIGHT_STORAGE_KEY, data['athlete.weight']);
        }

        return data;
    } catch (error) {
        console.error('Error strava connection', error);
        return null;
    }
}

export function pushTechnicalLog(messageLog) {
    const uuid = initializeUserUuid();
    const params = new URLSearchParams({
        uuid,
        log: String(messageLog)
    });

    if (window.samplingLog === 0) {
        window.samplingLog = -1;
        params.set('done', '1');
    }

    fetch('./service/technical_log.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params.toString()
    }).catch(() => {
        // Avoid logging here because star-bike overrides console.log/error through this function.
    });
}

export function updateSliderValue(value) {
    const valueDisplay = document.getElementById('sliderValue');
    if (valueDisplay) {
        valueDisplay.textContent = value;
    }

    localStorage.setItem(FTP_STORAGE_KEY, String(value));
}

export function getFTP() {
    const ftp = Number.parseInt(localStorage.getItem(FTP_STORAGE_KEY), 10);

    if (!ftp || Number.isNaN(ftp)) {
        return 200;
    }

    return ftp;
}

export function detectAndSetUnitSystem() {
    const storedUnitSystem = localStorage.getItem(UNIT_SYSTEM_STORAGE_KEY);
    if (storedUnitSystem === 'miles' || storedUnitSystem === 'metric') {
        return storedUnitSystem;
    }

    let prefersMiles = false;

    try {
        const locales = navigator.languages && navigator.languages.length
            ? navigator.languages
            : [navigator.language || navigator.userLanguage || 'en'];

        for (const locale of locales) {
            const parts = locale.split('-');
            if (parts.length >= 2) {
                const country = parts[parts.length - 1].toUpperCase();
                prefersMiles = MILES_COUNTRY_CODES.includes(country);
                break;
            }
        }
    } catch (error) {
        console.warn('Could not detect locale for unit system:', error);
    }

    const unitSystem = prefersMiles ? 'miles' : 'metric';
    localStorage.setItem(UNIT_SYSTEM_STORAGE_KEY, unitSystem);
    return unitSystem;
}

export function getUnitSystem() {
    return localStorage.getItem(UNIT_SYSTEM_STORAGE_KEY) === 'miles' ? 'miles' : 'metric';
}

export function setUnitSystem(unit) {
    const unitSystem = unit === 'miles' ? 'miles' : 'metric';
    localStorage.setItem(UNIT_SYSTEM_STORAGE_KEY, unitSystem);
    return unitSystem;
}

export function isMiles() {
    return getUnitSystem() === 'miles';
}

function exposeLegacyGlobals() {
    window.initializeUserUuid = initializeUserUuid;
    window.checkConnectionStrava = checkConnectionStrava;
    window.pushTechnicalLog = pushTechnicalLog;
    window.updateSliderValue = updateSliderValue;
    window.getFTP = getFTP;
    window.detectAndSetUnitSystem = detectAndSetUnitSystem;
    window.getUnitSystem = getUnitSystem;
    window.setUnitSystem = setUnitSystem;
    window.isMiles = isMiles;
}

initializeUserUuid();
exposeLegacyGlobals();
