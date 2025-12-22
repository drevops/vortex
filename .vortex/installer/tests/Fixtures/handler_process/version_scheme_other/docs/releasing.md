@@ -20,18 +20,3 @@
 5. There are no PRs in GitHub related to the release.
 6. The hash of the `HEAD` of the `production` branch matches the hash of
    the `HEAD` of `main` branch.
-
-## Version Number - Calendar Versioning (CalVer)
-
-Release versions are numbered according to [CalVer Versioning](https://calver.org/).
-
-Given a version number `YY.M.Z`:
-
-- `YY` = Short year. No leading zeroes.
-- `M` = Short month. No leading zeroes.
-- `Z` = Hotfix/patch version. No leading zeroes.
-
-Examples:
-
-- Correct: `__VERSION__`, `__VERSION__` , `__VERSION__`, `__VERSION__`, `__VERSION__`
-- Incorrect: `__VERSION__`, `__VERSION__` , `25` , `__VERSION__` , `__VERSION__`, `__VERSION__`, `__VERSION__`
