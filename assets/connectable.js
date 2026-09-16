// Import the project Auuki Connectable
import Connectable from '../dependencies/auuki/src/ble/connectable.js';
window.Connectable = Connectable;

import { indoorBikeData } from '../dependencies/auuki/src/ble/ftms/indoor-bike-data.js';
window.indoorBikeData = indoorBikeData;

import { HeartRateMeasurement } from '../dependencies/auuki/src/ble/hrs/heart-rate-measurement.js';
window.HeartRateMeasurement = HeartRateMeasurement;