# Bullwatt catalog audit

Audit performed against the 71 JSON source files in `trainings/catalog` during MCP implementation.

## Consistent properties

- All 71 files are valid JSON.
- Every filename matches its training `id`; ids are unique.
- All sessions contain the expected top-level fields, except one older file without optional `creation_date` and `source`.
- Every phase uses `start`, `ftp_ratio`, and `notes`.
- Every first phase starts at zero.
- Phase starts are ordered increasingly in every session.
- Every session has a final marker whose start equals its declared duration.
- All time units are `seconds`.
- No catalog file defines tags or categories.

## Historical type and unit differences

- 70 sessions store `duration` as a numeric string; `training1.json` stores it as an integer.
- 70 sessions store FTP values as numeric strings; `training1.json` stores them as JSON numbers.

The existing browser deliberately normalizes phase starts and FTP ratios with JavaScript `Number()`. MCP catalog reads therefore preserve source JSON, while summaries and metrics normalize numeric strings. Newly generated sessions must use canonical JSON integers/numbers and an `ftp_ratio` unit label of `number` or `ftp_ratio`.

## Intensity range

Most values are conventional FTP ratios, but the catalog contains sprint values up to 2.0 and `ftp_ramp_test_43min` reaches 6.4. The MCP validator uses a technical maximum of 10 to remain compatible with such tests. This is not a sporting recommendation.
