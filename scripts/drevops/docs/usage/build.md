# Build

The full site build process is scripted in the [build](../../../../scripts/drevops/build.sh) script.
It is designed to be run locally and in CI with a single command to handle:

1. Docker Compose and Composer configuration validation
2. Building Docker images and starting containers
3. Calls site installation script
4. Checking that the stack works correctly and the site is availble

Practically, the script is a wrapper for commands that would be ran manually
and contains minimal workflow logic.

The build can be invoked by `ahoy build`.

The actual steps are described in the diagram below.

```mermaid
flowchart TB
  start(["Begin"]) --> A["1. Initialise variables from\n.env, .env.local and environment"]

  A --> B["2. Validate Docker Compose configuration"]
  B --> C["3. Validate Composer configuration"]

  C --> F{"4. Database\nin Docker\nimage?"}
  F -- Yes --> G["4.1 Pull the provided\nDocker image from registry"]
  F -- No --> H

  G --> H["5. Build images and start containers"]

  H --> H1{"6. Code export\ndir set?"}
  H1 -- Yes --> I["6.1 Export built code"]
  H1 -- No --> K

  I --> K["7. Install development\ndependencies"]

  K --> L{"8. Is theme\navailable?"}
  L -- Yes --> M["9. Install and compile front-end\ndependencies"]
  L -- No --> N["10. Install site"]
  M --> N["11. Install site"]

  N --> P["12. Check that the site\nis available"]

  P --> finish(["End"])
```
