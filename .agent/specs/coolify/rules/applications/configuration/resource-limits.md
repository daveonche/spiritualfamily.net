# Applications › Configuration › Resource Limits

Source: coollabsio/coolify-docs `content/docs/applications/configuration/resource-limits.mdx` @ main `3b7c416`.

- **CPU limit** and the memory fields use `0` = no configured limit. Start from measured usage, not the lowest possible value.

## CPU

- **CPU limit**: whole or decimal (`2`, `0.5`); `0` = all CPUs.
- **CPU set**: cores/ranges (`0-2`, `0,1,3`); empty = all cores.
- **CPU weight**: whole ≥ 0, default `1024`; relative priority under contention only — does NOT reserve capacity.

## Memory

- Values: `0` or whole number + `b`/`k`/`m`/`g` (case-insensitive): `256m`, `1g`.
- **Memory reservation**: considered under memory pressure; default `0`.
- **Swappiness**: `0`–`100`, default `60`.
- **Memory limit**: hard limit; default `0`.
- **Memory and swap limit**: total memory+swap; default `0`.
- A hard limit below the working set → Docker OOM-kills the process; a low CPU limit can time out health checks under load.

## Apply and verify

- **Save changes** → redeploy/restart.
- Verify: **Metrics** stay below limits; **Runtime Logs** free of OOM exits/restarts/health failures; representative traffic keeps response times acceptable.
