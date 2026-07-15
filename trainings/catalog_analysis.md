# BullWatt Training Catalog Analysis

## Current Catalog Summary

The current catalog contains 13 workouts between 16 and 90 minutes. The dominant structure is FTP-scaled ERG training with simple step changes and empty phase notes. Most sessions use values between 0.5 and 1.2 FTP, with one sprint workout using 2.0 FTP.

Well-covered areas:

- Sweet spot / steady muscular endurance: `sst30min`, `sst55min`, and parts of `efficient1`.
- General endurance and tempo: `endsoft50min`, `solid50min`, `tempo82min`.
- Climbing-inspired workouts: `alpehuez01`, `ventoux01`.
- Short high-intensity variety: `flash_burn`, `training2`.
- Sprint exposure: `sprint10`.

Recurring patterns:

- Warm-up values around 0.5 to 0.7 FTP.
- Productive work mainly at 0.8, 0.9, 1.0, and 1.2 FTP.
- Long simple blocks for endurance and tempo.
- Alternating 0.8 / 0.9 sweet spot blocks.
- Short spikes at 1.2 or 2.0 FTP.
- A final phase at the total duration to close the workout.

## Catalog Gaps

- Recovery sessions are almost absent; `training1` is close, but it reads like an example workout.
- Zone 2 endurance is under-represented as a clean modern base session.
- There is no classic 2x15 or 2x20 threshold-style FTP workout.
- Over-under work around FTP is missing, despite being a staple for lactate tolerance.
- VO2 Max work exists indirectly, but there is no clear 5x3min or similar VO2 session.
- 30/30 HIIT is missing, even though it is popular, easy to follow, and time efficient.
- Sprint work exists, but `sprint10` is long and extreme at 2.0 FTP; a shorter repeatable sprint workout is useful.
- Climbing sessions are present, but not a simple tempo-with-surges climbing format.

## Market Benchmark

Common patterns across Zwift, TrainerRoad, Wahoo SYSTM, ROUVY, and MyWhoosh:

- FTP-scaled workouts remain the shared foundation for structured indoor training.
- Sweet spot, threshold, VO2 Max, endurance, tempo, anaerobic, sprint, and recovery folders are common taxonomy.
- Time-efficient sessions under 35 to 60 minutes are prominent.
- Popular formats include 3x10 sweet spot, 2x20 threshold, over-under blocks, 5x3min VO2 Max, 30/30 or 40/20 HIIT, and sprint repeats.
- Modern platforms increasingly classify workouts by goal and rider level, with progressive families of workouts.
- Wahoo SYSTM goes beyond FTP through 4DP, but the practical pattern still maps well to FTP-based targets: sustained FTP, MAP/VO2, anaerobic capacity, and neuromuscular sprint work.

Sources used for benchmark:

- TrainerRoad power zones: https://support.trainerroad.com/hc/en-us/articles/115005942786-Understanding-Power-Zones
- Zwift FTP workout examples and plans: https://www.zwift.com/eu-es/news/33613-best-zwift-workouts-to-improve-your-ftp-functional-threshold-power
- Zwift training plans: https://www.zwift.com/eu/training-on-zwift
- ROUVY workout types: https://support.rouvy.com/hc/en-us/articles/33778911571729-Types-of-Workouts
- MyWhoosh workout library: https://mywhoosh.com/a-comprehensive-guide-to-mywhoosh-workouts/
- Wahoo 4DP overview: https://support.wahoofitness.com/hc/en-us/articles/34803282270226-Learn-about-4DP

## Proposed New Workouts

1. `Recovery Flow 25`: recovery, 25min, 0.45-0.55 FTP. Adds a repeatable low-stress session for easy days.
2. `Zone 2 Cruise`: endurance, 45min, 0.5-0.72 FTP. Adds a clean aerobic base workout.
3. `Tempo Builder`: tempo, 40min, 3 progressive 8min blocks at 0.80, 0.83, 0.85 FTP.
4. `Sweet Spot Ladder`: sweet spot, 50min, 6/8/10min progression at 0.88, 0.90, 0.92 FTP.
5. `Over Under Engine`: threshold, 48min, 3x8min alternating 0.95 and 1.05 FTP.
6. `FTP Anchor`: FTP, 55min, 2x15min at 0.98 and 1.00 FTP.
7. `VO2 Five by Three`: VO2 Max, 45min, 5x3min at 1.15 FTP.
8. `30-30 Ignite`: HIIT, 40min, 2 sets of 8x30sec at 1.20 FTP.
9. `Climb Surges`: climbing, 60min, tempo blocks at 0.85 FTP with 1min surges at 1.10 FTP.
10. `Sprint Kicks`: sprint, 38min, 8x15sec at 1.80 FTP with generous recovery.

## Selection Rationale

These ten workouts fill the main missing categories without duplicating the existing sessions. They keep the BullWatt UX simple: clear names, short descriptions with duration, FTP multipliers, no complex instructions, and easy-to-read interval patterns. The set gives regular amateur cyclists a reusable weekly toolbox: recovery, base, tempo, sweet spot, threshold, VO2, HIIT, climbing, and sprint work.
