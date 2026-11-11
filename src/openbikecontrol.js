// OpenBikeControl controller support (https://github.com/OpenBikeControl/openbikecontrol-protocol)
export const OBC_SERVICE_UUID = 'd273f680-d548-419d-b9d1-fa0472345229';
const BUTTON_STATE_UUID = 'd273f681-d548-419d-b9d1-fa0472345229';
const APP_INFO_UUID = 'd273f683-d548-419d-b9d1-fa0472345229';

const MESSAGE_TYPE_BUTTON_STATE = 0x01;
const MESSAGE_TYPE_APP_INFO = 0x04;
const APP_INFO_VERSION = 0x01;
const APP_ID = 'bullwatt';
const APP_VERSION = '1.0.0';

// Button IDs ordered by priority: a controller may send several IDs for one press
// (e.g. Shift Up + Increase Difficulty + Select), only the first known one is used.
// BullWatt is always in ERG mode, so difficulty wins over shifting.
const BUTTON_ACTIONS = [
    [0x30, 'increase'], // Increase Difficulty
    [0x31, 'decrease'], // Decrease Difficulty
    [0x01, 'increase'], // Shift Up
    [0x02, 'decrease'], // Shift Down
    [0x33, 'pause'],    // Pause
    [0x34, 'resume']    // Resume
];

export const SUPPORTED_BUTTON_IDS = BUTTON_ACTIONS.map(([buttonId]) => buttonId);

export function parseButtonState(bytes) {
    if (bytes.length === 0 || bytes[0] !== MESSAGE_TYPE_BUTTON_STATE) {
        return [];
    }

    const changes = [];
    for (let i = 1; i + 1 < bytes.length; i += 2) {
        changes.push({ buttonId: bytes[i], state: bytes[i + 1] });
    }
    return changes;
}

function encodeText(text) {
    const bytes = Array.from(new TextEncoder().encode(text)).slice(0, 32);
    return [bytes.length, ...bytes];
}

export function encodeAppInfo() {
    return Uint8Array.from([
        MESSAGE_TYPE_APP_INFO,
        APP_INFO_VERSION,
        ...encodeText(APP_ID),
        ...encodeText(APP_VERSION),
        SUPPORTED_BUTTON_IDS.length,
        ...SUPPORTED_BUTTON_IDS
    ]);
}

// Messages only carry the buttons that changed, so the held buttons are tracked
// to turn the IDs newly pressed in one message into a single action.
export function createButtonTracker() {
    const heldButtonIds = new Set();

    return function getActionForMessage(bytes) {
        const newlyPressed = [];
        for (const { buttonId, state } of parseButtonState(bytes)) {
            if (state === 0) {
                heldButtonIds.delete(buttonId);
            } else if (!heldButtonIds.has(buttonId)) {
                heldButtonIds.add(buttonId);
                newlyPressed.push(buttonId);
            }
        }

        const match = BUTTON_ACTIONS.find(([buttonId]) => newlyPressed.includes(buttonId));
        return match ? match[1] : null;
    };
}

export async function connectOpenBikeControl(device, { onAction, onDisconnect }) {
    const getActionForMessage = createButtonTracker();

    device.addEventListener('gattserverdisconnected', onDisconnect);
    const server = await device.gatt.connect();
    const service = await server.getPrimaryService(OBC_SERVICE_UUID);

    const buttonState = await service.getCharacteristic(BUTTON_STATE_UUID);
    buttonState.addEventListener('characteristicvaluechanged', (event) => {
        const value = event.target.value;
        const action = getActionForMessage(new Uint8Array(value.buffer, value.byteOffset, value.byteLength));
        if (action !== null) {
            onAction(action);
        }
    });
    await buttonState.startNotifications();

    // App Information is optional for the controller, it still works without it
    try {
        const appInfo = await service.getCharacteristic(APP_INFO_UUID);
        await appInfo.writeValueWithResponse(encodeAppInfo());
    } catch (error) {
        console.warn('OpenBikeControl app information not sent: ' + error.message);
    }
}
