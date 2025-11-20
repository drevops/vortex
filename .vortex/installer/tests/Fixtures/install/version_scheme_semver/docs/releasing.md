@@ -21,17 +21,17 @@
 6. The hash of the `HEAD` of the `production` branch matches the hash of
    the `HEAD` of `main` branch.
 
-## Version Number - Calendar Versioning (CalVer)
+## Version Number - Semantic Versioning (SemVer)
 
-Release versions are numbered according to [CalVer Versioning](https://calver.org/).
+Release versions are numbered according to [Semantic Versioning](https://semver.org/).
 
-Given a version number `YY.M.Z`:
+Given a version number `X.Y.Z`:
 
-- `YY` = Short year. No leading zeroes.
-- `M` = Short month. No leading zeroes.
+- `X` = Major release version. No leading zeroes.
+- `Y` = Minor Release version. No leading zeroes.
 - `Z` = Hotfix/patch version. No leading zeroes.
 
 Examples:
 
-- Correct: `25.1.0`, `25.11.1` , `25.1.10`, `25.10.1`, `9.12.0`
-- Incorrect: `25.0.0`, `2025.1.1` , `25` , `25.1.00` , `25.01.0`, `25.0.0`, `01.1.0`
+- Correct: `0.1.0`, `1.0.0` , `1.0.1` , `1.0.10`
+- Incorrect: `0.1` , `1` , `1.0` , `1.0.01` , `1.0.010`
