
# Detailed Explanation:

## Global

id: unique identifier, like a slug

training_name: A string providing a descriptive name for the entire time sequence.

duration: total duration of the training in seconds

description: a short sentence to describe the training, there is always the duration in minutes.

units: A JSON object specifying the units of measurement for time and values.

## Phases

phases: A JSON array where each element represents a phase of the time sequence. Each phase is described by an object with the following properties:

start: The start time of the phase, in seconds (or the unit specified in units.time).

value: The value to apply during this phase. The value is a decimal which will be mulitply with the FTP (example value = 0.5 and FTP = 200W, so during this phase the training will be at 0.5 * 200 = 100W).

notes: (Optional) A string to add notes or annotations specific to this phase. Can be null if no note is needed.

creation_date: (Optional) A string representing the date and time of the sequence's creation, in ISO 8601 format (as in the example).

source: (Optional) A string indicating the source of the data.