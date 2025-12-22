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
 
-- Correct: `__VERSION__`, `__VERSION__` , `__VERSION__`, `__VERSION__`, `__VERSION__`
-- Incorrect: `__VERSION__`, `__VERSION__` , `25` , `__VERSION__` , `__VERSION__`, `__VERSION__`, `__VERSION__`
+- Correct: `__VERSION__`, `__VERSION__` , `__VERSION__` , `__VERSION__`
+- Incorrect: `0.1` , `1` , `1.0` , `__VERSION__` , `__VERSION__`
